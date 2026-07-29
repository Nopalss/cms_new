<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";

header('Content-Type: application/json');

// POST dipakai (bukan GET) supaya endpoint ini gak ke-cache browser/proxy,
// penting karena ini dipolling berkala dan datanya harus selalu fresh.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Akses tidak valid'
    ]);
    exit;
}

// bulan format 'YYYY-MM', default ke bulan berjalan kalau gak dikirim
$bulan = sanitize($_POST['bulan'] ?? date('Y-m'));
$order = strtoupper(sanitize($_POST['order'] ?? 'DESC'));

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
            c.name            AS nama,
            COALESCE(NULLIF(c.phone_contact, ''), c.phone) AS no_tlp,
            CONCAT_WS(' ', c.perumahan, c.location) AS alamat,
            rm.server,
            rm.deskripsi_issue AS aduan_pelanggan,
            rm.verifikasi_noc,
            ir.issue_id,
            ir.issue_type,
            ir.status AS issue_status,
            EXISTS (
                SELECT 1 FROM service_reports sr WHERE sr.schedule_id = s.schedule_id
            ) AS sudah_ada_laporan
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        LEFT JOIN request_maintenance rm ON rm.queue_id = s.queue_id
        LEFT JOIN customers c ON c.netpay_id = q.netpay_id
        LEFT JOIN tim t ON t.tim_id = s.tech_id
        LEFT JOIN issues_report ir ON ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
        WHERE s.job_type IN ('Service', 'Maintenance')
          AND (DATE_FORMAT(q.created_at, '%Y-%m') = :bulan OR DATE_FORMAT(s.date, '%Y-%m') = :bulan)
        ORDER BY q.created_at $order, s.date $order
    ");
    $stmt->execute([':bulan' => $bulan]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // cast EXISTS(...) (0/1 dari MySQL) ke boolean asli biar rapi di JSON
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
