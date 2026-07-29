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

$schedule_id      = sanitize($_POST['schedule_id'] ?? '');
$raw_phone_contact = sanitize($_POST['phone_contact'] ?? $_POST['no_tlp'] ?? '');
$phone_contact     = preg_replace('/[^0-9]/', '', $raw_phone_contact);
$server           = sanitize($_POST['server'] ?? '');
$aduan_pelanggan  = sanitize($_POST['aduan_pelanggan'] ?? '');
$verifikasi_noc   = sanitize($_POST['verifikasi_noc'] ?? '');
$tim_id           = sanitize($_POST['tim_id'] ?? '');
$tanggal_service  = sanitize($_POST['tanggal_service'] ?? '');
$noc_id           = sanitize($_POST['noc_id'] ?? '');
$reason           = sanitize($_POST['reason'] ?? '');
$status           = sanitize($_POST['status'] ?? '');

$allowedStatus = ['Pending', 'Actived', 'Rescheduled', 'Cancelled', 'Done'];

if (empty($schedule_id)) {
    echo json_encode(['status' => false, 'message' => 'schedule_id tidak boleh kosong']);
    exit;
}

if (!empty($status) && !in_array($status, $allowedStatus, true)) {
    echo json_encode(['status' => false, 'message' => 'Status tidak valid']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil queue_id & netpay_id terkait
    $stmtSched = $pdo->prepare("
        SELECT s.queue_id, q.netpay_id
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        WHERE s.schedule_id = :schedule_id
        LIMIT 1
    ");
    $stmtSched->execute([':schedule_id' => $schedule_id]);
    $sched = $stmtSched->fetch(PDO::FETCH_ASSOC);

    if (!$sched) {
        $pdo->rollBack();
        echo json_encode(['status' => false, 'message' => 'Tiket tidak ditemukan']);
        exit;
    }

    $queue_id  = $sched['queue_id'];
    $netpay_id = $sched['netpay_id'];

    // Update phone_contact jika diisi
    if (!empty($phone_contact) && !empty($netpay_id)) {
        $stmtCust = $pdo->prepare("
            UPDATE customers
            SET phone_contact = :phone_contact,
                updated_at = CURRENT_TIMESTAMP
            WHERE netpay_id = :netpay_id
        ");
        $stmtCust->execute([
            ':phone_contact' => $phone_contact,
            ':netpay_id'      => $netpay_id
        ]);
    }

    // 2. Update request_maintenance
    $stmtRM = $pdo->prepare("
        UPDATE request_maintenance
        SET server = COALESCE(NULLIF(:server, ''), server),
            deskripsi_issue = COALESCE(NULLIF(:aduan, ''), deskripsi_issue),
            verifikasi_noc = COALESCE(NULLIF(:verifikasi, ''), verifikasi_noc),
            request_by = COALESCE(NULLIF(:noc_id, ''), request_by)
        WHERE queue_id = :queue_id
    ");
    $stmtRM->execute([
        ':server'     => $server,
        ':aduan'      => $aduan_pelanggan,
        ':verifikasi' => $verifikasi_noc,
        ':noc_id'     => $noc_id,
        ':queue_id'   => $queue_id
    ]);

    // 3. Update schedules
    $stmtSchedUp = $pdo->prepare("
        UPDATE schedules
        SET reason = :reason,
            status = COALESCE(NULLIF(:status, ''), status),
            tech_id = COALESCE(NULLIF(:tech_id, ''), tech_id),
            date = COALESCE(NULLIF(:date, ''), date),
            noc_id = COALESCE(NULLIF(:noc_id, ''), noc_id)
        WHERE schedule_id = :schedule_id
    ");
    $stmtSchedUp->execute([
        ':reason'      => $reason,
        ':status'      => $status,
        ':tech_id'     => $tim_id,
        ':date'        => $tanggal_service,
        ':noc_id'      => $noc_id,
        ':schedule_id' => $schedule_id
    ]);

    $pdo->commit();

    // Trigger FCM notification if tim_id is set
    require_once __DIR__ . "/../../../helper/fcm_helper.php";
    try {
        if (!empty($tim_id)) {
            sendTeamTicketNotification($pdo, $tim_id, $schedule_id);
        }
    } catch (Exception $fcmEx) {
        error_log("[FCM] Error sending ticket update notification: " . $fcmEx->getMessage());
    }

    echo json_encode([
        'status' => true,
        'message' => 'Perubahan berhasil disimpan'
    ]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

