<?php
require_once __DIR__ . "/../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT COUNT(*) as total FROM register WHERE is_verified = 'Unverified'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "total" => $result['total']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
