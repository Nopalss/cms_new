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

$schedule_id = sanitize($payload['schedule_id'] ?? '');

if (!$schedule_id) {
    echo json_encode([
        'status' => false,
        'message' => 'ID Schedule wajib diisi'
    ]);
    exit;
}

function formatDurasi($seconds) {
    if ($seconds <= 0) return '0 detik';

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $sec = $seconds % 60;

    if ($hours < 1) {
        if ($minutes < 1) {
            return "{$sec} detik";
        }
        return "{$minutes} menit {$sec} detik";
    } else {
        if ($minutes < 1) {
            return "{$hours} jam";
        }
        return "{$hours} jam {$minutes} menit";
    }
}

try {
    // 1. Ambil data utama schedule & customer
    $stmt = $pdo->prepare("
        SELECT
            s.schedule_id,
            s.queue_id,
            s.tech_id,
            s.noc_id,
            s.date            AS tanggal_service,
            s.time            AS jam_service,
            s.start_time,
            s.end_time,
            s.target_status,
            s.status,
            s.reason,
            s.catatan         AS catatan_noc,
            q.netpay_id,
            COALESCE(c.name, reg.name, '-') AS nama,
            c.phone_contact,
            COALESCE(c.phone, reg.phone, '-') AS no_tlp,
            COALESCE(c.perumahan, reg.perumahan, '-') AS perumahan,
            COALESCE(c.location, reg.location, '-') AS location,
            COALESCE(c.paket_internet, reg.paket_internet, '-') AS paket_internet,
            ri.catatan        AS catatan_ikr
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        LEFT JOIN request_ikr ri ON ri.queue_id = s.queue_id
        LEFT JOIN register reg ON reg.registrasi_id = ri.registrasi_id
        LEFT JOIN customers c ON c.netpay_id = q.netpay_id
        WHERE s.schedule_id = :schedule_id
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$detail) {
        echo json_encode([
            'status' => false,
            'message' => 'Detail tiket tidak ditemukan'
        ]);
        exit;
    }

    // 2. Hitung durasi pengerjaan teknisi
    $durasiStr = '-';
    $jamMulai = '-';
    $jamSelesai = '-';

    if (!empty($detail['start_time'])) {
        $startTime = new DateTime($detail['start_time']);
        $jamMulai = $startTime->format('H:i') . ' WIB';

        if (!empty($detail['end_time'])) {
            $endTime = new DateTime($detail['end_time']);
            $jamSelesai = $endTime->format('H:i') . ' WIB';
            $diffSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();
            $durasiStr = formatDurasi($diffSeconds);
        } else {
            $now = new DateTime();
            $diffSeconds = $now->getTimestamp() - $startTime->getTimestamp();
            $durasiStr = formatDurasi($diffSeconds) . ' (Berlangsung)';
        }
    }
    $detail['durasi'] = $durasiStr;
    $detail['jam_mulai'] = $jamMulai;
    $detail['jam_selesai'] = $jamSelesai;

    // 3. Ambil data IKR Report jika sudah selesai
    $stmtReport = $pdo->prepare("
        SELECT *
        FROM ikr_report
        WHERE schedule_id = :schedule_id
        LIMIT 1
    ");
    $stmtReport->execute([':schedule_id' => $schedule_id]);
    $report = $stmtReport->fetch(PDO::FETCH_ASSOC);

    $teknisi = [];
    if ($report) {
        $detail['sn'] = $report['sn'];
        $detail['type_ont'] = $report['type_ont'];
        $detail['redaman'] = $report['redaman'];
        $detail['odp_no'] = $report['odp_no'];
        $detail['odc_no'] = $report['odc_no'];
        $detail['jc_no'] = $report['jc_no'];
        $detail['mac_sebelum'] = $report['mac_sebelum'];
        $detail['mac_sesudah'] = $report['mac_sesudah'];
        $detail['alamat_pasang'] = $report['alamat'];

        // Ambil PIC teknisi dari ikr_report_pic
        $stmtPic = $pdo->prepare("
            SELECT t.name
            FROM ikr_report_pic irp
            JOIN technician t ON t.tech_id = irp.tech_id
            WHERE irp.ikr_id = :ikr_id
        ");
        $stmtPic->execute([':ikr_id' => $report['ikr_id']]);
        $teknisi = $stmtPic->fetchAll(PDO::FETCH_COLUMN);
    }
    $detail['teknisi'] = $teknisi;

    // 4. Ambil data laporan kendala (issues_report) jika ada
    $stmtIssue = $pdo->prepare("
        SELECT issue_id, issue_type, description, reported_by, status, created_at
        FROM issues_report
        WHERE schedule_id = :schedule_id
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtIssue->execute([':schedule_id' => $schedule_id]);
    $issueReport = $stmtIssue->fetch(PDO::FETCH_ASSOC);
    $detail['issue_report'] = $issueReport ?: null;

    echo json_encode([
        'status' => true,
        'data' => $detail
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
