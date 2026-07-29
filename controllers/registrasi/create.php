<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';
require_once __DIR__ . '/../../helper/validatePhone.php';
require_once __DIR__ . '/../../helper/isDateValidTomorrow.php';

if (isset($_POST['submit'])) {

    // Ambil & sanitasi data POST
    $name             = isset($_POST['name']) ? sanitize($_POST['name']) : null;
    $phone            = isset($_POST['phone']) ? sanitize($_POST['phone']) : null;
    $perumahan        = isset($_POST['perumahan']) ? sanitize($_POST['perumahan']) : null;
    $paket_internet   = isset($_POST['paket_internet']) ? sanitize($_POST['paket_internet']) : null;
    $date             = isset($_POST['date']) ? sanitize($_POST['date']) : null;
    $time             = isset($_POST['time']) ? sanitize($_POST['time']) : null;
    $location         = isset($_POST['location']) ? sanitize($_POST['location']) : null;

    // Pastikan semua data terisi
    if (
        !$name ||
        !$phone ||
        !$perumahan ||
        !$paket_internet ||
        !$date ||
        !$time ||
        !$location
    ) {

        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Registrasi gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];

        header("Location: " . BASE_URL . "pages/registrasi/create.php");
        exit;
    }

    if (!isDateValidTomorrow($date)) {

        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Registrasi gagal. Pastikan jadwal pemasangan sudah benar.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];

        header("Location: " . BASE_URL . "pages/registrasi/create.php");
        exit;
    }

    if (!validatePhone($phone)) {

        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Registrasi gagal. Pastikan No Telepon sesuai format.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];

        header("Location: " . BASE_URL . "pages/registrasi/create.php");
        exit;
    }

    // Buat ID unik
    $registrasi_id = "REG" . date("YmdHis");

    try {

        $sql = "INSERT INTO register (
                    registrasi_id,
                    name,
                    perumahan,
                    location,
                    phone,
                    paket_internet,
                    date,
                    time
                ) VALUES (
                    :registrasi_id,
                    :name,
                    :perumahan,
                    :location,
                    :phone,
                    :paket_internet,
                    :date,
                    :time
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':registrasi_id' => $registrasi_id,
            ':name'          => $name,
            ':perumahan'     => $perumahan,
            ':location'      => $location,
            ':phone'         => $phone,
            ':paket_internet' => $paket_internet,
            ':date'          => $date,
            ':time'          => $time
        ]);

        $_SESSION['lanjut_ikr'] = [
            'icon' => 'success',
            'title' => 'Registrasi Berhasil!',
            'text' => 'Apakah Anda ingin langsung membuat Request IKR?',
            'registrasi_id' => $registrasi_id
        ];

        header("Location: " . BASE_URL . "pages/registrasi");
        exit;
    } catch (PDOException $e) {

        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];

        header("Location: " . BASE_URL . "pages/registrasi/create.php");
        exit;
    }
} else {

    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'button' => 'Coba Lagi',
        'style'  => 'danger'
    ];

    header("Location: " . BASE_URL . "pages/registrasi/create.php");
    exit;
}
