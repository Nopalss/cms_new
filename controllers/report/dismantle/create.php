<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . "/../../../helper/redirect.php";

date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method tidak valid.']);
    exit;
}

/*
=====================================
SANITIZE BASIC INPUT
=====================================
*/

$dismantle_id      = sanitize($_POST['dismantle_id'] ?? '');
$schedule_id      = sanitize($_POST['schedule_id'] ?? '');
$netpay_id        = sanitize($_POST['netpay_id'] ?? '');
$tanggal           = sanitize($_POST['tanggal'] ?? '');
$alasan            = sanitize($_POST['alasan'] ?? '');
$action            = sanitize($_POST['action'] ?? '');
$part_removed      = sanitize($_POST['part_removed'] ?? '');
$kondisi_perangkat = sanitize($_POST['kondisi_perangkat'] ?? '');
$keterangan        = sanitize($_POST['keterangan'] ?? '');
$jamReport = date('H:i:s');       // untuk kolom TIME
$endTime   = date('Y-m-d H:i:s'); // untuk kolom DATETIME

/*
=====================================
HANDLE PIC ARRAY
=====================================
*/

$pic = $_POST['pic'] ?? [];

if (!is_array($pic) || count($pic) === 0) {
    echo json_encode(['status' => false, 'message' => 'Minimal 1 PIC harus dipilih.']);
    exit;
}



/*
=====================================
VALIDASI WAJIB
=====================================
*/

$required = compact(
    'dismantle_id',
    'schedule_id',
    'netpay_id',
    'tanggal',
    'alasan',
    'action',
    'part_removed',
    'kondisi_perangkat',
    'keterangan'
);

foreach ($required as $field => $value) {
    if (empty($value)) {
        echo json_encode(['status' => false, 'message' => "Field $field tidak boleh kosong."]);
        exit;
    }
}

try {

    $pdo->beginTransaction();

    /*
    =====================================
    VALIDASI SCHEDULE
    =====================================
    */
    while (true) {

        $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM dismantle_reports
        WHERE dismantle_id = :dismantle_id
    ");
        $check->execute([
            ':dismantle_id' => $dismantle_id
        ]);

        if ($check->fetchColumn() == 0) {
            break;
        }

        // Ambil timestamp dari dismantle_id
        $datetime = substr($dismantle_id, 2); // 20260701101530

        $dt = DateTime::createFromFormat('YmdHis', $datetime);
        $dt->modify('+1 second');

        $dismantle_id = 'SR' . $dt->format('YmdHis');
    }

    $checkSchedule = $pdo->prepare("
        SELECT status, job_type, start_time
        FROM schedules
        WHERE schedule_id = :schedule_id
        FOR UPDATE
    ");
    $checkSchedule->execute([':schedule_id' => $schedule_id]);
    $schedule = $checkSchedule->fetch(PDO::FETCH_ASSOC);

    if (!$schedule || $schedule['job_type'] !== 'Dismantle') {
        throw new Exception("Schedule tidak valid.");
    }

    if ($schedule['status'] !== 'Actived') {
        throw new Exception("Schedule belum aktif.");
    }

    /*
    =====================================
    CEK DUPLIKAT REPORT
    =====================================
    */

    $checkReport = $pdo->prepare("
        SELECT 1 FROM dismantle_reports
        WHERE schedule_id = :schedule_id
        LIMIT 1
    ");
    $checkReport->execute([':schedule_id' => $schedule_id]);

    if ($checkReport->fetch()) {
        throw new Exception("Report sudah pernah dibuat.");
    }

    /*
    =====================================
    INSERT DISMANTLE REPORT
    =====================================
    */

    $stmt = $pdo->prepare("
        INSERT INTO dismantle_reports 
        (dismantle_id, schedule_id, netpay_id, tanggal, jam, alasan, action, part_removed, kondisi_perangkat,  keterangan)
        VALUES
        (:dismantle_id, :schedule_id, :netpay_id, :tanggal, :jam, :alasan, :action, :part_removed, :kondisi_perangkat,  :keterangan)
    ");

    $stmt->execute([
        ':dismantle_id'      => $dismantle_id,
        ':schedule_id'      => $schedule_id,
        ':netpay_id'        => $netpay_id,
        ':tanggal'           => $tanggal,
        ':jam'               => $jamReport,
        ':alasan'            => $alasan,
        ':action'            => $action,
        ':part_removed'      => $part_removed,
        ':kondisi_perangkat' => $kondisi_perangkat,
        ':keterangan'        => $keterangan,
    ]);

    /*
    =====================================
    UPDATE SCHEDULE → DONE
    =====================================
    */
    if (!empty($pic)) {
        $stmtPic = $pdo->prepare("
            INSERT INTO dismantle_report_pic (dismantle_id, tech_id)
            VALUES (:dismantle_id, :tech_id)
        ");
        foreach ($pic as $tech_id) {
            if (empty($tech_id)) continue;
            $stmtPic->execute([
                ':dismantle_id'  => $dismantle_id,
                ':tech_id' => $tech_id,
            ]);
        }
    }


    $stmt = $pdo->prepare("
        UPDATE schedules
        SET 
            end_time = :jam,
            status = 'Done'
            
        WHERE schedule_id = :schedule_id
    ");
    $stmt->execute([':jam' => $endTime, ':schedule_id' => $schedule_id]);

    /*
    =====================================
    CUSTOMER → INACTIVE
    =====================================
    */

    $stmt = $pdo->prepare("
        UPDATE customers 
        SET is_active = 'DISMANTLE'
        WHERE netpay_id = :netpay_id
    ");
    $stmt->execute([':netpay_id' => $netpay_id]);

    // Clear active ONT details in customer_details upon Dismantle
    if (!empty($netpay_id) && $netpay_id !== '-') {
        $stmtCustDetail = $pdo->prepare("
            UPDATE customer_details
            SET modem_sn = NULL, device_brand = NULL, updated_at = NOW()
            WHERE netpay_id = :netpay_id
        ");
        $stmtCustDetail->execute([':netpay_id' => $netpay_id]);
    }

    $pdo->commit();

    echo json_encode(['status' => true, 'message' => 'Dismantle Report berhasil disimpan.']);
} catch (Exception $e) {

    $pdo->rollBack();
    error_log("DISMANTLE ERROR: " . $e->getMessage());

    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}

exit;
