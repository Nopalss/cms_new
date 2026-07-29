<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tidak valid'
    ]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM type WHERE id=?");
$stmt->execute([$id]);

echo json_encode([
    'success' => true,
    'message' => 'Data berhasil dihapus'
]);
