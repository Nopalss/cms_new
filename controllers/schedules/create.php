<?php
require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../helper/sanitize.php";
require_once __DIR__ . "/../../helper/redirect.php";

if (isset($_POST['submit'])) {
    // Ambil & sanitasi data POST
    $schedule_id   = isset($_POST['schedule_id']) ? sanitize($_POST['schedule_id']) : null;
    $queue_id   = isset($_POST['queue_id']) ? sanitize($_POST['queue_id']) : null;
    $tech_id   = isset($_POST['tech_id']) ? sanitize($_POST['tech_id']) : null;
    $date = isset($_POST['date']) ? sanitize($_POST['date']) : null;
    $time      = isset($_POST['time']) ? sanitize($_POST['time']) : null;
    $job_type  = isset($_POST['job_type']) ? sanitize($_POST['job_type']) : null;
    $catatan  = isset($_POST['catatan']) ? sanitize($_POST['catatan']) : null;

    // Pastikan semua data terisi
    if (!$schedule_id  || !$queue_id  || !$tech_id || !$date || !$time || !$job_type || !$catatan) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Schedule gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/queue/");
    }
    $today = date('Y-m-d');

    if ($date < $today) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Tanggal Tidak Valid',
            'text' => 'Tanggal schedule tidak boleh kurang dari hari ini.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];

        redirect("pages/queue/");
        exit;
    }

    try {
        $pdo->beginTransaction();
        // Query insert dengan prepared statement
        $sql = "INSERT INTO schedules (schedule_id ,tech_id, `date`, `time`, job_type, queue_id, catatan) 
                VALUES (:schedule_id, :tech_id, :date, :time, :job_type, :queue_id, :catatan)";
        $stmt = $pdo->prepare($sql);
        $scheduleSuccess = $stmt->execute([
            ':schedule_id' => $schedule_id,
            ':tech_id'     => $tech_id,
            ':date'        => $date,
            ':time'         => $time,
            ':job_type'    => $job_type,
            ':queue_id'     => $queue_id,
            ':catatan'     => $catatan,
        ]);

        $sql = "UPDATE queue_scheduling 
                        SET status = 'Accepted' 
                    WHERE queue_id = :queue_id";
        $stmt = $pdo->prepare($sql);
        $queueSuccess = $stmt->execute([":queue_id" => $queue_id]);
        $pdo->commit();

        // Trigger Notifikasi Push FCM ke Teknisi Tim
        try {
            require_once __DIR__ . "/../../helper/fcm_helper.php";
            sendTeamTicketNotification($pdo, $tech_id, $schedule_id);
        } catch (Exception $fcmEx) {
            error_log("[FCM] Error sending schedule notification: " . $fcmEx->getMessage());
        }

        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Selamat!',
            'text' => 'Data schedule berhasil disimpan',
            'button' => "Oke",
            'style' => "success"
        ];
        redirect("pages/schedule/");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("DB Error: " . $e->getMessage()); // simpan ke error log server
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops!',
            'text' => 'Gagal menyimpan data, silakan coba lagi.',
            // 'text' => $e->getMessage(),
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
    }
} else {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'Gagal mengakses halaman!',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}
redirect("pages/queue/");
