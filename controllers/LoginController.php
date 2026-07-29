<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../helper/redirect.php';

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['username'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == "teknisi") {
        redirect("pages/schedule/");
    }
    redirect("pages/dashboard.php");
}

if (isset($_POST['login'])) {
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Username atau password wajib diisi',
            'text' => 'Silakan coba lagi',
            'button' => "Coba Lagi",
            'style' => 'danger'
        ];
        redirect("index.php");
    }
    try {
        // ambil data username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // cek password 
        if ($user && password_verify($password, $user['password'])) {
            // cek role
            switch ($user['role']) {
                case 'admin':
                    $table = 'admin';
                    $id_col = 'admin_id';
                    $redirect_path = "pages/dashboard.php";
                    break;
                case 'teknisi':
                    $table = 'technician';
                    $id_col = 'tech_id';
                    $redirect_path = "pages/schedule/";
                    break;
                default:
                    throw new Exception("Role tidak dikenali");
            }

            $stmt = $pdo->prepare("SELECT * FROM $table WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $karyawan = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['id_karyawan'] = $karyawan[$id_col] ?? null;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['jabatan'] = ($user['role'] === 'admin') ? (!empty($karyawan['jabatan']) ? $karyawan['jabatan'] : 'Admin') : 'Teknisi';
            $_SESSION['img'] = $user['avatar'];
            $_SESSION['name'] = $karyawan['name'];
            $_SESSION['no_np'] = $karyawan['phone'];
            $_SESSION['tim_id'] = isset($karyawan['tim_id']) ? $karyawan['tim_id'] : 0;

            $_SESSION['alert'] = [
                'icon' => 'success',
                'title' => 'Login Berhasil',
                'text' => 'Selamat datang kembali!',
                'button' => "Oke",
                'style' => 'success'
            ];
            redirect($redirect_path);
        } else {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Login Gagal',
                'text' => 'Username atau password salah!',
                'button' => "Coba Lagi",
                'style' => 'danger'
            ];
            redirect("");
        }
    } catch (PDOException $e) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Terjadi Kesalahan',
            'text' => 'Silakan coba lagi nanti.',
            'button' => "Coba Lagi",
            'style' => 'danger'
        ];
        redirect("");
    }
}
