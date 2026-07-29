<?php

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$kode = $_GET['kode'] ?? '';

if ($kode == '') {

    echo json_encode([
        'status' => false
    ]);

    exit;
}

$sql = "
SELECT MAX(netpay_id)
FROM customers
WHERE netpay_id LIKE :kode
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':kode' => $kode . '%'
]);

$last = $stmt->fetchColumn();

if ($last) {

    $urut = intval(substr($last, 2)) + 1;
} else {

    $urut = 1;
}

$netpay_id = $kode . str_pad($urut, 6, '0', STR_PAD_LEFT);

echo json_encode([
    'status' => true,
    'netpay_id' => $netpay_id
]);
