<?php

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$id = $_GET['netpay_id'] ?? '';

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'Netpay ID kosong'
    ]);
    exit;
}

try {

    // =====================
    // CEK DATABASE LOKAL
    // =====================
    $stmt = $pdo->prepare("
        SELECT *
        FROM customers
        WHERE netpay_id = ?
    ");

    $stmt->execute([$id]);

    $local = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($local) {

        echo json_encode([
            'success' => true,
            'source' => 'database',
            'data' => [
                'netpay_id' => $local['netpay_id'],
                'netpay_key' => $local['netpay_key'],
                'name' => $local['name'],
                'phone' => $local['phone'],
                'phone_contact' => $local['phone_contact'] ?? '',
                'paket_internet' => $local['paket_internet'],
                'is_active' => $local['is_active'],
                'perumahan' => $local['perumahan'],
                'location' => $local['location'],
                'sharelock' => $local['sharelock']
            ]
        ]);

        exit;
    }

    // =====================
    // HIT API NETPAY
    // =====================

    $apiBase = "https://netpay.jabbar23.net/1_api/netpaydt.php";

    $token = defined('NETPAY_API_TOKEN')
        ? NETPAY_API_TOKEN
        : '';

    $query = http_build_query([
        'path' => 'usernet',
        'netpay_id' => $id
    ]);

    $url = $apiBase . '?' . $query;

    $options = [
        "http" => [
            "method" => "GET",
            "header" =>
            "Authorization: Bearer {$token}\r\n" .
                "Accept: application/json\r\n",
            "timeout" => 10
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];

    $context = stream_context_create($options);

    $response = @file_get_contents(
        $url,
        false,
        $context
    );

    // if ($response === false) {

    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Gagal koneksi ke server Netpay'
    //     ]);

    //     exit;
    // }

    $data = json_decode($response, true);

    if (empty($data)) {

        echo json_encode([
            'success' => false,
            'message' => "Customer tidak ditemukan"
        ]);

        exit;
    }

    echo json_encode([
        'success' => true,
        'source' => 'api',
        'data' => [
            'netpay_id' => $data['netpay_id'] ?? $id,
            'netpay_key' => $data['iduser'] ?? '',
            'name' => $data['nama'] ?? '',
            'phone' => $data['telepon'] ?? '',
            'phone_contact' => '',
            'paket_internet' => $data['paket'] ?? '',
            'is_active' => $data['status'] ?? '',
            'perumahan' => $data['alamat'] ?? '',
            'location' => $data['jalan'] ?? '',
            'sharelock' => ''
        ]
    ]);
} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
