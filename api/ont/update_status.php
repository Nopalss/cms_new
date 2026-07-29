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

$ont_id         = sanitize($payload['ont_id'] ?? '');
$status         = sanitize($payload['status'] ?? '');
$netpay_id      = sanitize($payload['netpay_id'] ?? '');
$condition_note = sanitize($payload['condition_note'] ?? '');
$tech_id        = sanitize($payload['tech_id'] ?? '');

$validStatuses = ['IN_STOCK', 'IN_USE', 'DAMAGED', 'LOST', 'REPAIR'];

if (!$ont_id || !in_array($status, $validStatuses, true)) {
    echo json_encode(['status' => false, 'message' => 'ID ONT atau Status tidak valid']);
    exit;
}

try {
    // 1. Fetch current ONT state
    $stmtOld = $pdo->prepare("SELECT * FROM ont_inventory WHERE ont_id = :ont_id LIMIT 1");
    $stmtOld->execute([':ont_id' => $ont_id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        echo json_encode(['status' => false, 'message' => 'Unit ONT tidak ditemukan']);
        exit;
    }

    $pdo->beginTransaction();

    // Determine current_netpay_id
    $newNetpayId = ($status === 'IN_USE') ? ($netpay_id ?: $old['current_netpay_id']) : null;
    $updatedNote = $condition_note ?: $old['condition_note'];

    // 2. Update ont_inventory
    $stmtUpd = $pdo->prepare("
        UPDATE ont_inventory
        SET status = :status,
            current_netpay_id = :netpay_id,
            condition_note = :condition_note,
            updated_at = NOW()
        WHERE ont_id = :ont_id
    ");
    $stmtUpd->execute([
        ':status'        => $status,
        ':netpay_id'      => $newNetpayId,
        ':condition_note'=> $updatedNote,
        ':ont_id'        => $ont_id
    ]);

    // 3. Map event_type for movement log
    $eventMap = [
        'IN_STOCK' => 'RETURN_TO_STOCK',
        'IN_USE'   => 'INSTALL',
        'DAMAGED'  => 'SWAP_OUT',
        'REPAIR'   => 'REPAIR_OUT',
        'LOST'     => 'LOST'
    ];
    $eventType = $eventMap[$status] ?? 'RETURN_TO_STOCK';

    $movement_id = "MOV-UPD-" . date("YmdHis") . rand(10, 99);
    $logNote = "Perubahan status manual ke {$status}. " . ($condition_note ? "Catatan: {$condition_note}" : "");

    $stmtLog = $pdo->prepare("
        INSERT INTO ont_movement_log (
            movement_id, ont_id, netpay_id, event_type, ref_table, ref_id, event_date, tech_id, notes, created_at
        ) VALUES (
            :movement_id, :ont_id, :netpay_id, :event_type, 'manual_update', :ref_id, NOW(), :tech_id, :notes, NOW()
        )
    ");
    $stmtLog->execute([
        ':movement_id' => $movement_id,
        ':ont_id'      => $ont_id,
        ':netpay_id'   => $newNetpayId,
        ':event_type'  => $eventType,
        ':ref_id'      => $ont_id,
        ':tech_id'     => $tech_id ?: null,
        ':notes'       => $logNote
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Status unit ONT (' . $ont_id . ') berhasil diperbarui menjadi ' . $status
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal memperbarui status ONT: ' . $e->getMessage()
    ]);
}
