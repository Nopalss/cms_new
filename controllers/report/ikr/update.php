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

$ikr_id      = sanitize($_POST['ikr_id'] ?? '');
$netpay_id   = sanitize($_POST['netpay_id'] ?? '');
$schedule_id = sanitize($_POST['schedule_id'] ?? '');

$alamat      = sanitize($_POST['alamat'] ?? '');
$rt          = sanitize($_POST['rt'] ?? '');
$rw          = sanitize($_POST['rw'] ?? '');

$desa        = sanitize($_POST['desa'] ?? '');
$kec         = sanitize($_POST['kec'] ?? '');
$kab         = sanitize($_POST['kab'] ?? '');

$type_ont    = sanitize($_POST['type_ont'] ?? '');
$sn          = sanitize($_POST['sn'] ?? '');
$redaman     = sanitize($_POST['redaman'] ?? '');

$odp_no      = sanitize($_POST['odp_no'] ?? '');
$odc_no      = sanitize($_POST['odc_no'] ?? '');
$jc_no       = sanitize($_POST['jc_no'] ?? '');
$odp         = sanitize($_POST['odp'] ?? '');
$odc         = sanitize($_POST['odc'] ?? '');
$enclosure   = sanitize($_POST['enclosure'] ?? '');

$mac_sebelum = sanitize($_POST['mac_sebelum'] ?? ''); // opsional
$mac_sesudah = sanitize($_POST['mac_sesudah'] ?? ''); // opsional


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
    'ikr_id',
    'netpay_id',
    'schedule_id',
    'alamat',
    'rt',
    'rw',
    'desa',
    'kec',
    'kab',
    'type_ont',
    'sn',
    'redaman',
    'odp_no',
    'odc_no',
    'jc_no',
    'odp',
    'odc',
    'enclosure'
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

    // pastikan laporan yang mau diupdate memang ada
    $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM ikr_report
        WHERE ikr_id = :ikr_id
    ");
    $check->execute([
        ':ikr_id' => $ikr_id
    ]);

    if ($check->fetchColumn() == 0) {
        echo json_encode([
            'status' => false,
            'message' => 'Laporan IKR tidak ditemukan'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // update
    $stmt = $pdo->prepare("
        UPDATE ikr_report
        SET
            netpay_id   = :netpay_id,
            schedule_id = :schedule_id,
            alamat      = :alamat,
            rt          = :rt,
            rw          = :rw,
            desa        = :desa,
            kec         = :kec,
            kab         = :kab,
            type_ont    = :type_ont,
            sn          = :sn,
            redaman     = :redaman,
            odp_no      = :odp_no,
            odc_no      = :odc_no,
            jc_no       = :jc_no,
            odp         = :odp,
            odc         = :odc,
            enclosure   = :enclosure,
            mac_sebelum = :mac_sebelum,
            mac_sesudah = :mac_sesudah
        WHERE ikr_id = :ikr_id
    ");

    $stmt->execute([
        ':netpay_id'   => $netpay_id,
        ':schedule_id' => $schedule_id,
        ':alamat'      => $alamat,
        ':rt'          => $rt,
        ':rw'          => $rw,
        ':desa'        => $desa,
        ':kec'         => $kec,
        ':kab'         => $kab,
        ':type_ont'    => $type_ont,
        ':sn'          => $sn,
        ':redaman'     => $redaman,
        ':odp_no'      => $odp_no,
        ':odc_no'      => $odc_no,
        ':jc_no'       => $jc_no,
        ':odp'         => $odp,
        ':odc'         => $odc,
        ':enclosure'   => $enclosure,
        ':mac_sebelum' => !empty($mac_sebelum) ? $mac_sebelum : null,
        ':mac_sesudah' => !empty($mac_sesudah) ? $mac_sesudah : null,
        ':ikr_id'      => $ikr_id,
    ]);

    // ganti daftar PIC: hapus yang lama, insert ulang yang baru
    $del = $pdo->prepare("DELETE FROM ikr_report_pic WHERE ikr_id = :ikr_id");
    $del->execute([':ikr_id' => $ikr_id]);

    if (!empty($pic)) {
        $stmtPic = $pdo->prepare("
            INSERT INTO ikr_report_pic (ikr_id, tech_id)
            VALUES (:ikr_id, :tech_id)
        ");
        foreach ($pic as $tech_id) {
            if (empty($tech_id)) continue;
            $stmtPic->execute([
                ':ikr_id'  => $ikr_id,
                ':tech_id' => $tech_id,
            ]);
        }
    }

    // ==========================
    // UPSERT CUSTOMER_DETAILS (ONT & SN)
    // ==========================
    if (!empty($netpay_id) && $netpay_id !== '-') {
        $stmtCustDetail = $pdo->prepare("
            INSERT INTO customer_details (netpay_id, device_brand, modem_sn, updated_at)
            VALUES (:netpay_id, :device_brand, :modem_sn, NOW())
            ON DUPLICATE KEY UPDATE
                device_brand = VALUES(device_brand),
                modem_sn     = VALUES(modem_sn),
                updated_at   = NOW()
        ");
        $stmtCustDetail->execute([
            ':netpay_id'    => $netpay_id,
            ':device_brand' => $type_ont,
            ':modem_sn'     => $sn,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Perubahan laporan berhasil disimpan'
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
