<?php
require_once __DIR__ . '/../../includes/config.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    $id = isset($_POST['id']) ? sanitize($_POST['id']) : null;


    // Validasi input dasar
    if (!$id) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Data tidak lengkap.',
            'button' => "Coba Lagi",
            'style' => "warning"
        ];
        header("Location: " . BASE_URL . "pages/user/");
        exit;
    }

    try {
        // Ambil role dan username user target
        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            throw new Exception("User tidak ditemukan.");
        }

        $role = $targetUser['role'];

        // Mapping role -> tabel spesifik
        $roles = [
            "admin"   => 'admin',
            "teknisi" => 'technician'
        ];

        $pdo->beginTransaction();

        // Hapus dari tabel role (jika ada)
        if (isset($roles[$role])) {
            $table = $roles[$role];
            $stmt = $pdo->prepare("DELETE FROM $table WHERE username = :username");
            $stmt->execute([':username' => $targetUser['username']]);
        }

        // Hapus dari tabel users
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon'   => 'success',
            'title'  => 'Berhasil!',
            'text'   => 'User berhasil dihapus.',
            'button' => 'Oke',
            'style'  => 'success'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alert'] = [
            'icon'   => 'danger',
            'title'  => 'Error!',
            'text'   => 'Gagal menghapus data. Error: ' . $e->getMessage(),
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
    }

    header("Location: " . BASE_URL . "pages/user/");
    exit;
}
