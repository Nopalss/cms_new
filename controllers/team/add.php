<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');

if (!$name) {
    echo json_encode([
        'success' => false,
        'message' => 'Nama team wajib diisi'
    ]);
    exit;
}

// generate TIM ID
$tim_id = 'TIM' . date('YmdHis');

try {

    $stmt = $pdo->prepare("
        INSERT INTO tim (tim_id, nama)
        VALUES (:tim_id, :nama)
    ");

    $stmt->execute([
        ':tim_id' => $tim_id,
        ':nama'   => $name
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Team berhasil dibuat'
    ]);
} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
