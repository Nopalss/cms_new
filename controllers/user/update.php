<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/validatePhone.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function sanitize($data)
    {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    $oldUsername = sanitize($_POST['old_username'] ?? $_POST['username'] ?? '');
    $rawUsername = sanitize($_POST['username'] ?? '');
    $newUsername = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $rawUsername));

    if (empty($newUsername)) {
        $newUsername = $oldUsername;
    }

    $name        = sanitize($_POST['name'] ?? '');
    $nip         = sanitize($_POST['nip'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $passwordRaw = trim($_POST['password'] ?? '');
    $rawRole     = sanitize($_POST['role'] ?? '');

    if (strcasecmp($rawRole, 'teknisi') === 0) {
        $dbRole  = 'teknisi';
        $jabatan = '';
    } else {
        $dbRole  = 'admin';
        $jabatan = $rawRole; // 'Admin', 'SuperAdmin', 'NOC', 'Manager'
    }

    if (!$name || !$phone || !$newUsername || !$nip || !$rawRole) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Pastikan semua data sudah diisi dengan benar.',
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];
        header("Location: " . BASE_URL . "pages/user/update.php?id=" . urlencode($oldUsername));
        exit;
    }

    if (!validatePhone($phone)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Pastikan No Telepon sesuai format.',
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];
        header("Location: " . BASE_URL . "pages/user/update.php?id=" . urlencode($oldUsername));
        exit;
    }

    if (strpos($phone, '08') === 0) {
        $phone = '62' . substr($phone, 1);
    }

    // Cek jika username diubah dan sudah terpakai oleh user lain
    if ($newUsername !== $oldUsername) {
        $check = $pdo->prepare("SELECT 1 FROM users WHERE username = :newUsername");
        $check->execute([':newUsername' => $newUsername]);
        if ($check->fetchColumn()) {
            $_SESSION['alert'] = [
                'icon' => 'error',
                'title' => 'Oops!',
                'text' => "Username '$newUsername' sudah digunakan user lain.",
                'button' => 'Coba Lagi',
                'style' => 'danger'
            ];
            header("Location: " . BASE_URL . "pages/user/update.php?id=" . urlencode($oldUsername));
            exit;
        }
    }

    try {

        $pdo->beginTransaction();

        // =============================
        // AMBIL ROLE LAMA
        // =============================

        $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
        $stmt->execute([$oldUsername]);
        $oldRole = $stmt->fetchColumn();

        if (!$oldRole) {
            throw new Exception("User tidak ditemukan.");
        }

        // Jika username diubah, update username di tabel admin & technician dulu sebelum users
        if ($newUsername !== $oldUsername) {
            $pdo->prepare("UPDATE admin SET username = :new WHERE username = :old")->execute([':new' => $newUsername, ':old' => $oldUsername]);
            $pdo->prepare("UPDATE technician SET username = :new WHERE username = :old")->execute([':new' => $newUsername, ':old' => $oldUsername]);
        }

        // =============================
        // UPDATE USERS
        // =============================

        $paramsUser = [
            ':new_username' => $newUsername,
            ':old_username' => $oldUsername,
            ':role'         => $dbRole
        ];

        $sqlUser = "UPDATE users SET username = :new_username, role = :role";

        if (!empty($passwordRaw)) {
            $sqlUser .= ", password = :password";
            $paramsUser[':password'] = password_hash($passwordRaw, PASSWORD_DEFAULT);
        }

        $sqlUser .= " WHERE username = :old_username";
        $stmt = $pdo->prepare($sqlUser);
        $stmt->execute($paramsUser);

        // username yang aktif sekarang adalah newUsername
        $username = $newUsername;

        // =============================
        // PROSES PERUBAHAN ROLE & DATA KARYAWAN
        // =============================

        if ($dbRole === 'admin') {
            // Hapus dari tabel technician jika ada
            $pdo->prepare("DELETE FROM technician WHERE username = ?")->execute([$username]);

            // Upsert ke tabel admin
            $chkAdmin = $pdo->prepare("SELECT 1 FROM admin WHERE username = ?");
            $chkAdmin->execute([$username]);

            if ($chkAdmin->fetchColumn()) {
                $stmt = $pdo->prepare("
                    UPDATE admin
                    SET admin_id = :nip,
                        name     = :name,
                        phone    = :phone,
                        jabatan  = :jabatan
                    WHERE username = :username
                ");
                $stmt->execute([
                    ':nip'      => $nip,
                    ':name'     => $name,
                    ':phone'    => $phone,
                    ':jabatan'  => $jabatan,
                    ':username' => $username
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO admin (admin_id, name, phone, username, jabatan)
                    VALUES (:nip, :name, :phone, :username, :jabatan)
                ");
                $stmt->execute([
                    ':nip'      => $nip,
                    ':name'     => $name,
                    ':phone'    => $phone,
                    ':username' => $username,
                    ':jabatan'  => $jabatan
                ]);
            }
        } elseif ($dbRole === 'teknisi') {
            // Hapus dari tabel admin jika ada
            $pdo->prepare("DELETE FROM admin WHERE username = ?")->execute([$username]);

            // Upsert ke tabel technician
            $chkTech = $pdo->prepare("SELECT 1 FROM technician WHERE username = ?");
            $chkTech->execute([$username]);

            if ($chkTech->fetchColumn()) {
                $stmt = $pdo->prepare("
                    UPDATE technician
                    SET tech_id = :nip,
                        name    = :name,
                        phone   = :phone
                    WHERE username = :username
                ");
                $stmt->execute([
                    ':nip'      => $nip,
                    ':name'     => $name,
                    ':phone'    => $phone,
                    ':username' => $username
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO technician (tech_id, name, phone, username)
                    VALUES (:nip, :name, :phone, :username)
                ");
                $stmt->execute([
                    ':nip'      => $nip,
                    ':name'     => $name,
                    ':phone'    => $phone,
                    ':username' => $username
                ]);
            }
        }

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data user berhasil diupdate.',
            'button' => 'Oke',
            'style' => 'success'
        ];
    } catch (Exception $e) {

        $pdo->rollBack();

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Error!',
            'text' => $e->getMessage(),
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];
    }

    header("Location: " . BASE_URL . "pages/user/");
    exit;
}

header("Location: " . BASE_URL . "pages/user/");
exit;
