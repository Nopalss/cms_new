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

if (empty($schedule_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'schedule_id tidak boleh kosong'
    ]);
    exit;
}

/*
Hitung durasi dari waktu komplain masuk (queue_scheduling.created_at)
sampai laporan teknisi disubmit (service_reports.created_at).
Format: "X hari Y jam Z menit" (bagian yang 0 gak ditampilin).
*/
function formatDurasi(string $start, string $end): string
{
    $startDt = new DateTime($start);
    $endDt   = new DateTime($end);

    $totalSeconds = $endDt->getTimestamp() - $startDt->getTimestamp();
    if ($totalSeconds < 0) $totalSeconds = 0;

    $days    = (int) floor($totalSeconds / 86400);
    $hours   = (int) floor(($totalSeconds % 86400) / 3600);
    $minutes = (int) floor(($totalSeconds % 3600) / 60);
    $seconds = (int) ($totalSeconds % 60);

    if ($days > 0) {
        $parts = [$days . ' hari'];
        if ($hours > 0) $parts[] = $hours . ' jam';
        if ($minutes > 0) $parts[] = $minutes . ' menit';
        return implode(' ', $parts);
    }

    if ($hours > 0) {
        $parts = [$hours . ' jam'];
        if ($minutes > 0) $parts[] = $minutes . ' menit';
        return implode(' ', $parts);
    }

    // Dibawah 1 jam -> Menit & Detik
    $parts = [];
    if ($minutes > 0) $parts[] = $minutes . ' menit';
    if ($seconds > 0 || empty($parts)) $parts[] = $seconds . ' detik';

    return implode(' ', $parts);
}

function formatTime(?string $datetime): ?string
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return null;
    try {
        $dt = new DateTime($datetime);
        return $dt->format('H:i') . ' WIB';
    } catch (Exception $e) {
        return null;
    }
}

try {
    // 1. Data utama tiket (tanpa laporan teknisi dulu)
    $stmt = $pdo->prepare("
        SELECT
            s.schedule_id,
            s.queue_id,
            s.date            AS tanggal_service,
            s.start_time,
            s.end_time,
            s.target_status,
            s.status,
            s.tech_id,
            s.noc_id,
            s.reason,
            s.catatan,
            t.nama            AS tim_nama,
            COALESCE(q.netpay_id, '-') AS netpay_id,
            q.created_at      AS tanggal_komplain,
            COALESCE(c.name, rm.nama, 'Infrastruktur Jaringan') AS nama,
            c.phone,
            c.phone_contact,
            COALESCE(NULLIF(c.phone_contact, ''), c.phone, '-') AS no_tlp,
            COALESCE(NULLIF(TRIM(CONCAT_WS(' ', rm.perumahan, rm.location)), ''), CONCAT_WS(' ', c.perumahan, c.location), '-') AS alamat,
            rm.perumahan AS non_perumahan,
            rm.location AS non_location,
            rm.sharelock AS non_sharelock,
            rm.server,
            rm.deskripsi_issue AS aduan_pelanggan,
            rm.verifikasi_noc
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        LEFT JOIN request_maintenance rm ON rm.queue_id = s.queue_id
        LEFT JOIN customers c ON c.netpay_id = q.netpay_id
        LEFT JOIN tim t ON t.tim_id = s.tech_id
        WHERE s.schedule_id = :schedule_id
        LIMIT 1
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode([
            'status' => false,
            'message' => 'Tiket tidak ditemukan'
        ]);
        exit;
    }

    // 2. Laporan teknisi (kalau udah ada). Ambil yang paling baru kalau
    //    entah kenapa ada lebih dari satu laporan untuk schedule_id yang sama.
    $stmt = $pdo->prepare("
        SELECT srv_id, problem, action, part, ont_bef, ont_aft,
               red_bef, red_aft, keterangan, tanggal, jam, created_at
        FROM service_reports
        WHERE schedule_id = :schedule_id
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    $akar_masalah = null;
    $penanganan   = null;
    $teknisi      = [];
    $durasi       = null;
    $jam_mulai    = null;
    $jam_selesai  = null;
    $keterangan   = null;
    $detail_laporan = null;

    if ($report) {
        $akar_masalah = $report['problem'];
        $penanganan   = $report['action'];
        $jam_mulai    = formatTime($ticket['start_time'] ?? null);
        $jam_selesai  = formatTime($ticket['end_time'] ?? null) ?? formatTime($report['created_at'] ?? null);
        $keterangan   = $report['keterangan'] ?? null;

        $startTimeVal = $ticket['start_time'] ?? null;
        $endTimeVal   = $ticket['end_time'] ?? $report['created_at'] ?? null;

        if (!empty($startTimeVal) && !empty($endTimeVal)) {
            $durasi = formatDurasi($startTimeVal, $endTimeVal);
        } elseif (!empty($ticket['tanggal_komplain']) && !empty($endTimeVal)) {
            $durasi = formatDurasi($ticket['tanggal_komplain'], $endTimeVal);
        }

        $detail_laporan = [
            'part'       => $report['part'],
            'ont_bef'    => $report['ont_bef'],
            'ont_aft'    => $report['ont_aft'],
            'red_bef'    => $report['red_bef'],
            'red_aft'    => $report['red_aft'],
            'keterangan' => $report['keterangan'],
        ];

        // 3. Nama-nama teknisi (PIC) yang ngerjain
        $stmtPic = $pdo->prepare("
            SELECT tech.name
            FROM service_report_pic srp
            JOIN technician tech ON tech.tech_id = srp.tech_id
            WHERE srp.srv_id = :srv_id
        ");
        $stmtPic->execute([':srv_id' => $report['srv_id']]);
        $teknisi = array_column($stmtPic->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    // 4. Laporan kendala teknisi (jika ada)
    $stmtIssue = $pdo->prepare("
        SELECT issue_id, issue_type, description, status, reported_by, created_at
        FROM issues_report
        WHERE schedule_id = :schedule_id
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtIssue->execute([':schedule_id' => $schedule_id]);
    $issue_report = $stmtIssue->fetch(PDO::FETCH_ASSOC) ?: null;

    echo json_encode([
        'status' => true,
        'data' => array_merge($ticket, [
            'akar_masalah'   => $akar_masalah,
            'penanganan'     => $penanganan,
            'teknisi'        => $teknisi,   // array nama, join di frontend kalau perlu jadi 1 string
            'jam_mulai'      => $jam_mulai,
            'jam_selesai'    => $jam_selesai,
            'durasi'         => $durasi,
            'keterangan'     => $keterangan,
            'detail_laporan' => $detail_laporan,
            'issue_report'   => $issue_report,
        ])
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
