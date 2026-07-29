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

$rawInput = file_get_contents('php://input');
$jsonPayload = json_decode($rawInput, true) ?? [];
$payload = array_merge($_POST, $jsonPayload);

$schedule_id = sanitize($payload['schedule_id'] ?? '');

if (!$schedule_id) {
    echo json_encode([
        'status' => false,
        'message' => 'ID Schedule wajib diisi'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT queue_id FROM schedules WHERE schedule_id = :schedule_id");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $sch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sch) {
        throw new Exception('Tiket tidak ditemukan');
    }

    $queue_id = $sch['queue_id'];

    // Delete schedules
    $pdo->prepare("DELETE FROM schedules WHERE schedule_id = :schedule_id")->execute([':schedule_id' => $schedule_id]);

    // Delete request_ikr & queue_scheduling if needed
    if ($queue_id) {
        $pdo->prepare("DELETE FROM request_ikr WHERE queue_id = :queue_id")->execute([':queue_id' => $queue_id]);
        $pdo->prepare("DELETE FROM queue_scheduling WHERE queue_id = :queue_id")->execute([':queue_id' => $queue_id]);
    }

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Tiket instalasi berhasil dihapus'
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
