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

$action = sanitize($payload['action'] ?? '');
$issue_id = sanitize($payload['issue_id'] ?? '');
$schedule_id = sanitize($payload['schedule_id'] ?? '');

if (!$action || !$issue_id || !$schedule_id) {
    echo json_encode([
        'status' => false,
        'message' => 'Parameter tidak lengkap'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // 1. Approve issue report
        $stmtIssue = $pdo->prepare("
            UPDATE issues_report
            SET status = 'Approved'
            WHERE issue_id = :issue_id
        ");
        $stmtIssue->execute([':issue_id' => $issue_id]);

        // 2. Set ticket status to Cancelled
        $stmtSch = $pdo->prepare("
            UPDATE schedules
            SET status = 'Cancelled',
                reason = 'Kendala Lapangan Disetujui (Tiket Dibatalkan)'
            WHERE schedule_id = :schedule_id
        ");
        $stmtSch->execute([':schedule_id' => $schedule_id]);

        $message = 'Laporan kendala disetujui. Tiket instalasi dibatalkan.';

    } elseif ($action === 'reschedule') {
        $new_date = sanitize($payload['new_date'] ?? '');
        $reason   = sanitize($payload['reason'] ?? 'Reschedule Kendala Lapangan');

        if (!$new_date) {
            throw new Exception('Tanggal reschedule baru wajib dipilih');
        }

        // 1. Mark issue report as Approved/Rescheduled
        $stmtIssue = $pdo->prepare("
            UPDATE issues_report
            SET status = 'Approved'
            WHERE issue_id = :issue_id
        ");
        $stmtIssue->execute([':issue_id' => $issue_id]);

        // 2. Update schedules to Rescheduled with new date
        $stmtSch = $pdo->prepare("
            UPDATE schedules
            SET status = 'Rescheduled',
                date = :new_date,
                reason = :reason
            WHERE schedule_id = :schedule_id
        ");
        $stmtSch->execute([
            ':new_date'    => $new_date,
            ':reason'      => $reason,
            ':schedule_id' => $schedule_id,
        ]);

        $message = 'Kendala disetujui dan jadwal tiket berhasil diperbarui ke tanggal ' . $new_date;

    } elseif ($action === 'reject') {
        $stmtIssue = $pdo->prepare("
            UPDATE issues_report
            SET status = 'Rejected'
            WHERE issue_id = :issue_id
        ");
        $stmtIssue->execute([':issue_id' => $issue_id]);

        $message = 'Laporan kendala ditolak.';
    } else {
        throw new Exception('Aksi tidak dikenali');
    }

    $pdo->commit();

    echo json_encode([
        'status' => true,
        'message' => $message
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
