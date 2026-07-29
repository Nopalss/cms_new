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

$action      = sanitize($_POST['action'] ?? '');
$issue_id    = sanitize($_POST['issue_id'] ?? '');
$schedule_id = sanitize($_POST['schedule_id'] ?? '');
$new_date    = sanitize($_POST['new_date'] ?? '');
$reason      = sanitize($_POST['reason'] ?? '');

if (empty($issue_id) || empty($schedule_id) || !in_array($action, ['approve', 'reject', 'reschedule'], true)) {
    echo json_encode([
        'status' => false,
        'message' => 'Data permintaan tidak valid'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // Set issues_report -> Approved
        $stmt = $pdo->prepare("UPDATE issues_report SET status = 'Approved' WHERE issue_id = :issue_id");
        $stmt->execute([':issue_id' => $issue_id]);

        // Set schedules -> Cancelled
        $stmtSched = $pdo->prepare("UPDATE schedules SET status = 'Cancelled' WHERE schedule_id = :schedule_id");
        $stmtSched->execute([':schedule_id' => $schedule_id]);

        $msg = 'Laporan kendala disetujui. Tiket dibatalkan.';
    } elseif ($action === 'reschedule') {
        if (empty($new_date)) {
            throw new Exception('Tanggal reschedule baru harus diisi');
        }

        // Set issues_report -> Approved
        $stmt = $pdo->prepare("UPDATE issues_report SET status = 'Approved' WHERE issue_id = :issue_id");
        $stmt->execute([':issue_id' => $issue_id]);

        // Set schedules -> Rescheduled
        $stmtSched = $pdo->prepare("
            UPDATE schedules
            SET status = 'Rescheduled', date = :new_date, reason = :reason
            WHERE schedule_id = :schedule_id
        ");
        $stmtSched->execute([
            ':new_date'    => $new_date,
            ':reason'      => $reason,
            ':schedule_id' => $schedule_id,
        ]);

        $msg = 'Laporan kendala disetujui. Tiket berhasil di-reschedule ke tanggal ' . $new_date . '.';
    } else {
        // Set issues_report -> Rejected
        $stmt = $pdo->prepare("UPDATE issues_report SET status = 'Rejected' WHERE issue_id = :issue_id");
        $stmt->execute([':issue_id' => $issue_id]);

        $msg = 'Laporan kendala ditolak. Teknisi dapat melanjutkan pekerjaan.';
    }

    $pdo->commit();

    echo json_encode([
        'status'  => true,
        'message' => $msg
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
