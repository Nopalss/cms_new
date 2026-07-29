<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id   = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');

if (!$id || !$name) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE tim 
        SET nama = :nama
        WHERE id = :id
    ");

    $stmt->execute([
        ':nama' => $name,
        ':id'   => $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Team berhasil diupdate'
    ]);
} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
