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

$schedule_id = sanitize($_POST['schedule_id'] ?? '');

if (empty($schedule_id)) {
    echo json_encode([
        'status' => false,
        'message' => 'schedule_id tidak boleh kosong'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil queue_id dari schedules
    $stmt = $pdo->prepare("SELECT queue_id FROM schedules WHERE schedule_id = :schedule_id LIMIT 1");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $sched = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sched) {
        $pdo->rollBack();
        echo json_encode([
            'status' => false,
            'message' => 'Tiket tidak ditemukan'
        ]);
        exit;
    }

    $queue_id = $sched['queue_id'];

    // 2. Bersihkan service_reports & service_report_pic jika ada
    $stmtSrv = $pdo->prepare("SELECT srv_id FROM service_reports WHERE schedule_id = :schedule_id");
    $stmtSrv->execute([':schedule_id' => $schedule_id]);
    $reports = $stmtSrv->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reports as $r) {
        $stmtPic = $pdo->prepare("DELETE FROM service_report_pic WHERE srv_id = :srv_id");
        $stmtPic->execute([':srv_id' => $r['srv_id']]);
    }

    $stmtDelSrv = $pdo->prepare("DELETE FROM service_reports WHERE schedule_id = :schedule_id");
    $stmtDelSrv->execute([':schedule_id' => $schedule_id]);

    // 3. Hapus schedules
    $stmtDelSched = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = :schedule_id");
    $stmtDelSched->execute([':schedule_id' => $schedule_id]);

    // 4. Hapus request_maintenance
    if (!empty($queue_id)) {
        $stmtDelRM = $pdo->prepare("DELETE FROM request_maintenance WHERE queue_id = :queue_id");
        $stmtDelRM->execute([':queue_id' => $queue_id]);

        // 5. Hapus queue_scheduling
        $stmtDelQ = $pdo->prepare("DELETE FROM queue_scheduling WHERE queue_id = :queue_id");
        $stmtDelQ->execute([':queue_id' => $queue_id]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Tiket berhasil dihapus'
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
