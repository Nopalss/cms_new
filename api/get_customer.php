<?php
require_once __DIR__ . "/../includes/config.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $netpay_id = isset($_POST['netpay_id']) ? trim($_POST['netpay_id']) : null;

    if (empty($netpay_id)) {
        echo json_encode([
            "status" => "error",
            "message" => "netpay_id tidak boleh kosong"
        ]);
        exit;
    }

    $sql = "SELECT * FROM customers WHERE netpay_id = :netpay_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":netpay_id" => $netpay_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            "status" => "success",
            "data" => $result
        ]);
    } else {
        echo json_encode([
            "status" => "not_found",
            "message" => "Customer tidak ditemukan"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
