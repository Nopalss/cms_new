<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . "/../../../helper/fcm_helper.php";

date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Akses tidak valid'
    ]);
    exit;
}

/*
=====================================
SANITIZE INPUT
=====================================
*/

$ticket_type     = sanitize($_POST['ticket_type'] ?? 'customer');
$netpay_id       = sanitize($_POST['netpay_id'] ?? '');
$phone_contact   = preg_replace('/[^0-9]/', '', sanitize($_POST['phone_contact'] ?? $_POST['no_tlp'] ?? ''));
$server          = sanitize($_POST['server'] ?? '');
$aduan_pelanggan = sanitize($_POST['aduan_pelanggan'] ?? '');
$verifikasi_noc  = sanitize($_POST['verifikasi_noc'] ?? '');
$tim_id          = sanitize($_POST['tim_id'] ?? '');
$tanggal_service = sanitize($_POST['tanggal_service'] ?? '');
$noc_id          = sanitize($_POST['noc_id'] ?? '');

$perumahan       = sanitize($_POST['perumahan'] ?? '');
$location        = sanitize($_POST['location'] ?? '');
$sharelock       = sanitize($_POST['sharelock'] ?? '');
$nama            = sanitize($_POST['nama'] ?? '');

/*
=====================================
VALIDASI
=====================================
*/

if ($ticket_type === 'non_customer') {
    $netpay_id = null;
    if (empty($nama)) {
        $nama = 'Infrastruktur Jaringan';
    }
    $required = compact(
        'perumahan',
        'location',
        'aduan_pelanggan',
        'verifikasi_noc',
        'tim_id',
        'tanggal_service',
        'noc_id'
    );
} else {
    $nama = null;
    $required = compact(
        'netpay_id',
        'aduan_pelanggan',
        'verifikasi_noc',
        'tim_id',
        'tanggal_service',
        'noc_id'
    );
}

foreach ($required as $field => $value) {
    if (empty($value)) {
        echo json_encode([
            'status' => false,
            'message' => "Field $field tidak boleh kosong"
        ]);
        exit;
    }
}

/*
=====================================
GENERATE ID UNIK
=====================================
*/

function generateUniqueId(PDO $pdo, string $table, string $column, string $prefix): string
{
    $id = $prefix . date('YmdHis');

    while (true) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = :id");
        $check->execute([':id' => $id]);

        if ($check->fetchColumn() == 0) {
            return $id;
        }

        $ts = substr($id, strlen($prefix));
        $dt = DateTime::createFromFormat('YmdHis', $ts);
        $dt->modify('+1 second');
        $id = $prefix . $dt->format('YmdHis');
    }
}

try {

    if (!empty($netpay_id)) {
        // pastikan netpay_id valid
        $cust = $pdo->prepare("SELECT netpay_id FROM customers WHERE netpay_id = :netpay_id LIMIT 1");
        $cust->execute([':netpay_id' => $netpay_id]);
        if (!$cust->fetch()) {
            echo json_encode([
                'status' => false,
                'message' => 'Netpay ID tidak ditemukan'
            ]);
            exit;
        }
    }

    // pastikan tim_id valid
    $timCheck = $pdo->prepare("SELECT tim_id FROM tim WHERE tim_id = :tim_id LIMIT 1");
    $timCheck->execute([':tim_id' => $tim_id]);
    if (!$timCheck->fetch()) {
        echo json_encode([
            'status' => false,
            'message' => 'Tim tidak ditemukan'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    if (!empty($netpay_id) && !empty($phone_contact)) {
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

    $queue_id    = generateUniqueId($pdo, 'queue_scheduling', 'queue_id', 'Q');
    $rm_id       = generateUniqueId($pdo, 'request_maintenance', 'rm_id', 'RM');
    $schedule_id = generateUniqueId($pdo, 'schedules', 'schedule_id', 'SC');

    // 1. queue_scheduling — ticket master
    $stmt = $pdo->prepare("
        INSERT INTO queue_scheduling (netpay_id, queue_id, type_queue, status)
        VALUES (:netpay_id, :queue_id, 'Service', 'Accepted')
    ");
    $stmt->execute([
        ':netpay_id' => $netpay_id,
        ':queue_id'  => $queue_id
    ]);

    // 2. request_maintenance — detail komplain + lokasi non-customer
    $stmt = $pdo->prepare("
        INSERT INTO request_maintenance
            (rm_id, queue_id, nama, type_issue, deskripsi_issue, server, verifikasi_noc, request_by, perumahan, location, sharelock)
        VALUES
            (:rm_id, :queue_id, :nama, :type_issue, :deskripsi_issue, :server, :verifikasi_noc, :request_by, :perumahan, :location, :sharelock)
    ");
    $stmt->execute([
        ':rm_id'           => $rm_id,
        ':queue_id'        => $queue_id,
        ':nama'            => $nama,
        ':type_issue'      => 'Service',
        ':deskripsi_issue' => $aduan_pelanggan,
        ':server'          => $server,
        ':verifikasi_noc'  => $verifikasi_noc,
        ':request_by'      => $noc_id,
        ':perumahan'       => !empty($perumahan) ? $perumahan : null,
        ':location'        => !empty($location) ? $location : null,
        ':sharelock'       => !empty($sharelock) ? $sharelock : null,
    ]);

    // 3. schedules — assignment ke tim (tech_id diisi dengan ID tim), target_status default "On Time"
    $stmt = $pdo->prepare("
        INSERT INTO schedules
            (schedule_id, tech_id, date, time, job_type, status, target_status, queue_id, noc_id)
        VALUES
            (:schedule_id, :tech_id, :date, :time, 'Service', 'Pending', 'On Time', :queue_id, :noc_id)
    ");
    $stmt->execute([
        ':schedule_id' => $schedule_id,
        ':tech_id'     => $tim_id,
        ':date'        => $tanggal_service,
        ':time'        => date('H:i:s'),
        ':queue_id'    => $queue_id,
        ':noc_id'      => $noc_id
    ]);

    $pdo->commit();

    // Trigger Firebase FCM Push Notification to team members
    try {
        sendTeamTicketNotification($pdo, $tim_id, $schedule_id);
    } catch (Exception $fcmEx) {
        error_log("[FCM] Error sending ticket notification: " . $fcmEx->getMessage());
    }

    echo json_encode([
        'status'      => true,
        'message'     => 'Tiket berhasil dibuat',
        'queue_id'    => $queue_id,
        'schedule_id' => $schedule_id
    ]);
    exit;
} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
