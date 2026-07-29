<?php

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT paket_id, name, paket, harga FROM paket_internet ORDER BY harga ASC, paket_id ASC");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data'   => $packages
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'message' => 'Gagal mengambil data paket: ' . $e->getMessage()
    ]);
}
