<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $search = trim($_REQUEST['query']['generalSearch'] ?? '');

    // ---------------- Decode filter state ----------------
    $filterStateRaw = $_REQUEST['query']['filterState'] ?? '';
    if ($filterStateRaw) {
        $fs         = json_decode($filterStateRaw, true) ?: [];
        $period     = $fs['period']  ?? 'today';
        $customFrom = $fs['from']    ?? '';
        $customTo   = $fs['to']      ?? '';
    } else {
        $period     = $_REQUEST['query']['period'] ?? 'today';
        $customFrom = $_REQUEST['query']['from']   ?? '';
        $customTo   = $_REQUEST['query']['to']     ?? '';
    }

    // ---------------- Resolve tanggal dari periode ----------------
    $today = new DateTime('now');

    switch ($period) {
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
        case 'today':
        default:
            $from = $today->format('Y-m-d');
            $to   = $today->format('Y-m-d');
            break;
    }

    if ($from > $to) { [$from, $to] = [$to, $from]; }

    // ================= KPI SUMMARY =================
    // Menghitung total IKR dalam periode terpilih
    $sumSql = "SELECT COUNT(1) AS total
                FROM ikr_report i
                LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
                WHERE DATE(i.created_at) BETWEEN :from AND :to";

    if ($_SESSION['role'] == 'teknisi') {
        $sumSql .= ' AND irp.tech_id = :pic';
    }

    $sumStmt = $pdo->prepare($sumSql);
    $sumParams = [':from' => $from, ':to' => $to];
    if ($_SESSION['role'] == 'teknisi') {
        $sumParams[':pic'] = $_SESSION['id_karyawan'];
    }
    $sumStmt->execute($sumParams);
    $summaryRow = $sumStmt->fetch(PDO::FETCH_ASSOC);

    // Kueri paket internet terpopuler
    $pkgSql = "SELECT COALESCE(c.paket_internet, i.type_ont, '-') AS paket_internet, COUNT(1) AS cnt
               FROM ikr_report i
               LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
               LEFT JOIN customers c ON (i.netpay_id IS NOT NULL AND i.netpay_id != '-' AND i.netpay_id != 'NULL' AND i.netpay_id = c.netpay_id)
               WHERE DATE(i.created_at) BETWEEN :from AND :to";

    if ($_SESSION['role'] == 'teknisi') {
        $pkgSql .= ' AND irp.tech_id = :pic';
    }

    $pkgSql .= " GROUP BY COALESCE(c.paket_internet, i.type_ont, '-') ORDER BY cnt DESC LIMIT 1";

    $pkgStmt = $pdo->prepare($pkgSql);
    $pkgStmt->execute($sumParams);
    $pkgRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);

    $summary = [
        'total' => (int) ($summaryRow['total'] ?? 0),
        'popular_package' => $pkgRow ? $pkgRow['paket_internet'] : '-',
        'popular_package_count' => $pkgRow ? (int) $pkgRow['cnt'] : 0,
    ];

    // ================= DATA LIST =================
    $sql = "SELECT DISTINCT 
                i.*, 
                IF(i.netpay_id IS NULL OR i.netpay_id = '' OR i.netpay_id = '-' OR i.netpay_id = 'NULL', '-', i.netpay_id) AS netpay_id,
                COALESCE(c.name, reg.name, '-') AS name,
                COALESCE(c.perumahan, reg.perumahan, '-') AS perumahan 
            FROM ikr_report i 
            LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id 
            LEFT JOIN customers c ON (i.netpay_id IS NOT NULL AND i.netpay_id != '-' AND i.netpay_id != 'NULL' AND i.netpay_id = c.netpay_id)
            LEFT JOIN schedules s ON i.schedule_id = s.schedule_id
            LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
            LEFT JOIN request_ikr ri ON q.queue_id = ri.queue_id
            LEFT JOIN register reg ON ri.registrasi_id = reg.registrasi_id
            WHERE DATE(i.created_at) BETWEEN :from AND :to";

    $params = [':from' => $from, ':to' => $to];

    if ($_SESSION['role'] == 'teknisi') {
        $sql .= ' AND irp.tech_id = :pic';
        $params[":pic"] = $_SESSION['id_karyawan'];
    }

    if (!empty($search)) {
        $sql .= " AND (
                    i.ikr_id LIKE :search
                    OR i.alamat LIKE :search
                    OR c.name LIKE :search
                    OR i.rt LIKE :search
                    OR i.rw LIKE :search
                    OR i.desa LIKE :search
                    OR i.kec LIKE :search
                    OR i.kab LIKE :search
                    OR i.telp LIKE :search
                    OR i.sn LIKE :search
                    OR i.paket LIKE :search
                    OR i.type_ont LIKE :search
                    OR i.redaman LIKE :search
                    OR i.odp_no LIKE :search
                    OR i.odc_no LIKE :search
                    OR i.jc_no LIKE :search
                    OR i.mac_sebelum LIKE :search
                    OR i.mac_sesudah LIKE :search
                    OR i.odp LIKE :search
                    OR i.odc LIKE :search
                    OR i.enclosure LIKE :search
                    OR i.schedule_id LIKE :search
                )";

        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY i.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $ikr = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "summary" => $summary,
        "data"    => $ikr,
        "period"  => ["from" => $from, "to" => $to]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
