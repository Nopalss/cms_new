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
        $status     = $fs['status']  ?? '';
        $type       = $fs['type']    ?? '';
        $customFrom = $fs['from']    ?? '';
        $customTo   = $fs['to']      ?? '';
    } else {
        $period     = $_REQUEST['query']['period'] ?? 'today';
        $status     = $_REQUEST['query']['status'] ?? '';
        $type       = $_REQUEST['query']['type']   ?? '';
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
    $sumSql = "SELECT
                    COALESCE(SUM(1), 0) AS total,
                    COALESCE(SUM(CASE WHEN status = 'Pending'  THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END), 0) AS accepted,
                    COALESCE(SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END), 0) AS rejected,
                    COALESCE(SUM(CASE WHEN type_queue = 'Install'     THEN 1 ELSE 0 END), 0) AS type_install,
                    COALESCE(SUM(CASE WHEN type_queue = 'Maintenance' THEN 1 ELSE 0 END), 0) AS type_maintenance,
                    COALESCE(SUM(CASE WHEN type_queue = 'Dismantle'   THEN 1 ELSE 0 END), 0) AS type_dismantle,
                    COALESCE(SUM(CASE WHEN type_queue = 'Service'     THEN 1 ELSE 0 END), 0) AS type_service
                FROM queue_scheduling
                WHERE DATE(created_at) BETWEEN :from AND :to";

    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute([':from' => $from, ':to' => $to]);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    // ================= DATA LIST =================
    $sql = "
        SELECT
            qs.*,
            ri.rikr_id,
            rm.rm_id,
            rd.rd_id,
            CASE
                WHEN qs.type_queue = 'Install'               THEN ri.rikr_id
                WHEN qs.type_queue IN ('Maintenance','Service') THEN rm.rm_id
                WHEN qs.type_queue = 'Dismantle'             THEN rd.rd_id
                ELSE NULL
            END AS request_id
        FROM queue_scheduling qs
        LEFT JOIN request_ikr         ri ON ri.queue_id = qs.queue_id
        LEFT JOIN request_maintenance rm ON rm.queue_id = qs.queue_id
        LEFT JOIN request_dismantle   rd ON rd.queue_id = qs.queue_id
        WHERE DATE(qs.created_at) BETWEEN :from AND :to
    ";

    $params = [':from' => $from, ':to' => $to];

    if ($search !== '') {
        $sql .= " AND (
                    qs.queue_id    LIKE :search
                    OR qs.netpay_id  LIKE :search
                    OR qs.type_queue LIKE :search
                    OR qs.status     LIKE :search
                    OR ri.rikr_id    LIKE :search
                    OR rm.rm_id      LIKE :search
                    OR rd.rd_id      LIKE :search
                )";
        $params[':search'] = "%{$search}%";
    }

    if ($status !== '') {
        $sql .= " AND qs.status = :status";
        $params[':status'] = $status;
    }

    if ($type !== '') {
        $sql .= " AND qs.type_queue = :type";
        $params[':type'] = $type;
    }

    $sql .= " ORDER BY qs.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "summary" => $summary,
        "data"    => $data,
        "period"  => ["from" => $from, "to" => $to]
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "message" => $e->getMessage()]);
}
