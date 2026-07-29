<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";

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

$srv_id      = sanitize($_POST['srv_id'] ?? '');
$schedule_id = sanitize($_POST['schedule_id'] ?? '');
$netpay_id   = sanitize($_POST['netpay_id'] ?? '');
$tanggal     = sanitize($_POST['tanggal'] ?? '');
$problem     = sanitize($_POST['problem'] ?? '');
$action      = sanitize($_POST['action'] ?? '');
$part        = sanitize($_POST['part'] ?? '');

$ont_bef     = sanitize($_POST['ont_bef'] ?? '');
$ont_aft     = sanitize($_POST['ont_aft'] ?? ''); // opsional

$red_bef     = sanitize($_POST['red_bef'] ?? '');
$red_aft     = sanitize($_POST['red_aft'] ?? '');
$keterangan  = sanitize($_POST['keterangan'] ?? '');

// Jam & waktu selesai SELALU diambil dari jam server, bukan dari input
// client (field "jam" di form cuma buat tampilan). Ini disengaja, biar
// durasi/laporan gak bisa dimanipulasi lewat jam browser teknisi.
$jamReport = date('H:i:s');       // untuk kolom TIME (service_reports.jam)
$endTime   = date('Y-m-d H:i:s'); // untuk kolom DATETIME (schedules.end_time)

/*
=====================================
PIC
=====================================
*/

$pic = $_POST['pic'] ?? [];

if (!is_array($pic) || count($pic) === 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Minimal 1 PIC harus dipilih'
    ]);
    exit;
}

/*
=====================================
VALIDASI
=====================================
*/

$is_non_customer = (empty($netpay_id) || $netpay_id === '-' || $netpay_id === 'NON_CUSTOMER');

if ($is_non_customer) {
    $netpay_id = null;
    $required = compact(
        'srv_id',
        'schedule_id',
        'tanggal',
        'problem',
        'action',
        'part'
    );
} else {
    $required = compact(
        'srv_id',
        'schedule_id',
        'netpay_id',
        'tanggal',
        'problem',
        'action',
        'part',
        'ont_bef',
        'red_bef',
        'red_aft',
        'keterangan'
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

try {

    // Cek duplikat srv_id, generate id baru kalau bentrok.
    // Prefix bisa berapa pun panjangnya ("S", "SR", dll) — yang kita
    // asumsikan tetap cuma 14 digit terakhir adalah timestamp YmdHis.
    // Prefix diambil dinamis dari sisa string di depannya, bukan
    // hardcode panjang tetap kayak sebelumnya (substr($srv_id, 2)).
    while (true) {

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM service_reports
            WHERE srv_id = :srv_id
        ");
        $check->execute([
            ':srv_id' => $srv_id
        ]);

        if ($check->fetchColumn() == 0) {
            break;
        }

        if (!preg_match('/^(\D*)(\d{14})$/', $srv_id, $m)) {
            throw new Exception('Format srv_id tidak valid: ' . $srv_id);
        }

        $prefix   = $m[1];
        $datetime = $m[2];

        $dt = DateTime::createFromFormat('YmdHis', $datetime);
        if ($dt === false) {
            throw new Exception('Gagal parsing timestamp dari srv_id: ' . $srv_id);
        }

        $dt->modify('+1 second');
        $srv_id = $prefix . $dt->format('YmdHis');
    }

    $pdo->beginTransaction();

    // insert laporan
    $stmt = $pdo->prepare("
        INSERT INTO service_reports 
        (srv_id, tanggal, jam, netpay_id, problem, action, part, ont_bef, ont_aft, red_bef, red_aft, keterangan, schedule_id)
        VALUES
        (:srv_id, :tanggal, :jam, :netpay_id, :problem, :action, :part, :ont_bef, :ont_aft, :red_bef, :red_aft, :keterangan, :schedule_id)
    ");

    $stmt->execute([
        ':srv_id'      => $srv_id,
        ':tanggal'     => $tanggal,
        ':jam'         => $jamReport,
        ':netpay_id'   => $netpay_id,
        ':problem'     => $problem,
        ':action'      => $action,
        ':part'        => $part,
        ':ont_bef'     => $ont_bef,
        ':ont_aft'     => !empty($ont_aft) ? $ont_aft : null,
        ':red_bef'     => $red_bef,
        ':red_aft'     => $red_aft,
        ':keterangan'  => $keterangan,
        ':schedule_id' => $schedule_id
    ]);

    // Insert PIC (multi teknisi). Ini SATU-SATUNYA sumber buat nampilin
    // "Teknisi" di UI (join ke tabel technician) — schedules.tech_id
    // sengaja gak diisi di sini, karena satu laporan bisa punya lebih
    // dari satu PIC dan gak ada satu "teknisi utama" yang pasti benar.
    $stmtPic = $pdo->prepare("
        INSERT INTO service_report_pic (srv_id, tech_id)
        VALUES (:srv_id, :tech_id)
    ");
    foreach ($pic as $tech_id) {
        if (empty($tech_id)) continue;
        $stmtPic->execute([
            ':srv_id'  => $srv_id,
            ':tech_id' => $tech_id,
        ]);
    }

    // Update schedule: tandai selesai + default reason "Close".
    // NOC masih bisa ubah manual reason/status ini belakangan dari UI.
    $stmt = $pdo->prepare("
        UPDATE schedules
        SET 
            end_time = :end_time,
            status   = 'Done',
            reason   = 'Close'
        WHERE schedule_id = :schedule_id
    ");

    $stmt->execute([
        ':end_time'    => $endTime,
        ':schedule_id' => $schedule_id
    ]);

    // Update modem_sn in customer_details if ONT replacement ($ont_aft) occurred
    if (!empty($netpay_id) && $netpay_id !== '-' && !empty($ont_aft)) {
        $stmtCustDetail = $pdo->prepare("
            INSERT INTO customer_details (netpay_id, modem_sn, updated_at)
            VALUES (:netpay_id, :modem_sn, NOW())
            ON DUPLICATE KEY UPDATE
                modem_sn   = VALUES(modem_sn),
                updated_at = NOW()
        ");
        $stmtCustDetail->execute([
            ':netpay_id' => $netpay_id,
            ':modem_sn'  => $ont_aft,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status'  => true,
        'message' => 'Report berhasil disimpan',
        'srv_id'  => $srv_id
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
