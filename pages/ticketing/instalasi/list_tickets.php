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

$rawInput = file_get_contents('php://input');
$jsonPayload = json_decode($rawInput, true) ?? [];
$payload = array_merge($_POST, $_GET, $jsonPayload);

$bulan = sanitize($payload['bulan'] ?? date('Y-m'));
$order = strtoupper(sanitize($payload['order'] ?? 'DESC'));

if (!in_array($order, ['ASC', 'DESC'], true)) {
    $order = 'DESC';
}

if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    echo json_encode([
        'status' => false,
        'message' => 'Format bulan tidak valid, harus YYYY-MM'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            s.schedule_id,
            s.queue_id,
            s.date            AS tanggal_service,
            s.target_status,
            s.status,
            t.nama            AS tim_nama,
            q.netpay_id,
            q.created_at      AS tanggal_dibuat,
            COALESCE(c.name, reg.name, '-') AS nama,
            COALESCE(NULLIF(c.phone_contact, ''), c.phone, reg.phone, '-') AS no_tlp,
            CONCAT_WS(' ', COALESCE(c.perumahan, reg.perumahan, ''), COALESCE(c.location, reg.location, '')) AS alamat,
            COALESCE(c.paket_internet, reg.paket_internet, '-') AS paket_internet,
            ri.catatan,
            ir.issue_id,
            ir.issue_type,
            ir.status AS issue_status,
            EXISTS (
                SELECT 1 FROM ikr_report irp WHERE irp.schedule_id = s.schedule_id
            ) AS sudah_ada_laporan
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        LEFT JOIN request_ikr ri ON ri.queue_id = s.queue_id
        LEFT JOIN register reg ON reg.registrasi_id = ri.registrasi_id
        LEFT JOIN customers c ON c.netpay_id = q.netpay_id
        LEFT JOIN tim t ON t.tim_id = s.tech_id
        LEFT JOIN issues_report ir ON ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
        WHERE s.job_type IN ('Instalasi', 'Install', 'IKR')
          AND (DATE_FORMAT(q.created_at, '%Y-%m') = :bulan OR DATE_FORMAT(s.date, '%Y-%m') = :bulan)
        ORDER BY q.created_at $order, s.date $order
    ");
    $stmt->execute([':bulan' => $bulan]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['sudah_ada_laporan'] = (bool) $row['sudah_ada_laporan'];
    }

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
