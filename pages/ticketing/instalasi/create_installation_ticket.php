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

$netpay_id      = sanitize($payload['netpay_id'] ?? '');
$name           = sanitize($payload['name'] ?? '');
$phone_contact  = sanitize($payload['phone_contact'] ?? '');
$perumahan      = sanitize($payload['perumahan'] ?? '');
$location       = sanitize($payload['location'] ?? '');
$paket_internet = sanitize($payload['paket_internet'] ?? '');
$catatan        = sanitize($payload['catatan'] ?? '');
$tim_id         = sanitize($payload['tim_id'] ?? '');
$tanggal_service= sanitize($payload['tanggal_service'] ?? '');
$noc_id         = sanitize($payload['noc_id'] ?? '');

if (!$netpay_id || !$name || !$phone_contact || !$perumahan || !$location || !$tim_id || !$tanggal_service || !$noc_id) {
    echo json_encode([
        'status' => false,
        'message' => 'Semua field wajib (Netpay ID, Nama, No Tlp, Perumahan, Alamat, Tim, Tgl Service, NOC) harus diisi'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Simpan ke tabel register (Pendaftaran Baru)
    $registrasi_id = "REG" . date("YmdHis") . rand(10, 99);
    $stmtReg = $pdo->prepare("
        INSERT INTO register (registrasi_id, name, perumahan, location, phone, paket_internet, date, time, is_verified)
        VALUES (:registrasi_id, :name, :perumahan, :location, :phone, :paket_internet, :date, '08:00:00', 'Verified')
    ");
    $stmtReg->execute([
        ':registrasi_id' => $registrasi_id,
        ':name'          => $name,
        ':perumahan'     => $perumahan,
        ':location'      => $location,
        ':phone'         => $phone_contact,
        ':paket_internet'=> $paket_internet,
        ':date'          => $tanggal_service,
    ]);

    // 2. Simpan / update data customer di tabel customers
    $stmtCust = $pdo->prepare("
        INSERT INTO customers (netpay_id, name, phone, phone_contact, perumahan, location, paket_internet, is_active)
        VALUES (:netpay_id, :name, :phone, :phone_contact, :perumahan, :location, :paket_internet, 'PENDING')
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            phone = VALUES(phone),
            phone_contact = VALUES(phone_contact),
            perumahan = VALUES(perumahan),
            location = VALUES(location),
            paket_internet = VALUES(paket_internet)
    ");
    $stmtCust->execute([
        ':netpay_id'     => $netpay_id,
        ':name'          => $name,
        ':phone'         => $phone_contact,
        ':phone_contact' => $phone_contact,
        ':perumahan'     => $perumahan,
        ':location'      => $location,
        ':paket_internet'=> $paket_internet,
    ]);

    // 3. Buat queue_scheduling
    $queue_id = "Q" . date("YmdHis") . rand(10, 99);
    $stmtQueue = $pdo->prepare("
        INSERT INTO queue_scheduling (queue_id, type_queue, netpay_id)
        VALUES (:queue_id, 'Install', :netpay_id)
    ");
    $stmtQueue->execute([
        ':queue_id'  => $queue_id,
        ':netpay_id' => $netpay_id,
    ]);

    // 4. Buat request_ikr
    $rikr_id = "RIKR" . date("YmdHis") . rand(10, 99);
    $stmtIkr = $pdo->prepare("
        INSERT INTO request_ikr (rikr_id, queue_id, registrasi_id, catatan)
        VALUES (:rikr_id, :queue_id, :registrasi_id, :catatan)
    ");
    $stmtIkr->execute([
        ':rikr_id'       => $rikr_id,
        ':queue_id'      => $queue_id,
        ':registrasi_id' => $registrasi_id,
        ':catatan'       => $catatan,
    ]);

    // 5. Buat tiket di schedules
    $schedule_id = "SCH" . date("YmdHis") . rand(100, 999);
    $stmtSch = $pdo->prepare("
        INSERT INTO schedules (
            schedule_id, queue_id, tech_id, tim_id, noc_id,
            job_type, date, time, status, target_status, catatan
        ) VALUES (
            :schedule_id, :queue_id, :tech_id, :tim_id, :noc_id,
            'Instalasi', :date, '08:00:00', 'Pending', 'On Time', :catatan
        )
    ");
    $stmtSch->execute([
        ':schedule_id' => $schedule_id,
        ':queue_id'    => $queue_id,
        ':tech_id'     => $tim_id,
        ':tim_id'      => $tim_id,
        ':noc_id'      => $noc_id,
        ':date'        => $tanggal_service,
        ':catatan'     => $catatan,
    ]);

    $pdo->commit();

    // Trigger Notifikasi Push FCM ke Teknisi Tim
    try {
        sendTeamTicketNotification($pdo, $tim_id, $schedule_id);
    } catch (Exception $fcmEx) {
        error_log("FCM Error: " . $fcmEx->getMessage());
    }

    echo json_encode([
        'status' => true,
        'message' => 'Registrasi & Tiket Instalasi baru berhasil disimpan',
        'schedule_id' => $schedule_id
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
