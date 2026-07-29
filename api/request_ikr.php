<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $search = $_REQUEST['query']['generalSearch'] ?? '';

    // ---------------- Decode filter state ----------------
    // Datatable mengirim via filterState JSON (satu reload, no race condition)
    // refreshSummary() mengirim via individual params langsung
    $filterStateRaw = $_REQUEST['query']['filterState'] ?? '';
    if ($filterStateRaw) {
        $fs         = json_decode($filterStateRaw, true) ?: [];
        $period     = $fs['period']  ?? 'today';
        $status     = $fs['status']  ?? '';
        $customFrom = $fs['from']    ?? '';
        $customTo   = $fs['to']      ?? '';
    } else {
        $period     = $_REQUEST['query']['period'] ?? 'today';
        $status     = $_REQUEST['query']['status'] ?? '';
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

    // Referensi tanggal: COALESCE karena request_ikr tidak punya created_at sendiri
    $dateRef = "COALESCE(q.created_at, r.updated_at)";

    // ================= KPI SUMMARY =================
    $sumSql = "SELECT
                    COALESCE(SUM(1), 0) AS total,
                    COALESCE(SUM(CASE WHEN q.status = 'Accepted' THEN 1 ELSE 0 END), 0) AS accepted,
                    COALESCE(SUM(CASE WHEN q.status = 'Pending'  THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN q.status = 'Rejected' THEN 1 ELSE 0 END), 0) AS rejected
                FROM request_ikr r
                LEFT JOIN queue_scheduling q ON r.queue_id = q.queue_id
                WHERE DATE($dateRef) BETWEEN :from AND :to";

    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute([':from' => $from, ':to' => $to]);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    // ================= DATA LIST =================
    $sql = "SELECT
                r.*,
                q.netpay_id AS netpay_id,
                COALESCE(q.status, 'Not Queued') AS status,
                rg.date AS jadwal_pemasangan
            FROM request_ikr r
            LEFT JOIN queue_scheduling q ON r.queue_id = q.queue_id
            LEFT JOIN register rg ON r.registrasi_id = rg.registrasi_id
            WHERE DATE($dateRef) BETWEEN :from AND :to";

    $params = [':from' => $from, ':to' => $to];

    if (!empty($search)) {
        $sql .= " AND (
                    r.rikr_id          LIKE :search
                    OR q.netpay_id     LIKE :search
                    OR r.registrasi_id LIKE :search
                    OR rg.date         LIKE :search
                    OR r.catatan       LIKE :search
                    OR q.status        LIKE :search
                )";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $sql .= " AND q.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY r.rikr_key DESC";

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