<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . '/../../../helper/sanitize.php';
require_once __DIR__ . '/../../../helper/validatePhone.php';
require_once __DIR__ . '/../../../helper/redirect.php';

if (isset($_POST['submit'])) {

    // Ambil & sanitasi data POST
    $rikr_id         = isset($_POST['rikr_id'])         ? sanitize($_POST['rikr_id'])          : null;
    $netpay_id       = isset($_POST['netpay_id'])       ? sanitize($_POST['netpay_id'])        : null;
    $old_netpay_id   = isset($_POST['old_netpay_id'])   ? sanitize($_POST['old_netpay_id'])    : null;
    $registrasi_id   = isset($_POST['registrasi_id'])   ? sanitize($_POST['registrasi_id'])    : null;
    $name            = isset($_POST['name'])            ? sanitize($_POST['name'])             : null;
    $phone           = isset($_POST['phone'])           ? sanitize($_POST['phone'])            : null;
    $paket_internet  = isset($_POST['paket_internet'])  ? sanitize($_POST['paket_internet'])   : null;
    $location        = isset($_POST['location'])        ? sanitize($_POST['location'])         : null;
    $perumahan       = isset($_POST['perumahan'])       ? sanitize($_POST['perumahan'])        : null;
    $time            = isset($_POST['time_pemasangan']) ? sanitize($_POST['time_pemasangan'])  : null;
    $date            = isset($_POST['date_pemasangan']) ? sanitize($_POST['date_pemasangan'])  : null;
    $catatan         = isset($_POST['catatan'])         ? sanitize($_POST['catatan'])          : null;

    // Validasi semua field wajib
    if (
        !$rikr_id || !$netpay_id || !$old_netpay_id ||
        !$name || !$phone || !$registrasi_id ||
        !$paket_internet || !$date || !$time ||
        !$location || !$perumahan ||
        !validatePhone($phone) ||
        !$catatan
    ) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Update gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => 'Coba Lagi',
            'style'  => 'danger',
        ];
        redirect("pages/request/ikr/update.php?id=" . $rikr_id);
    }

    // Normalisasi nomor HP — hilangkan spasi, ganti awalan 0 jadi 62
    $phone = str_replace(' ', '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '62' . substr($phone, 1);
    }


    try {
        $pdo->beginTransaction();

        // 1. Update data customer
        $sql = "UPDATE customers
                SET name            = :name,
                    phone           = :phone,
                    paket_internet  = :paket_internet,
                    location        = :location,
                    perumahan       = :perumahan,
                    netpay_id       = :netpay_id
                WHERE netpay_id     = :old_netpay_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'           => $name,
            ':phone'          => $phone,
            ':paket_internet' => $paket_internet,
            ':location'       => $location,
            ':perumahan'      => $perumahan,
            ':netpay_id'      => $netpay_id,
            ':old_netpay_id'  => $old_netpay_id
        ]);

        // 1.5 Update netpay_id in queue_scheduling to keep relationships in sync
        $sqlQueue = "UPDATE queue_scheduling
                     SET netpay_id = :netpay_id
                     WHERE netpay_id = :old_netpay_id";
        $stmtQueue = $pdo->prepare($sqlQueue);
        $stmtQueue->execute([
            ':netpay_id'     => $netpay_id,
            ':old_netpay_id' => $old_netpay_id
        ]);

        // 2. Update request IKR (jadwal & catatan)
        $sql = "UPDATE request_ikr
                   SET catatan           = :catatan
                WHERE rikr_id        = :rikr_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':catatan'           => $catatan,
            ':rikr_id'          => $rikr_id,
        ]);

        // 3. Update jadwal konfirmasi di tabel register
        $sql = "UPDATE register
                SET time = :time,
                    date = :date
                WHERE registrasi_id = :registrasi_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':time'            => $time,
            ':date'            => $date,
            ':registrasi_id'  => $registrasi_id,
        ]);

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon'   => 'success',
            'title'  => 'Berhasil!',
            'text'   => 'Request IKR berhasil diperbarui.',
            'button' => 'Oke',
            'style'  => 'success',
        ];
        redirect("pages/request/ikr/");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("DB Error [update IKR]: " . $e->getMessage());
        echo $e->getMessage();
        exit;
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Silakan coba lagi nanti.' . $e->getMessage(),
            'button' => 'Coba Lagi',
            'style'  => 'danger',
        ];
        redirect("pages/request/ikr/update.php?id=" . $rikr_id);
    }
} else {
    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'text'   => 'Gagal melakukan update, silakan coba lagi.',
        'button' => 'Coba Lagi',
        'style'  => 'danger',
    ];
    redirect("pages/request/ikr/");
}
