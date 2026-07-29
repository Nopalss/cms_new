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
        $period     = $fs['period']   ?? 'today';
        $status     = $fs['status']   ?? '';
        $jobType    = $fs['job_type'] ?? '';
        $tech       = $fs['tech_id']  ?? '';
        $customFrom = $fs['from']     ?? '';
        $customTo   = $fs['to']       ?? '';
    } else {
        $period     = $_REQUEST['query']['period']   ?? 'today';
        $status     = $_REQUEST['query']['status']   ?? '';
        $jobType    = $_REQUEST['query']['job_type'] ?? '';
        $tech       = $_REQUEST['query']['tech_id']  ?? '';
        $customFrom = $_REQUEST['query']['from']     ?? '';
        $customTo   = $_REQUEST['query']['to']       ?? '';
    }

    // ---------------- Resolve tanggal dari periode ----------------
    // PERHATIAN: schedules menggunakan kolom `date` (tanggal jadwal), bukan created_at
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
                    COALESCE(SUM(CASE WHEN status = 'Pending'     THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status = 'Actived'     THEN 1 ELSE 0 END), 0) AS actived,
                    COALESCE(SUM(CASE WHEN status = 'Rescheduled' THEN 1 ELSE 0 END), 0) AS rescheduled,
                    COALESCE(SUM(CASE WHEN status = 'Cancelled'   THEN 1 ELSE 0 END), 0) AS cancelled,
                    COALESCE(SUM(CASE WHEN status = 'Done'        THEN 1 ELSE 0 END), 0) AS done
                FROM schedules
                WHERE `date` BETWEEN :from AND :to";

    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute([':from' => $from, ':to' => $to]);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    // ================= DATA LIST =================
    $sql = "
        SELECT
            s.schedule_key,
            s.schedule_id,
            c.netpay_id,
            s.tech_id,
            s.date,
            s.time,
            c.location,
            s.job_type,
            s.status,
            COALESCE(t.name, tm.nama) AS technician_name
        FROM schedules s
        LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
        LEFT JOIN technician t        ON s.tech_id = t.tech_id
        LEFT JOIN tim tm              ON s.tech_id = tm.tim_id
        LEFT JOIN customers c         ON c.netpay_id = q.netpay_id
        WHERE s.`date` BETWEEN :from AND :to
    ";

    $params = [':from' => $from, ':to' => $to];

    if ($search !== '') {
        $sql .= " AND (
                    s.schedule_id LIKE :search
                    OR s.tech_id   LIKE :search
                    OR c.netpay_id LIKE :search
                    OR s.date      LIKE :search
                    OR c.location  LIKE :search
                    OR s.job_type  LIKE :search
                    OR s.status    LIKE :search
                    OR t.name      LIKE :search
                    OR tm.nama     LIKE :search
                )";
        $params[':search'] = "%{$search}%";
    }

    if ($status !== '') {
        $sql .= " AND s.status = :status";
        $params[':status'] = $status;
    }

    if ($jobType !== '') {
        $sql .= " AND s.job_type = :job_type";
        $params[':job_type'] = $jobType;
    }

    if ($tech !== '') {
        $sql .= " AND s.tech_id = :tech_id";
        $params[':tech_id'] = $tech;
    }

    $sql .= " ORDER BY s.schedule_key DESC";

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
