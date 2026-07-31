<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/redirect.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . '/../../../helper/validatePhone.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert'] = [
        'icon'   => 'warning',
        'title'  => 'Oops!',
        'text'   => 'Akses tidak valid.',
        'button' => "Oke",
        'style'  => "warning"
    ];
    redirect("pages/ikr/");
    exit;
}

// ==========================
// AMBIL & SANITIZE INPUT
// ==========================
$ikr_id      = isset($_POST['ikr_id'])      ? sanitize($_POST['ikr_id'])      : null;
$netpay_id   = isset($_POST['netpay_id'])   ? sanitize($_POST['netpay_id'])   : null;

// ── FIX: form ngirim 'schedule_key', bukan 'schedule_id' ──
$schedule_id = isset($_POST['schedule_id']) ? sanitize($_POST['schedule_id']) : null;

$alamat      = isset($_POST['alamat'])      ? sanitize($_POST['alamat'])      : null;
$rt          = isset($_POST['rt'])          ? sanitize($_POST['rt'])          : null;
$rw          = isset($_POST['rw'])          ? sanitize($_POST['rw'])          : null;

$desa        = isset($_POST['desa'])        ? sanitize($_POST['desa'])        : null;
$kec         = isset($_POST['kec'])         ? sanitize($_POST['kec'])         : null;
$kab         = isset($_POST['kab'])         ? sanitize($_POST['kab'])         : null;

$type_ont    = isset($_POST['type_ont'])    ? sanitize($_POST['type_ont'])    : null;
$sn          = isset($_POST['sn'])          ? sanitize($_POST['sn'])          : null;
$redaman     = isset($_POST['redaman'])     ? sanitize($_POST['redaman'])     : null;

$odp_no      = isset($_POST['odp_no'])      ? sanitize($_POST['odp_no'])      : null;
$odc_no      = isset($_POST['odc_no'])      ? sanitize($_POST['odc_no'])      : null;
$jc_no       = isset($_POST['jc_no'])       ? sanitize($_POST['jc_no'])       : null;

$odp         = isset($_POST['odp'])         ? sanitize($_POST['odp'])         : null;
$odc         = isset($_POST['odc'])         ? sanitize($_POST['odc'])         : null;
$enclosure   = isset($_POST['enclosure'])   ? sanitize($_POST['enclosure'])   : null;

$mac_sebelum = isset($_POST['mac_sebelum']) ? sanitize($_POST['mac_sebelum']) : null;
$mac_sesudah = isset($_POST['mac_sesudah']) ? sanitize($_POST['mac_sesudah']) : null;

// pic[] berupa array, jangan pakai sanitize langsung
$pic = isset($_POST['pic']) ? array_map('sanitize', $_POST['pic']) : [];

if (!is_array($pic) || count($pic) === 0) {
    echo json_encode([
        'status' => false,
        'message' => 'Minimal 1 PIC harus dipilih'
    ]);
    exit;
}

// ==========================
// VALIDASI
// ==========================
$required = compact(
    'ikr_id',
    'schedule_id',
    'netpay_id',
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
    'enclosure',
    'mac_sebelum',
    'mac_sesudah'
);

foreach ($required as $field => $value) {
    if ($value === null || trim($value) === '') {
        echo json_encode([
            'status'  => false,
            'message' => "Field $field tidak boleh kosong"
        ]);
        exit;
    }
}

