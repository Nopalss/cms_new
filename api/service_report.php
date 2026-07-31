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
    // Menghitung total service dalam periode terpilih
    $sumSql = "SELECT COUNT(DISTINCT sr.srv_id) AS total
                FROM service_reports sr
                WHERE DATE(sr.created_at) BETWEEN :from AND :to";

    if ($_SESSION['role'] == 'teknisi') {
        $sumSql .= " AND EXISTS (SELECT 1 FROM service_report_pic srp WHERE srp.srv_id = sr.srv_id AND srp.tech_id = :tech_id) ";
    }

    $sumStmt = $pdo->prepare($sumSql);
    $sumParams = [':from' => $from, ':to' => $to];
    if ($_SESSION['role'] == 'teknisi') {
        $sumParams[':tech_id'] = $_SESSION['id_karyawan'];
    }
    $sumStmt->execute($sumParams);
    $summaryRow = $sumStmt->fetch(PDO::FETCH_ASSOC);

    $summary = [
        'total' => (int) ($summaryRow['total'] ?? 0)
    ];

    // ================= DATA LIST =================
    $sql = "SELECT sr.*, 
            COALESCE(NULLIF(TRIM(c.netpay_id),''), sr.netpay_id, '-') AS netpay_id, 
            COALESCE(NULLIF(TRIM(c.name),''), rm.nama, 'Pelanggan') AS name, 
            COALESCE(NULLIF(TRIM(c.perumahan),''), rm.perumahan, '-') AS perumahan, 
            SEC_TO_TIME(TIMESTAMPDIFF(SECOND,se.start_time,se.end_time)) AS durasi
            FROM service_reports sr 
            LEFT JOIN schedules se ON sr.schedule_id = se.schedule_id
            LEFT JOIN request_maintenance rm ON se.queue_id = rm.queue_id
            LEFT JOIN customers c ON sr.netpay_id = c.netpay_id 
            WHERE DATE(sr.created_at) BETWEEN :from AND :to";

    $params = [':from' => $from, ':to' => $to];

    if ($_SESSION['role'] == 'teknisi') {
        $sql .= " AND EXISTS (SELECT 1 FROM service_report_pic srp WHERE srp.srv_id = sr.srv_id AND srp.tech_id = :tech_id) ";
        $params[':tech_id'] = $_SESSION['id_karyawan'];
    }

    if (!empty($search)) {
        $sql .= " AND (
            sr.srv_id LIKE :search
            OR sr.netpay_id LIKE :search
            OR c.netpay_id LIKE :search
            OR c.name LIKE :search
            OR rm.nama LIKE :search
            OR c.perumahan LIKE :search
            OR rm.perumahan LIKE :search
            OR sr.problem LIKE :search
            OR sr.action LIKE :search
            OR sr.part LIKE :search
            OR sr.tanggal LIKE :search
            OR sr.keterangan LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY sr.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $service = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "summary" => $summary,
        "data"    => $service,
        "period"  => ["from" => $from, "to" => $to]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error"   => true,
        "message" => $e->getMessage()
    ]);
}
