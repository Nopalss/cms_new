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

$serial_number = sanitize($payload['serial_number'] ?? '');
$brand         = sanitize($payload['brand'] ?? '');
$type_ont      = sanitize($payload['type_ont'] ?? '');
$mac_address   = sanitize($payload['mac_address'] ?? '');
$condition_note= sanitize($payload['condition_note'] ?? '');

if (empty($serial_number)) {
    echo json_encode(['status' => false, 'message' => 'Serial Number (SN) wajib diisi']);
    exit;
}

try {
    // 1. Cek apakah SN sudah terdaftar
    $checkStmt = $pdo->prepare("SELECT ont_id FROM ont_inventory WHERE serial_number = :sn LIMIT 1");
    $checkStmt->execute([':sn' => $serial_number]);
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => false, 'message' => 'Serial Number (' . $serial_number . ') sudah terdaftar di sistem!']);
        exit;
    }

    $pdo->beginTransaction();

    // 2. Generate ONT ID otomatis (ONT000001)
    $maxStmt = $pdo->query("SELECT MAX(ont_key) FROM ont_inventory");
    $maxKey = (int)($maxStmt->fetchColumn() ?: 0) + 1;
    $ont_id = sprintf("ONT%06d", $maxKey);

    // Mastiin ont_id benar-benar unik
    $checkId = $pdo->prepare("SELECT ont_key FROM ont_inventory WHERE ont_id = :ont_id");
    $checkId->execute([':ont_id' => $ont_id]);
    if ($checkId->fetch()) {
        $ont_id = "ONT" . date("YmdHis") . rand(10, 99);
    }

    // 3. Insert ke ont_inventory
    $insertOnt = $pdo->prepare("
        INSERT INTO ont_inventory (
            ont_id, serial_number, type_ont, brand, mac_address, status, current_netpay_id, condition_note, created_at, updated_at
        ) VALUES (
            :ont_id, :serial_number, :type_ont, :brand, :mac_address, 'IN_STOCK', NULL, :condition_note, NOW(), NOW()
        )
    ");
    $insertOnt->execute([
        ':ont_id'        => $ont_id,
        ':serial_number' => $serial_number,
        ':type_ont'      => $type_ont ?: 'XPON',
        ':brand'         => $brand ?: 'Generic',
        ':mac_address'   => $mac_address ?: null,
        ':condition_note'=> $condition_note ?: 'Stock In Baru dari Supplier/Gudang'
    ]);

    // 4. Catat ke ont_movement_log
    $movement_id = "MOV-STK-" . date("YmdHis") . rand(10, 99);
    $insertLog = $pdo->prepare("
        INSERT INTO ont_movement_log (
            movement_id, ont_id, netpay_id, event_type, ref_table, ref_id, event_date, notes, created_at
        ) VALUES (
            :movement_id, :ont_id, NULL, 'RETURN_TO_STOCK', 'manual_stock_in', :ref_id, NOW(), :notes, NOW()
        )
    ");
    $insertLog->execute([
        ':movement_id' => $movement_id,
        ':ont_id'      => $ont_id,
        ':ref_id'      => $ont_id,
        ':notes'       => $condition_note ?: 'Stock In unit ONT baru ke gudang'
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Unit ONT baru (' . $ont_id . ' - ' . $serial_number . ') berhasil ditambahkan ke Stok Gudang!',
        'ont_id' => $ont_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal menyimpan unit ONT: ' . $e->getMessage()
    ]);
}
