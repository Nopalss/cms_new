<?php

require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../helper/redirect.php";


$id       = $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Data tidak lengkap.',
        'button' => "Coba Lagi",
        'style' => "warning"
    ];
    redirect("pages/schedule/detail.php?id=$id");
    exit;
}

try {

    // ambil schedule dulu (anti double start)
    $check = $pdo->prepare("
        SELECT status, start_time, job_type 
        FROM schedules 
        WHERE schedule_id = ?
    ");
    $check->execute([$id]);
    $schedule = $check->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        throw new Exception("Schedule tidak ditemukan");
    }
    $job = $schedule['job_type'];


    // update status + start_time
    $stmt = $pdo->prepare("
        UPDATE schedules 
        SET 
            status = 'Actived',
            start_time = NOW()
        WHERE schedule_id = :schedule_id
    ");

    $stmt->execute([
        ':schedule_id' => $id
    ]);

    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Berhasil',
        'text' => 'Pekerjaan berhasil dimulai.',
        'button' => 'Oke',
        'style' => 'success'
    ];

    redirect("pages/schedule/detail.php?id=$id&job_type=$job");
    exit;
} catch (Exception $e) {

    error_log("START WORK ERROR: " . $e->getMessage());

    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'Gagal memulai pekerjaan.',
        'button' => 'Coba Lagi',
        'style' => 'danger'
    ];

    redirect("pages/schedule/detail.php?id=$id&job_type=$job");
    exit;
}
