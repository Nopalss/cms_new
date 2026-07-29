<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';
require_once __DIR__ . '/../../helper/validatePhone.php';
require_once __DIR__ . '/../../helper/isDateValidTomorrow.php';
require_once __DIR__ . '/../../helper/redirect.php';

if (isset($_POST['submit'])) {

    // Ambil & sanitasi data POST
    $registrasi_key = isset($_POST['registrasi_key']) ? sanitize($_POST['registrasi_key']) : null;
    $name           = isset($_POST['name'])           ? sanitize($_POST['name'])           : null;
    $phone          = isset($_POST['phone'])          ? sanitize($_POST['phone'])          : null;
    $perumahan      = isset($_POST['perumahan'])      ? sanitize($_POST['perumahan'])      : null;
    $paket_internet = isset($_POST['paket_internet']) ? sanitize($_POST['paket_internet']) : null;
    $date           = isset($_POST['date'])           ? sanitize($_POST['date'])           : null;
    $time           = isset($_POST['time'])           ? sanitize($_POST['time'])           : null;
    $location       = isset($_POST['location'])       ? sanitize($_POST['location'])       : null;

    // Pastikan semua data terisi
    if (!$registrasi_key || !$name || !$phone || !$perumahan || !$paket_internet || !$date || !$time || !$location) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Update gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
        redirect("pages/registrasi/update.php?id=$registrasi_key");
    }

    if (!isDateValidTomorrow($date)) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Update gagal. Pastikan jadwal pemasangan sudah benar.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
        redirect("pages/registrasi/update.php?id=$registrasi_key");
    }

    if (!validatePhone($phone)) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Update gagal. Pastikan No Telepon sesuai format.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
        redirect("pages/registrasi/update.php?id=$registrasi_key");
    }

    try {

        $check = $pdo->prepare("SELECT COUNT(*) FROM register WHERE registrasi_key = :key");
        $check->execute([':key' => $registrasi_key]);
        if ($check->fetchColumn() == 0) {
            $_SESSION['alert'] = [
                'icon'   => 'error',
                'title'  => 'Data Tidak Ditemukan',
                'text'   => 'Registrasi yang ingin kamu ubah tidak ada.',
                'button' => 'Coba Lagi',
                'style'  => 'danger'
            ];
            redirect("pages/registrasi/");
        }

        $sql = "UPDATE register
                SET name           = :name,
                    phone          = :phone,
                    perumahan      = :perumahan,
                    paket_internet = :paket_internet,
                    date           = :date,
                    time           = :time,
                    location       = :location
                WHERE registrasi_key = :registrasi_key";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':registrasi_key' => $registrasi_key,
            ':name'           => $name,
            ':phone'          => $phone,
            ':perumahan'      => $perumahan,
            ':paket_internet' => $paket_internet,
            ':date'           => $date,
            ':time'           => $time,
            ':location'       => $location,
        ]);

        $_SESSION['alert'] = [
            'icon'   => 'success',
            'title'  => 'Selamat!',
            'text'   => 'Data registrasi berhasil diperbarui',
            'button' => 'Oke',
            'style'  => 'success'
        ];
        redirect("pages/registrasi/");
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Silakan coba lagi nanti.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
        redirect("pages/registrasi/");
    }
} else {
    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'text'   => 'Gagal melakukan update, silakan coba lagi',
        'button' => 'Coba Lagi',
        'style'  => 'danger'
    ];
    redirect("pages/registrasi/");
}
