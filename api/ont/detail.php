<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

header('Content-Type: application/json');

try {
    $ont_id = sanitize($_GET['ont_id'] ?? $_GET['id'] ?? '');

    if (!$ont_id) {
        echo json_encode([
            'status' => false,
            'message' => 'ID ONT tidak valid'
        ]);
        exit;
    }

    // 1. Fetch ONT Inventory Record
    $stmt = $pdo->prepare("
        SELECT 
            o.ont_key,
            o.ont_id,
            o.serial_number,
            o.type_ont,
            o.brand,
            o.mac_address,
            o.status,
            o.current_netpay_id,
            o.condition_note,
            o.created_at,
            o.updated_at,
            c.name AS customer_name,
            c.phone AS customer_phone,
            c.paket_internet AS customer_paket,
            CONCAT_WS(' ', c.perumahan, c.location) AS customer_alamat
        FROM ont_inventory o
        LEFT JOIN customers c ON c.netpay_id = o.current_netpay_id
        WHERE o.ont_id = :ont_id OR o.serial_number = :ont_id
        LIMIT 1
    ");
    $stmt->execute([':ont_id' => $ont_id]);
    $ont = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ont) {
        echo json_encode([
            'status' => false,
            'message' => 'Unit ONT tidak ditemukan'
        ]);
        exit;
    }

    // 2. Fetch Movement Log History
    $stmtLog = $pdo->prepare("
        SELECT 
            m.movement_key,
            m.movement_id,
            m.ont_id,
            m.netpay_id,
            m.event_type,
            m.ref_table,
            m.ref_id,
            m.event_date,
            m.tech_id,
            m.notes,
            m.created_at,
            c.name AS customer_name,
            t.name AS tech_name
        FROM ont_movement_log m
        LEFT JOIN customers c ON c.netpay_id = m.netpay_id
        LEFT JOIN technician t ON t.tech_id = m.tech_id
        WHERE m.ont_id = :ont_id
        ORDER BY m.event_date DESC, m.movement_key DESC
    ");
    $stmtLog->execute([':ont_id' => $ont['ont_id']]);
    $movements = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'ont' => $ont,
        'movements' => $movements
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengambil detail ONT: ' . $e->getMessage()
    ]);
}
