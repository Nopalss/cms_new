<?php
require_once __DIR__ . "/../includes/config.php";
header('Content-Type: application/json; charset=utf-8');

try {
    // Ambil semua data pending
    $sql = "SELECT * FROM issues_report WHERE status = 'Pending'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "status"      => "success",
        "total"       => count($rows), // total pending
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
