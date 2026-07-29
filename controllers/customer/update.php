<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/validatePhone.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Cek request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // Ambil data POST & sanitasi
    $netpay_key      = isset($_POST['netpay_key']) ? sanitize($_POST['netpay_key']) : null;
    $name           = isset($_POST['name']) ? sanitize($_POST['name']) : null;
    $phone          = isset($_POST['phone']) ? sanitize($_POST['phone']) : null;
    $paket_internet = isset($_POST['paket_internet']) ? sanitize($_POST['paket_internet']) : null;
    $is_active      = isset($_POST['is_active']) ? sanitize($_POST['is_active']) : null;
    $location       = isset($_POST['location']) ? sanitize($_POST['location']) : null;

    // Validasi field wajib
    $requiredFields = [
        'netpay_key'      => $netpay_key,
        'name'           => $name,
        'phone'          => $phone,
        'paket_internet' => $paket_internet,
        'is_active'      => $is_active,
        'location'       => $location
    ];

    if (!$name || !$phone || !$netpay_key || !$paket_internet  || !$is_active || !$location || !$phone) {

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Update gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/customers/update.php?id=$netpay_key");
        exit;
    }
    if (!validatePhone($phone)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Update gagal. Pastikan No Telepon sesuai format.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/customers/update.php?id=$netpay_key");
        exit;
    }

    try {
        // Query update
        $sql = "UPDATE customers 
                SET name = :name,
                    phone = :phone,
                    paket_internet = :paket_internet,
                    is_active = :is_active,
                    location = :location
                WHERE netpay_key = :netpay_key";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':netpay_key'      => $netpay_key,
            ':name'           => $name,
            ':phone'          => $phone,
            ':paket_internet' => $paket_internet,
            ':is_active'      => $is_active,
            ':location'       => $location
        ]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = [
                'icon'   => 'success',
                'title'  => 'Berhasil!',
                'text'   => 'Data customer berhasil diperbarui.',
                'button' => 'Oke',
                'style'  => 'success'
            ];
        } else {
            $_SESSION['alert'] = [
                'icon'   => 'info',
                'title'  => 'Tidak ada perubahan',
                'text'   => 'Data tetap sama, tidak ada yang diupdate.',
                'button' => 'Oke',
                'style'  => 'info'
            ];
        }
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Error!',
            'text'   => 'Gagal update data. Error: ' . $e->getMessage(),
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
    }

    header("Location: " . BASE_URL . "pages/customers/");
    exit;
} else {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Akses tidak valid.',
        'button' => "Oke",
        'style' => "warning"
    ];
    header("Location: " . BASE_URL . "pages/customers/");
    exit;
}
