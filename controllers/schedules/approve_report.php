<?php
require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../includes/check_password.php";
require_once __DIR__ . "/../../helper/redirect.php";

$username = $_SESSION['username'] ?? null;
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Data tidak lengkap.',
        'button' => "Coba Lagi",
        'style' => "warning"
    ];
    redirect("pages/schedule");
}

$user = checkLogin($pdo, $username, $password);
if (!$user) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'Password salah.',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/schedule/");
}
try {
    $schedule_id = $_POST["scheduleId"] ?? null;
    $issue_id = $_POST["id"] ?? null;

    if (!$schedule_id || !$issue_id) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Data tidak lengkap!',
            'text' => 'Schedule ID atau Issue ID tidak ditemukan.',
            'button' => "Kembali",
            'style' => "danger"
        ];
        redirect("pages/schedule/");
    }

    $pdo->beginTransaction();

    // Ubah status issue report → Approved
    $sql = "UPDATE issues_report 
            SET status = 'Approved'
            WHERE issue_id = :issue_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':issue_id' => $issue_id]);

    // Ubah status schedule → Cancelled
    $sql = "UPDATE schedules
            SET status = 'Cancelled'
            WHERE schedule_id = :schedule_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schedule_id' => $schedule_id]);

    // Commit perubahan
    $pdo->commit();

    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Berhasil!',
        'text' => 'Issue Report berhasil disetujui.',
        'button' => "Oke",
        'style' => "success"
    ];
} catch (PDOException $e) {
    // Rollback kalau ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("DB Error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'Gagal menyimpan data, silakan coba lagi.',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}
redirect("pages/schedule/");
