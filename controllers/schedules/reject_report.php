<?php
require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../helper/redirect.php";

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

    // Ubah status issue report → Rejected
    $sql = "UPDATE issues_report 
            SET status = 'Rejected'
            WHERE issue_id = :issue_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':issue_id' => $issue_id]);

    // Commit perubahan
    $pdo->commit();

    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Berhasil!',
        'text' => 'Issue Report berhasil ditolak.',
        'button' => "Oke",
        'style' => "success"
    ];
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("DB Error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'Gagal memproses data, silakan coba lagi.',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}
redirect("pages/schedule/");
