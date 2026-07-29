<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonPayload = json_decode($rawInput, true) ?? [];
$payload = array_merge($_POST, $jsonPayload);

$paket_id = sanitize($payload['paket_id'] ?? '');

if (!$paket_id) {
    echo json_encode(['status' => false, 'message' => 'ID Paket wajib diisi']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM paket_internet WHERE paket_id = :id");
    $stmt->execute([':id' => $paket_id]);

    echo json_encode(['status' => true, 'message' => 'Paket internet berhasil dihapus']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Gagal menghapus paket: ' . $e->getMessage()]);
}
