<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Akses tidak valid'
    ]);
    exit;
}

$netpay_id = sanitize($_POST['netpay_id'] ?? '');

if (empty($netpay_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'Netpay ID tidak boleh kosong'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT name, phone, perumahan, location, is_active
        FROM customers
        WHERE netpay_id = :netpay_id
        LIMIT 1
    ");
    $stmt->execute([':netpay_id' => $netpay_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'status' => false,
            'message' => 'Netpay ID tidak ditemukan'
        ]);
        exit;
    }

    // Alamat = gabungan perumahan + location, sesuai keputusan sebelumnya
    $alamat = trim(($row['perumahan'] ?? '') . ' ' . ($row['location'] ?? ''));

    echo json_encode([
        'status' => true,
        'data' => [
            'nama'      => $row['name'],
            'no_tlp'    => $row['phone'],
            'alamat'    => $alamat,
            'is_active' => $row['is_active'], // frontend bisa kasih warning kalau bukan 'ACTIVE'
        ]
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
