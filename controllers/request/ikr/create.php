<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . '/../../../helper/sanitize.php';
require_once __DIR__ . '/../../../helper/validatePhone.php';
require_once __DIR__ . '/../../../helper/redirect.php';
if (isset($_POST['submit'])) {

    // Ambil & sanitasi data POST
    $registrasi_id   = isset($_POST['registrasi_id']) ? sanitize($_POST['registrasi_id']) : null;
    $name   = isset($_POST['name']) ? sanitize($_POST['name']) : null;
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : null;
    $paket_internet    = isset($_POST['paket_internet']) ? sanitize($_POST['paket_internet']) : null;
    $location  = isset($_POST['location']) ? sanitize($_POST['location']) : null;
    $rikr_id    = isset($_POST['rikr_id']) ? sanitize($_POST['rikr_id']) : null;
    $netpay_id    = isset($_POST['netpay_id']) ? sanitize($_POST['netpay_id']) : null;
    $time  = isset($_POST['time_pemasangan']) ? sanitize($_POST['time_pemasangan']) : null;
    $date  = isset($_POST['date_pemasangan']) ? sanitize($_POST['date_pemasangan']) : null;
    $catatan    = isset($_POST['catatan']) ? sanitize($_POST['catatan']) : null;
    $perumahan    = isset($_POST['perumahan']) ? sanitize($_POST['perumahan']) : null;

    // Pastikan semua data terisi
    if (!$name || !$phone  || !$registrasi_id || !$paket_internet || !$date || !$time || !$location || !$perumahan || !validatePhone($phone) || !$rikr_id  || !$netpay_id || !$catatan) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Request gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/request/ikr/create.php?id=" . $registrasi_id);
    }
    if ($phone) {

        // Hilangkan spasi
        $phone = str_replace(' ', '', $phone);

        // Kalau diawali 0 -> ubah jadi 62
        if (strpos($phone, '0') === 0) {
            $phone = '62' . substr($phone, 1);
        }
    }

    try {
        // query insert request_ikr

        $pdo->beginTransaction();
        // Query insert customers
        $sql = "INSERT INTO customers (netpay_id, name, phone, paket_internet, location, perumahan, is_active) 
                VALUES (:netpay_id, :name, :phone, :paket_internet, :location, :perumahan, 'PENDING')";
        $stmt = $pdo->prepare($sql);
        $customers_success = $stmt->execute([
            ':netpay_id' => $netpay_id,
            ':name' => $name,
            ':phone' => $phone,
            ':paket_internet' => $paket_internet,
            ':location' => $location,
            ':perumahan' => $perumahan
        ]);
        $queue_id = "Q" . date("YmdHis");
        $sql = "INSERT INTO queue_scheduling (queue_id, type_queue, netpay_id) 
                VALUES (:queue_id, :type_queue, :netpay_id)";
        $stmt = $pdo->prepare($sql);
        $queue_success = $stmt->execute([
            ':queue_id' => $queue_id,
            ':type_queue' => "Install",
            ':netpay_id' => $netpay_id
        ]);
        $sql = "INSERT INTO request_ikr (rikr_id ,queue_id, registrasi_id, catatan) 
                VALUES (:rikr_id , :queue_id, :registrasi_id, :catatan)";
        $stmt = $pdo->prepare($sql);
        $rikr_success = $stmt->execute([
            ':rikr_id' => $rikr_id,
            ':queue_id' => $queue_id,
            ':registrasi_id' => $registrasi_id,
            ':catatan' => $catatan,
        ]);

        $sql = "UPDATE register 
                SET   is_verified = 'Verified',
                      time = :time,
                      date = :date
                WHERE registrasi_id = :registrasi_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':registrasi_id' => $registrasi_id,
            ':time' => $time,
            ':date' => $date
        ]);

        $pdo->commit();
        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Selamat!',
            'text' => 'Pendaftaran Request sukses',
            'button' => "Oke",
            'style' => "success"
        ];
        redirect("pages/request/ikr/");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("DB Error: " . $e->getMessage());
        echo $e->getMessage();
        exit;
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Silakan coba lagi nanti.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/request/ikr/");
    }
} else {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal melakukan request, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/request/ikr/");
}