try {
    // ── Check if IKR Report already exists for this schedule_id ──
    $stmtCheckExist = $pdo->prepare("SELECT ikr_id FROM ikr_report WHERE schedule_id = :schedule_id LIMIT 1");
    $stmtCheckExist->execute([':schedule_id' => $schedule_id]);
    $existingIkr = $stmtCheckExist->fetchColumn();
    if ($existingIkr) {
        echo json_encode([
            'status'  => false,
            'message' => "Laporan IKR untuk tiket/jadwal ini sudah pernah dibuat (ID: $existingIkr)."
        ]);
        exit;
    }

    // ── Guarantee UNIQUE ikr_id ──
    if (empty($ikr_id)) {
        $ikr_id = "SI" . date("YmdHis");
    }
    $stmtCheckIkr = $pdo->prepare("SELECT COUNT(*) FROM ikr_report WHERE ikr_id = :ikr_id");
    $stmtCheckIkr->execute([':ikr_id' => $ikr_id]);
    if ($stmtCheckIkr->fetchColumn() > 0) {
        $ikr_id = "SI" . date("YmdHis") . sprintf("%02d", rand(10, 99));
    }

    $pdo->beginTransaction();

    // ==========================
    // INSERT IKR
    // ==========================
    $stmt = $pdo->prepare("
        INSERT INTO ikr_report (
            ikr_id, netpay_id,  alamat, rt, rw,
            desa, kec, kab, sn, 
            type_ont, redaman, odp_no, odc_no, jc_no,
            mac_sebelum, mac_sesudah, odp, odc, enclosure, schedule_id
        ) VALUES (
            :ikr_id, :netpay_id,  :alamat, :rt, :rw,
            :desa, :kec, :kab, :sn, 
            :type_ont, :redaman, :odp_no, :odc_no, :jc_no,
            :mac_sebelum, :mac_sesudah, :odp, :odc, :enclosure, :schedule_id
        )
    ");

    $stmt->execute([
        ':ikr_id'      => $ikr_id,
        ':netpay_id'   => $netpay_id,
        ':alamat'      => $alamat,
        ':rt'          => $rt,
        ':rw'          => $rw,
        ':desa'        => $desa,
        ':kec'         => $kec,
        ':kab'         => $kab,
        ':sn'          => $sn,
        ':type_ont'    => $type_ont,
        ':redaman'     => $redaman,
        ':odp_no'      => $odp_no,
        ':odc_no'      => $odc_no,
        ':jc_no'       => $jc_no,
        ':mac_sebelum' => $mac_sebelum,
        ':mac_sesudah' => $mac_sesudah,
        ':odp'         => $odp,
        ':odc'         => $odc,
        ':enclosure'   => $enclosure,
        ':schedule_id' => $schedule_id,
    ]);

    // ==========================
    // INSERT IKR_REPORT_PIC
    // Loop tiap tech_id yang dipilih dari pic[]
    // ==========================
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
    // UPDATE CUSTOMER → ACTIVE
    // ==========================
    $stmt = $pdo->prepare("
        UPDATE customers
        SET is_active = 'ACTIVE'
        WHERE netpay_id = :netpay_id
    ");
    $stmt->execute([':netpay_id' => $netpay_id]);

    // ==========================
    // UPSERT CUSTOMER_DETAILS (ONT & SN)
    // ==========================
    if (!empty($netpay_id) && $netpay_id !== '-') {
        $stmtCustDetail = $pdo->prepare("
            INSERT INTO customer_details (netpay_id, device_brand, modem_sn, installed_at)
            VALUES (:netpay_id, :device_brand, :modem_sn, NOW())
            ON DUPLICATE KEY UPDATE
                device_brand = VALUES(device_brand),
                modem_sn     = VALUES(modem_sn),
                installed_at = NOW()
        ");
        $stmtCustDetail->execute([
            ':netpay_id'    => $netpay_id,
            ':device_brand' => $type_ont,
            ':modem_sn'     => $sn,
        ]);
    }

    // ==========================
    // UPDATE SCHEDULE → DONE
    // FIX: hapus trailing comma sebelum WHERE
    // ==========================
    $stmt = $pdo->prepare("
        UPDATE schedules
        SET
            end_time  = NOW(),
            status    = 'Done'
        WHERE schedule_id = :schedule_id
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);

    $pdo->commit();

    // ── Karena sekarang dipanggil via fetch() dari JS,
    //    return JSON bukan redirect session ──
    echo json_encode(['status' => true, 'message' => 'Report Berhasil disimpan']);
    exit;
} catch (Exception $e) {

    $pdo->rollBack();
    error_log("IKR ERROR: " . $e->getMessage());

    echo json_encode(['status' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    exit;
}
