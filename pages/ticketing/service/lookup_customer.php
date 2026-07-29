<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . "/../../../helper/getCustomerData.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Akses tidak valid'
    ]);
    exit;
}

$netpay_id = trim(sanitize($_POST['netpay_id'] ?? ''));

if (empty($netpay_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'Netpay ID tidak boleh kosong'
    ]);
    exit;
}

try {
    // 1. Cek database lokal terlebih dahulu (super cepat via UNIQUE KEY index)
    $stmt = $pdo->prepare("
        SELECT name, phone, phone_contact, perumahan, location, is_active
        FROM customers
        WHERE netpay_id = :netpay_id OR TRIM(netpay_id) = :netpay_id
        LIMIT 1
    ");
    $stmt->execute([':netpay_id' => $netpay_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $alamat = trim(($row['perumahan'] ?? '') . ' ' . ($row['location'] ?? ''));
        echo json_encode([
            'status' => true,
            'data' => [
                'nama'          => $row['name'],
                'no_tlp'        => !empty($row['phone_contact']) ? $row['phone_contact'] : $row['phone'],
                'phone'         => $row['phone'],
                'phone_contact' => $row['phone_contact'] ?? '',
                'alamat'        => $alamat,
                'is_active'     => $row['is_active'],
            ]
        ]);
        exit;
    }

    // 2. Jika tidak ada di lokal, coba panggil helper getCustomerData (API Netpay fallback)
    try {
        $dataNetpay = getCustomerData($netpay_id);
        $alamat = trim(($dataNetpay['perumahan'] ?? '') . ' ' . ($dataNetpay['location'] ?? ''));
        echo json_encode([
            'status' => true,
            'data' => [
                'nama'      => $dataNetpay['name'],
                'no_tlp'    => $dataNetpay['phone'],
                'alamat'    => $alamat,
                'is_active' => $dataNetpay['is_active'],
            ]
        ]);
        exit;
    } catch (Exception $exNetpay) {
        echo json_encode([
            'status' => false,
            'message' => 'Netpay ID tidak ditemukan'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

