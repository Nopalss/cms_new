<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$catatan = trim($data['catatan'] ?? '');
$type = trim($data['type'] ?? '');

if (!$catatan || !$type) {
    echo json_encode(['success' => false, 'message' => 'Data kosong']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO type (catatan,type) VALUES (?,?)");
$stmt->execute([$catatan, $type]);

echo json_encode([
    'success' => true,
    'message' => 'Berhasil tambah data'
]);
