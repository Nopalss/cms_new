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
$name     = sanitize($payload['name'] ?? '');
$paket    = sanitize($payload['paket'] ?? '');
$harga    = (int)($payload['harga'] ?? 0);

if (!$name || !$paket || $harga <= 0) {
    echo json_encode(['status' => false, 'message' => 'Nama paket, kecepatan (Mbps), dan harga wajib diisi']);
    exit;
}

try {
    if ($paket_id) {
        // Edit existing package
        $stmt = $pdo->prepare("UPDATE paket_internet SET name = :name, paket = :paket, harga = :harga WHERE paket_id = :id");
        $stmt->execute([
            ':name'  => $name,
            ':paket' => $paket,
            ':harga' => $harga,
            ':id'    => $paket_id
        ]);
        $msg = 'Paket internet berhasil diperbarui!';
    } else {
        // Add new package
        $stmt = $pdo->prepare("INSERT INTO paket_internet (name, paket, harga) VALUES (:name, :paket, :harga)");
        $stmt->execute([
            ':name'  => $name,
            ':paket' => $paket,
            ':harga' => $harga
        ]);
        $msg = 'Paket internet baru berhasil ditambahkan!';
    }

    echo json_encode(['status' => true, 'message' => $msg]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Gagal menyimpan paket: ' . $e->getMessage()]);
}
