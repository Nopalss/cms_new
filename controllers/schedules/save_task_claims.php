<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$tech_id   = $_SESSION['id_karyawan'] ?? '';
$tech_name = $_SESSION['name'] ?? 'Teknisi';
$tim_id    = $_SESSION['tim_id'] ?? '';

if (empty($tech_id)) {
    echo json_encode(['status' => false, 'message' => 'Sesi login tidak valid.']);
    exit;
}

$inputRaw = file_get_contents('php://input');
$data     = json_decode($inputRaw, true);

$claimed   = isset($data['claimed']) && is_array($data['claimed']) ? $data['claimed'] : [];
$unclaimed = isset($data['unclaimed']) && is_array($data['unclaimed']) ? $data['unclaimed'] : [];

$saved = saveTaskClaims($pdo, $tech_id, $tech_name, $tim_id, $claimed, $unclaimed);

if ($saved) {
    echo json_encode([
        'status'  => true,
        'message' => 'Klaim tugas berhasil disimpan.',
        'claimed' => $claimed
    ]);
} else {
    echo json_encode([
        'status'  => false,
        'message' => 'Gagal menyimpan klaim tugas.'
    ]);
}
