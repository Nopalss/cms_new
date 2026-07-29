<?php

function getCustomerData($netpay_id)
{
    global $pdo;

    if (!$netpay_id) {
        throw new Exception("Netpay ID kosong");
    }

    // =====================
    // 1. CEK DATABASE LOKAL
    // =====================
    $stmt = $pdo->prepare("
        SELECT *
        FROM customers
        WHERE netpay_id = ?
    ");
    $stmt->execute([$netpay_id]);
    $local = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($local) {
        return [
            'netpay_id'      => $local['netpay_id'],
            'netpay_key'     => $local['netpay_key'],
            'name'           => $local['name'],
            'phone'          => $local['phone'],
            'phone_contact'  => $local['phone_contact'] ?? '',
            'paket_internet' => $local['paket_internet'],
            'is_active'      => $local['is_active'],
            'perumahan'      => $local['perumahan'],
            'location'       => $local['location'],
            'sharelock'      => $local['sharelock']
        ];
    }

    // =====================
    // 2. HIT API NETPAY
    // =====================
    $apiBase = "http://netpay.jabbar23.net/1_api/netpaydt.php";
    $token = defined('NETPAY_API_TOKEN') ? NETPAY_API_TOKEN : '';

    $query = http_build_query([
        'path'      => 'usernet',
        'netpay_id' => $netpay_id
    ]);

    $url = $apiBase . '?' . $query;

    $options = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: Bearer {$token}\r\n" .
                        "Accept: application/json\r\n",
            "timeout" => 10
        ],
        "ssl" => [
            "verify_peer"      => false,
            "verify_peer_name" => false
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        throw new Exception("Gagal koneksi ke server Netpay");
    }

    $data = json_decode($response, true);

    if (empty($data)) {
        throw new Exception("Customer tidak ditemukan di server Netpay");
    }

    return [
        'netpay_id'      => $data['netpay_id'] ?? $netpay_id,
        'netpay_key'     => $data['iduser'] ?? '',
        'name'           => $data['nama'] ?? '',
        'phone'          => $data['telepon'] ?? '',
        'phone_contact'  => '',
        'paket_internet' => $data['paket'] ?? '',
        'is_active'      => $data['status'] ?? '',
        'perumahan'      => $data['alamat'] ?? '',
        'location'       => $data['jalan'] ?? '',
        'sharelock'      => ''
    ];
}
