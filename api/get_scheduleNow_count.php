<?php
require_once __DIR__ . "/../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $sql = "
        SELECT
            qs.*,

            ri.rikr_id,
            rm.rm_id,
            rd.rd_id,

            CASE
                WHEN qs.type_queue = 'Install' THEN ri.rikr_id
                WHEN qs.type_queue IN ('Maintenance','Service') THEN rm.rm_id
                WHEN qs.type_queue = 'Dismantle' THEN rd.rd_id
                ELSE NULL
            END AS request_id

        FROM queue_scheduling qs

        LEFT JOIN request_ikr ri
            ON ri.queue_id = qs.queue_id

        LEFT JOIN request_maintenance rm
            ON rm.queue_id = qs.queue_id

        LEFT JOIN request_dismantle rd
            ON rd.queue_id = qs.queue_id

        WHERE qs.status = 'Pending'

        ORDER BY qs.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grouping
    $install   = [];
    $service   = [];
    $dismantle = [];

    foreach ($rows as $row) {

        switch ($row['type_queue']) {

            case 'Install':
                $install[] = $row;
                break;

            case 'Maintenance':
            case 'Service':
                $service[] = $row;
                break;

            case 'Dismantle':
                $dismantle[] = $row;
                break;
        }
    }

    echo json_encode([
        "status"     => "success",
        "total"      => count($rows),
        "data"       => $rows,
        "install"    => $install,
        "service"    => $service,
        "dismantle"  => $dismantle
    ]);
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
