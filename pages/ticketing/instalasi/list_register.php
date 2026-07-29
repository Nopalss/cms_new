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

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.registrasi_id,
            r.name,
            r.phone,
            r.perumahan,
            r.location,
            r.paket_internet,
            r.date AS tgl_reg,
            r.time AS jam_reg,
            ri.rikr_id,
            ri.catatan,
            q.netpay_id
        FROM register r
        LEFT JOIN request_ikr ri ON ri.registrasi_id = r.registrasi_id
        LEFT JOIN queue_scheduling q ON q.queue_id = ri.queue_id
        ORDER BY r.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $rows
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
