<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
try {

    $search    = $_POST['query']['generalSearch'] ?? '';
    $tech      = $_POST['query']['tech_id'] ?? '';
    $dateInput = $_POST['query']['date'] ?? '';

    // Konversi format tanggal (MM/DD/YYYY → YYYY-MM-DD)
    $dateObj = DateTime::createFromFormat('m/d/Y', $dateInput);
    $date    = $dateObj ? $dateObj->format('Y-m-d') : null;

    // ============================
    //      SQL BARU
    // ============================
    $sql = "
SELECT 
    t.tech_id,
    t.name AS technician_name,

    SUM(s.job_type = 'Instalasi') AS total_instalasi,
    SUM(s.job_type = 'Service') AS total_service,
    SUM(s.job_type = 'Dismantle') AS total_dismantle,

    COUNT(s.schedule_key) AS total_tugas,
    SUM(s.status = 'Done') AS total_done

FROM technician t

LEFT JOIN schedules s 
    ON (
        s.tech_id = t.tech_id
        OR s.tech_id = t.tim_id
    )
    AND (s.date = :date OR (:date IS NULL AND s.date = CURDATE()))

WHERE 1=1
";

    $params = [
        ':date' => $date
    ];

    if (!empty($tech)) {
        $sql .= " AND t.tech_id = :tech_id";
        $params[':tech_id'] = $tech;
    }

    if (!empty($search)) {
        $sql .= " AND (
        t.name LIKE :search
        OR t.tech_id LIKE :search
    )";
        $params[':search'] = "%$search%";
    }

    $sql .= " GROUP BY t.tech_id, t.name ORDER BY t.name ASC";


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapping output
    $data = array_map(function ($r) {
        return [
            "tech_id"         => $r["tech_id"],
            "technician_name" => $r["technician_name"],
            "total_instalasi" => (int) $r["total_instalasi"],
            "total_service" => (int) $r["total_service"],
            "total_dismantle"  => (int) $r["total_dismantle"],
            "total_tugas"       => (int) $r["total_tugas"],
            "total_done"        => (int) $r["total_done"]
        ];
    }, $rows);

    echo json_encode(["data" => $data]);
} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
