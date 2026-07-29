<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? 0;
$catatan = trim($data['catatan'] ?? '');
$type = trim($data['type'] ?? '');

if (!$id || !$catatan || !$type) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$stmt = $pdo->prepare("UPDATE type SET catatan=?, type=? WHERE id=?");
$stmt->execute([$catatan, $type, $id]);

echo json_encode([
    'success' => true,
    'message' => 'Berhasil update data'
]);
