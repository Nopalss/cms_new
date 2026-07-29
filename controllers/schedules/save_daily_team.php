<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$tech_id = $_SESSION['id_karyawan'] ?? '';
$tim_id  = $_SESSION['tim_id'] ?? '';

if (empty($tech_id)) {
    echo json_encode(['status' => false, 'message' => 'Sesi login tidak valid.']);
    exit;
}

// Receive partners array from POST or JSON input
$inputRaw = file_get_contents('php://input');
$data     = json_decode($inputRaw, true);

$partners = [];
if (isset($data['partners']) && is_array($data['partners'])) {
    $partners = $data['partners'];
} elseif (isset($_POST['partners']) && is_array($_POST['partners'])) {
    $partners = $_POST['partners'];
}

// Ensure logged-in technician is included
if (!in_array($tech_id, $partners, true)) {
    $partners[] = $tech_id;
}

$saved = saveDailyShiftTeam($pdo, $tim_id, $tech_id, $partners);

if ($saved) {
    echo json_encode([
        'status'   => true,
        'message'  => 'Tim hari ini berhasil disimpan.',
        'partners' => array_values(array_unique($partners))
    ]);
} else {
    echo json_encode([
        'status'  => false,
        'message' => 'Gagal menyimpan data tim ke database.'
    ]);
}
