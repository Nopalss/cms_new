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

/*
=====================================
SANITIZE INPUT
=====================================
*/

$srv_id    =  $_POST['srv_id'] ?? 0;

$problem    = sanitize($_POST['problem'] ?? '');
$action     = sanitize($_POST['action'] ?? '');
$part       = sanitize($_POST['part'] ?? '');

$ont_bef    = sanitize($_POST['ont_bef'] ?? '');
$ont_aft    = sanitize($_POST['ont_aft'] ?? ''); // opsional

$red_bef    = sanitize($_POST['red_bef'] ?? '');
$red_aft    = sanitize($_POST['red_aft'] ?? '');
$keterangan = sanitize($_POST['keterangan'] ?? '');

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

$required = compact(
    'srv_id',
    'problem',
    'action',
    'part',
    'ont_bef', // WAJIB
    'red_bef',
    'red_aft',
    'keterangan'
);

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

    /*
    =====================================
    CEK DATA ADA
    =====================================
    */

    $check = $pdo->prepare("SELECT srv_id, netpay_id FROM service_reports WHERE srv_id = :srv_id");
    $check->execute([':srv_id' => $srv_id]);
    $srvData = $check->fetch(PDO::FETCH_ASSOC);

    if (!$srvData) {
        echo json_encode([
            'status' => false,
            'message' => 'Data tidak ditemukan'
        ]);
        exit;
    }
    $netpay_id = $srvData['netpay_id'];

    $pdo->beginTransaction();

    /*
    =====================================
    UPDATE SERVICE REPORT
    =====================================
    */

    $stmt = $pdo->prepare("
        UPDATE service_reports
        SET
            problem    = :problem,
            action     = :action,
            part       = :part,
            ont_bef    = :ont_bef,
            ont_aft    = :ont_aft,
            red_bef    = :red_bef,
            red_aft    = :red_aft,
            keterangan = :keterangan
        WHERE srv_id = :srv_id
    ");

    $stmt->execute([
        ':srv_id'    => $srv_id,
        ':problem'    => $problem,
        ':action'     => $action,
        ':part'       => $part,
        ':ont_bef'    => $ont_bef,
        ':ont_aft'    => !empty($ont_aft) ? $ont_aft : null, // opsional
        ':red_bef'    => $red_bef,
        ':red_aft'    => $red_aft,
        ':keterangan' => $keterangan
    ]);


    // ganti daftar PIC: hapus yang lama, insert ulang yang baru
    $del = $pdo->prepare("DELETE FROM service_report_pic WHERE srv_id = :srv_id");
    $del->execute([':srv_id' => $srv_id]);

    if (!empty($pic)) {
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
    }

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
        'status' => true,
        'message' => 'Laporan berhasil diupdate'
    ]);
    exit;
} catch (Exception $e) {

    $pdo->rollBack();
    error_log("UPDATE SERVICE REPORT: " . $e->getMessage());

    echo json_encode([
        'status' => false,
        'message' => 'Terjadi kesalahan server, ' .  $e->getMessage()
    ]);
    exit;
}
