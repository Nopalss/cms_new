<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . "/../../../helper/fcm_helper.php";

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
$payload = array_merge($_POST, $jsonPayload);

$schedule_id    = sanitize($payload['schedule_id'] ?? '');
$name           = sanitize($payload['name'] ?? '');
$phone_contact  = sanitize($payload['phone_contact'] ?? '');
$paket_internet = sanitize($payload['paket_internet'] ?? '');
$perumahan      = sanitize($payload['perumahan'] ?? '');
$location       = sanitize($payload['location'] ?? '');
$catatan        = sanitize($payload['catatan'] ?? '');
$tim_id         = sanitize($payload['tim_id'] ?? '');
$tanggal_service= sanitize($payload['tanggal_service'] ?? '');
$noc_id         = sanitize($payload['noc_id'] ?? '');
$status         = sanitize($payload['status'] ?? '');
$reason         = sanitize($payload['reason'] ?? '');

if (!$schedule_id) {
    echo json_encode([
        'status' => false,
        'message' => 'ID Schedule wajib diisi'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil detail ticket lama
    $stmtOld = $pdo->prepare("
        SELECT s.schedule_id, s.queue_id, s.tim_id, s.tech_id, q.netpay_id
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        WHERE s.schedule_id = :schedule_id
    ");
    $stmtOld->execute([':schedule_id' => $schedule_id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        throw new Exception('Tiket tidak ditemukan');
    }

    // 2. Update schedules
    $stmtSch = $pdo->prepare("
        UPDATE schedules
        SET tech_id = :tech_id,
            tim_id  = :tim_id,
            noc_id  = :noc_id,
            date    = :date,
            status  = :status,
            reason  = :reason,
            catatan = :catatan
        WHERE schedule_id = :schedule_id
    ");
    $stmtSch->execute([
        ':tech_id'     => $tim_id,
        ':tim_id'      => $tim_id,
        ':noc_id'      => $noc_id,
        ':date'        => $tanggal_service,
        ':status'      => $status,
        ':reason'      => $reason,
        ':catatan'     => $catatan,
        ':schedule_id' => $schedule_id,
    ]);

    // 3. Update customer data (Nama, Phone Contact, Paket, Perumahan, Location)
    if ($old['netpay_id']) {
        $stmtCust = $pdo->prepare("
            UPDATE customers
            SET name           = CASE WHEN :name != '' THEN :name ELSE name END,
                phone_contact  = CASE WHEN :phone_contact != '' THEN :phone_contact ELSE phone_contact END,
                paket_internet = CASE WHEN :paket_internet != '' THEN :paket_internet ELSE paket_internet END,
                perumahan      = CASE WHEN :perumahan != '' THEN :perumahan ELSE perumahan END,
                location       = CASE WHEN :location != '' THEN :location ELSE location END
            WHERE netpay_id = :netpay_id
        ");
        $stmtCust->execute([
            ':name'           => $name,
            ':phone_contact'  => $phone_contact,
            ':paket_internet' => $paket_internet,
            ':perumahan'      => $perumahan,
            ':location'       => $location,
            ':netpay_id'      => $old['netpay_id']
        ]);
    }

    // 4. Update register data jika terhubung lewat request_ikr
    $stmtRegId = $pdo->prepare("SELECT ri.registrasi_id FROM request_ikr ri WHERE ri.queue_id = :queue_id LIMIT 1");
    $stmtRegId->execute([':queue_id' => $old['queue_id']]);
    $regId = $stmtRegId->fetchColumn();

    if ($regId) {
        $stmtReg = $pdo->prepare("
            UPDATE register
            SET name           = CASE WHEN :name != '' THEN :name ELSE name END,
                phone          = CASE WHEN :phone_contact != '' THEN :phone_contact ELSE phone END,
                paket_internet = CASE WHEN :paket_internet != '' THEN :paket_internet ELSE paket_internet END,
                perumahan      = CASE WHEN :perumahan != '' THEN :perumahan ELSE perumahan END,
                location       = CASE WHEN :location != '' THEN :location ELSE location END
            WHERE registrasi_id = :reg_id
        ");
        $stmtReg->execute([
            ':name'           => $name,
            ':phone_contact'  => $phone_contact,
            ':paket_internet' => $paket_internet,
            ':perumahan'      => $perumahan,
            ':location'       => $location,
            ':reg_id'         => $regId
        ]);
    }

    $pdo->commit();

    // Trigger FCM notification jika tim berubah
    if ($tim_id && ($tim_id !== $old['tim_id'] || $tim_id !== $old['tech_id'])) {
        try {
            sendTeamTicketNotification($pdo, $tim_id, $schedule_id);
        } catch (Exception $fcmEx) {
            error_log("FCM Error: " . $fcmEx->getMessage());
        }
    }

    echo json_encode([
        'status' => true,
        'message' => 'Tiket instalasi berhasil diperbarui'
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
