<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';
require_once __DIR__ . '/../../helper/validatePhone.php';
require_once __DIR__ . '/../../helper/isDateValidTomorrow.php';
require_once __DIR__ . '/../../helper/redirect.php';

if (isset($_POST['submit'])) {
    $posted_token = $_POST['token'] ?? '';
    $session_token = $_SESSION['form_token'] ?? '';
    if (!hash_equals($session_token, $posted_token)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops!',
            'text' => 'Token tidak valid atau sudah dipakai.',
            'button' => 'OK',
            'style' => 'danger'
        ];
        redirect("registration.php");
    }
    // Token valid → hapus agar sekali pakai
    unset($_SESSION['form_token']);

    // Ambil & sanitasi data POST
    $name   = isset($_POST['name']) ? sanitize($_POST['name']) : null;
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : null;
    $paket_internet    = isset($_POST['paket_internet']) ? sanitize($_POST['paket_internet']) : null;
    $date  = isset($_POST['date']) ? sanitize($_POST['date']) : null;
    $time  = isset($_POST['time']) ? sanitize($_POST['time']) : null;
    $location  = isset($_POST['location']) ? sanitize($_POST['location']) : null;

    // Pastikan semua data terisi
    if (!$name || !$phone || !$paket_internet || !$date  || !$time || !$location || !validatePhone($phone) || !isDateValidTomorrow($date)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Pendaftaran gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("registration.php");
    }

    $sqlCheck = "SELECT COUNT(*) FROM register WHERE phone = :phone";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':phone' => $phone]);
    $exists = $stmtCheck->fetchColumn();

    if ($exists > 0) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Nomor Sudah Terdaftar',
            'text' => 'Nomor HP ini sudah pernah registrasi.',
            'button' => "Oke",
            'style' => "warning"
        ];
        redirect("registration.php");
    }
    // Buat ID unik
    $registrasi_id = uniqid('REG');
    try {
        // Query insert dengan prepared statement
        $sql = "INSERT INTO register (registrasi_id, name, phone, paket_internet, date, time,  location) 
                VALUES (:registrasi_id, :name, :phone, :paket_internet, :date, :time, :location)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':registrasi_id' => $registrasi_id,
            ':name' => $name,
            ':phone' => $phone,
            ':paket_internet' => $paket_internet,
            ':date' => $date,
            ':time' => $time,
            ':location' => $location
        ]);
        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Selamat!',
            'text' => 'Pendaftaran sukses. Tim kami akan segera menghubungi Anda',
            'button' => "Oke",
            'style' => "success"
        ];
        redirect("registration.php");
    } catch (PDOException $e) {
        // echo $e;
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("registration.php");
    }
} else {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("registration.php");
}
