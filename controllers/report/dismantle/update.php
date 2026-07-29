<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status' => false,
        'message' => 'Method tidak valid.'
    ]);
    exit;
}

$dismantle_id      = sanitize($_POST['dismantle_id'] ?? '');
$alasan             = sanitize($_POST['alasan'] ?? '');
$action             = sanitize($_POST['action'] ?? '');
$part_removed       = sanitize($_POST['part_removed'] ?? '');
$kondisi_perangkat  = sanitize($_POST['kondisi_perangkat'] ?? '');
$keterangan         = sanitize($_POST['keterangan'] ?? '');
$pic                = $_POST['pic'] ?? [];

/*
=====================================
VALIDASI WAJIB
=====================================
*/

$requiredFields = compact(
    'dismantle_id',
    'alasan',
    'action',
    'part_removed',
    'kondisi_perangkat',
    'keterangan'
);

foreach ($requiredFields as $field => $value) {

    if (empty($value)) {

        echo json_encode([
            'status' => false,
            'message' => "Field {$field} tidak boleh kosong."
        ]);
        exit;
    }
}

/*
=====================================
VALIDASI PIC
=====================================
*/

if (!is_array($pic) || count($pic) === 0) {

    echo json_encode([
        'status' => false,
        'message' => 'Minimal 1 PIC harus dipilih.'
    ]);
    exit;
}



try {

    $pdo->beginTransaction();

    /*
    =====================================
    CEK REPORT EXIST
    =====================================
    */

    $check = $pdo->prepare("
        SELECT dr.schedule_id, s.status
        FROM dismantle_reports dr
        LEFT JOIN schedules s
            ON dr.schedule_id = s.schedule_id
        WHERE dr.dismantle_id = :id
        FOR UPDATE
    ");

    $check->execute([
        ':id' => $dismantle_id
    ]);

    $report = $check->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        throw new Exception("Data dismantle tidak ditemukan.");
    }

    if ($report['status'] === 'Cancelled') {
        throw new Exception("Schedule sudah dibatalkan, tidak bisa diubah.");
    }

    /*
    =====================================
    UPDATE REPORT
    =====================================
    */

    $stmt = $pdo->prepare("
        UPDATE dismantle_reports
        SET
            alasan             = :alasan,
            action             = :action,
            part_removed       = :part_removed,
            kondisi_perangkat  = :kondisi_perangkat,
            keterangan         = :keterangan,
            updated_at         = NOW()
        WHERE dismantle_id = :dismantle_id
    ");

    $stmt->execute([
        ':dismantle_id'     => $dismantle_id,
        ':alasan'            => $alasan,
        ':action'            => $action,
        ':part_removed'      => $part_removed,
        ':kondisi_perangkat' => $kondisi_perangkat,
        ':keterangan'        => $keterangan
    ]);

    // ganti daftar PIC: hapus yang lama, insert ulang yang baru
    $del = $pdo->prepare("DELETE FROM dismantle_report_pic WHERE dismantle_id = :dismantle_id");
    $del->execute([':dismantle_id' => $dismantle_id]);

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

    // Clear active ONT details in customer_details upon Dismantle update
    if (!empty($netpay_id) && $netpay_id !== '-') {
        $stmtCustDetail = $pdo->prepare("
            UPDATE customer_details
            SET modem_sn = NULL, device_brand = NULL, updated_at = NOW()
            WHERE netpay_id = :netpay_id
        ");
        $stmtCustDetail->execute([':netpay_id' => $netpay_id]);
    }
    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Laporan berhasil diperbarui.'
    ]);
    exit;
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("UPDATE DISMANTLE ERROR : " . $e->getMessage());

    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
