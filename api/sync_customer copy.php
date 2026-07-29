<?php

require_once __DIR__ . "/../includes/config.php";
include __DIR__ . "/token/env.php";

header("Content-Type: application/json");

/*
|--------------------------------------------------------------------------
| LOAD ENV
|--------------------------------------------------------------------------
*/

$integrationToken = $env['JBR_CMS_INTEGRATION_TOKEN'];

/*
|--------------------------------------------------------------------------
| VALIDASI TOKEN
|--------------------------------------------------------------------------
*/

$headers = function_exists('getallheaders') ? getallheaders() : [];

$authHeader = '';

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
} elseif (isset($headers['authorization'])) {
    $authHeader = $headers['authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {

    http_response_code(401);

    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);

    exit;
}

if (trim($matches[1]) !== $integrationToken) {

    http_response_code(401);

    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL BODY JSON
|--------------------------------------------------------------------------
*/

$body = file_get_contents("php://input");

$data = json_decode($body, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "status" => false,
        "message" => "Payload tidak valid."
    ]);

    exit;
}

try {

    $pdo->beginTransaction();

    $insert = 0;
    $update = 0;

    $checkStmt = $pdo->prepare("
        SELECT netpay_key
        FROM customers
        WHERE netpay_id = :netpay_id
        LIMIT 1
    ");

    $insertStmt = $pdo->prepare("
        INSERT INTO customers
        (
            netpay_id,
            name,
            perumahan,
            location,
            phone,
            paket_internet,
            is_active,
            harga,
            ip
        )
        VALUES
        (
            :netpay_id,
            :name,
            :perumahan,
            :location,
            :phone,
            :paket_internet,
            :is_active,
            :harga,
            :ip
        )
    ");

    $updateStmt = $pdo->prepare("
        UPDATE customers
        SET
            name = :name,
            perumahan = :perumahan,
            location = :location,
            phone = :phone,
            paket_internet = :paket_internet,
            is_active = :is_active,
            harga = :harga,
            ip = :ip,
            updated_at = CURRENT_TIMESTAMP
        WHERE netpay_id = :netpay_id
    ");

    foreach ($data as $customer) {

        $checkStmt->execute([
            ':netpay_id' => $customer['netpay_id']
        ]);

        if ($checkStmt->fetch()) {

            $updateStmt->execute([

                ':netpay_id'        => $customer['netpay_id'],
                ':name'             => $customer['name'],
                ':perumahan'        => $customer['perumahan'],
                ':location'         => $customer['location'],
                ':phone'            => $customer['phone'],
                ':paket_internet'   => $customer['paket_internet'],
                ':is_active'        => $customer['is_active'],
                ':harga'            => $customer['harga'],
                ':ip'               => $customer['ip']

            ]);

            $update++;
        } else {

            $insertStmt->execute([

                ':netpay_id'        => $customer['netpay_id'],
                ':name'             => $customer['name'],
                ':perumahan'        => $customer['perumahan'],
                ':location'         => $customer['location'],
                ':phone'            => $customer['phone'],
                ':paket_internet'   => $customer['paket_internet'],
                ':is_active'        => $customer['is_active'],
                ':harga'            => $customer['harga'],
                ':ip'               => $customer['ip']

            ]);

            $insert++;
        }
    }

    $pdo->commit();

    echo json_encode([
        "status" => true,
        "message" => "Sinkronisasi berhasil.",
        "total" => count($data),
        "insert" => $insert,
        "update" => $update
    ]);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "status" => false,
        "message" => $e->getMessage()
    ]);
}
