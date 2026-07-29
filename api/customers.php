<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $search        = trim($_REQUEST['query']['generalSearch'] ?? '');
    $pake_internet = trim($_REQUEST['query']['paket_internet'] ?? '');
    $status        = trim($_REQUEST['query']['status'] ?? '');

    // ---------------- Decode filter state ----------------
    $filterStateRaw = $_REQUEST['query']['filterState'] ?? '';
    if ($filterStateRaw) {
        $fs         = json_decode($filterStateRaw, true) ?: [];
        $period     = $fs['period']  ?? 'all';
        $customFrom = $fs['from']    ?? '';
        $customTo   = $fs['to']      ?? '';
    } else {
        $period     = $_REQUEST['query']['period'] ?? 'all';
        $customFrom = $_REQUEST['query']['from']   ?? '';
        $customTo   = $_REQUEST['query']['to']     ?? '';
    }

    // ---------------- Resolve tanggal dari periode ----------------
    $today = new DateTime('now');
    $use_period = true;

    switch ($period) {
        case 'today':
            $from = $today->format('Y-m-d');
            $to   = $today->format('Y-m-d');
            break;
        case 'week':
            $from = (clone $today)->modify('monday this week')->format('Y-m-d');
            $to   = (clone $today)->modify('sunday this week')->format('Y-m-d');
            break;
        case 'month':
            $from = $today->format('Y-m-01');
            $to   = $today->format('Y-m-t');
            break;
        case 'custom':
            $fromObj = DateTime::createFromFormat('Y-m-d', $customFrom);
            $toObj   = DateTime::createFromFormat('Y-m-d', $customTo);
            $from = $fromObj ? $fromObj->format('Y-m-d') : $today->format('Y-m-d');
            $to   = $toObj   ? $toObj->format('Y-m-d')   : $today->format('Y-m-d');
            break;
        case 'all':
        default:
            $use_period = false;
            break;
    }

    if ($use_period && $from > $to) { [$from, $to] = [$to, $from]; }

    // ================= KPI SUMMARY =================
    // 1. Total baru terdaftar dalam periode terpilih
    if ($use_period) {
        $newStmt = $pdo->prepare("SELECT COUNT(1) AS cnt FROM customers WHERE DATE(created_at) BETWEEN :from AND :to");
        $newStmt->execute([':from' => $from, ':to' => $to]);
    } else {
        $newStmt = $pdo->query("SELECT COUNT(1) AS cnt FROM customers");
    }
    $newCount = (int) ($newStmt->fetchColumn() ?: 0);

    // 2. Total active customers overall
    $activeStmt = $pdo->query("SELECT COUNT(1) AS cnt FROM customers WHERE LOWER(is_active) = 'active'");
    $totalActive = (int) ($activeStmt->fetchColumn() ?: 0);

    // 3. Most popular package within the selected period
    if ($use_period) {
        $pkgSql = "SELECT paket_internet, COUNT(1) AS cnt
                   FROM customers
                   WHERE DATE(created_at) BETWEEN :from AND :to
                   GROUP BY paket_internet
                   ORDER BY cnt DESC
                   LIMIT 1";
        $pkgStmt = $pdo->prepare($pkgSql);
        $pkgStmt->execute([':from' => $from, ':to' => $to]);
    } else {
        $pkgSql = "SELECT paket_internet, COUNT(1) AS cnt
                   FROM customers
                   GROUP BY paket_internet
                   ORDER BY cnt DESC
                   LIMIT 1";
        $pkgStmt = $pdo->query($pkgSql);
    }
    $pkgRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $summary = [
        'new_count' => $newCount,
        'total_active' => $totalActive,
        'popular_package' => $pkgRow ? $pkgRow['paket_internet'] . ' Mbps' : '-',
        'popular_package_count' => $pkgRow ? (int) $pkgRow['cnt'] : 0,
    ];

    // ================= DATA LIST =================
    $sql = "SELECT * FROM customers WHERE 1=1";
    $params = [];

    // Filter by period only if not searching (search searches all data) and period is not 'all'
    if ($use_period && empty($search)) {
        $sql .= " AND DATE(created_at) BETWEEN :from AND :to";
        $params[':from'] = $from;
        $params[':to'] = $to;
    }

    if (!empty($search)) {
        $sql .= " AND (
                    netpay_id LIKE :search
                    OR name LIKE :search
                    OR location LIKE :search
                    OR phone LIKE :search
                    OR paket_internet LIKE :search
                )";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $sql .= " AND LOWER(is_active) = :is_active";
        $params[':is_active'] = strtolower($status);
    }
    if (!empty($pake_internet)) {
        $sql .= " AND paket_internet LIKE :paket_internet";
        $params[':paket_internet'] = $pake_internet;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "summary" => $summary,
        "data" => $customers
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
