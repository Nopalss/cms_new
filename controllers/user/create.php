<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/validatePhone.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // =============================
    // 🔥 AMBIL DATA
    // =============================
    $rawUsername = sanitize($_POST['username'] ?? null);
    $name        = sanitize($_POST['name'] ?? null);
    $nip         = sanitize($_POST['nip'] ?? null);
    $phone       = sanitize($_POST['phone'] ?? null);
    $passwordRaw = trim($_POST['password'] ?? null);
    $rawRole     = sanitize($_POST['role'] ?? null);

    // Format username: lowercase & alphanumeric/underscore only
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $rawUsername ?? ''));

    // Fallback otomatis jika username belum terisi: e.g. udin123
    if (empty($username) && !empty($name)) {
        $firstWord = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', trim($name))[0] ?? 'user'));
        $username  = ($firstWord ?: 'user') . '123';
    }

    if (strcasecmp($rawRole, 'teknisi') === 0) {
        $dbRole  = 'teknisi';
        $jabatan = '';
    } else {
        $dbRole  = 'admin';
        $jabatan = $rawRole; // 'Admin', 'SuperAdmin', 'NOC', 'Manager'
    }

    // Pastikan semua data terisi
    if (!$name || !$phone || !$username || !$nip || !$passwordRaw || !$rawRole) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/user/create.php");
        exit;
    }

    if (!validatePhone($phone)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Pastikan No Telepon sesuai format.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/user/create.php");
        exit;
    }

    // 🔥 NORMALISASI → SELALU JADI 62
    if (strpos($phone, '08') === 0) {
        $phone = '62' . substr($phone, 1);
    }

    // =============================
    // 🔥 CEK DUPLICATE USERNAME
    // =============================
    $check = $pdo->prepare("SELECT 1 FROM users WHERE username = :username");
    $check->execute([':username' => $username]);

    if ($check->fetchColumn()) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops!',
            'text' => 'Username sudah digunakan.',
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];
        header("Location: " . BASE_URL . "pages/user/create.php");
        exit;
    }

    // =============================
    // 🔥 HASH PASSWORD
    // =============================
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

    try {

        $pdo->beginTransaction();

        // =============================
        // INSERT USERS
        // =============================
        $sql = "INSERT INTO users (username,password,role, avatar)
                VALUES (:username,:password,:role, :avatar)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':password' => $password,
            ':role'     => $dbRole,
            ':avatar'   => "blank.png"
        ]);

        // =============================
        // INSERT ROLE TABLE
        // =============================
        if ($dbRole === 'admin') {
            $stmt = $pdo->prepare("
                INSERT INTO admin (admin_id, name, phone, username, jabatan)
                VALUES (:nip, :name, :phone, :username, :jabatan)
            ");
            $stmt->execute([
                ':name'     => $name,
                ':phone'    => $phone,
                ':nip'      => $nip,
                ':username' => $username,
                ':jabatan'  => $jabatan
            ]);
        } elseif ($dbRole === 'teknisi') {
            $stmt = $pdo->prepare("
                INSERT INTO technician (tech_id, name, phone, username)
                VALUES (:nip, :name, :phone, :username)
            ");
            $stmt->execute([
                ':name'     => $name,
                ':phone'    => $phone,
                ':nip'      => $nip,
                ':username' => $username
            ]);
        }

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon'  => 'success',
            'title' => 'Selamat!',
            'text'  => 'Pembuatan Akun Sukses',
            'button' => 'Oke',
            'style' => 'success'
        ];
    } catch (PDOException $e) {

        $pdo->rollBack();

        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Error!',
            'text'   => 'Gagal menyimpan data. Error: ' . $e->getMessage(),
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
    }

    header("Location: " . BASE_URL . "pages/user/");
    exit;
}
