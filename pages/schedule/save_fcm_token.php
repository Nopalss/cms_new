<?php

require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../helper/sanitize.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Akses tidak valid']);
    exit;
}

$tech_id   = $_SESSION['id_karyawan'] ?? '';
$fcm_token = sanitize($_POST['fcm_token'] ?? '');

if (empty($tech_id)) {
    echo json_encode(['status' => false, 'message' => 'Sesi teknisi tidak ditemukan']);
    exit;
}

if (empty($fcm_token)) {
    echo json_encode(['status' => false, 'message' => 'Token FCM kosong']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE technician SET fcm_token = :fcm_token WHERE tech_id = :tech_id");
    $stmt->execute([
        ':fcm_token' => $fcm_token,
        ':tech_id'   => $tech_id,
    ]);

    echo json_encode([
        'status'  => true,
        'message' => 'FCM Token berhasil disimpan'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
