<?php
require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../includes/check_password.php";
require_once __DIR__ . "/../../../helper/redirect.php";

$id       = isset($_POST['id']) ? (int)$_POST['id'] : null;



try {
    $pdo->beginTransaction();

    // Cari registrasi_id dari request_ikr
    $sql = "SELECT r.rikr_id, r.registrasi_id, q.netpay_id, r.queue_id FROM request_ikr r JOIN queue_scheduling q ON r.queue_id = q.queue_id WHERE r.rikr_key = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Data tidak ditemukan.',
            'button' => "Oke",
            'style' => "warning"
        ];
        redirect("pages/request/ikr/");
    }

    // Update status register jadi Unverified
    if (!empty($row['registrasi_id'])) {
        $sql = "UPDATE register 
                    SET is_verified = 'Unverified'
                    WHERE registrasi_id = :registrasi_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':registrasi_id' => $row['registrasi_id']]);
    }

    // Hapus dari customers (anak)
    $sql = "DELETE FROM customers WHERE netpay_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $row['netpay_id']]);

    // Hapus dari queue_scheduling (anak)
    $sql = "DELETE FROM queue_scheduling WHERE queue_id= :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $row['queue_id']]);

    // Hapus dari request_ikr (induk)
    $sql = "DELETE FROM request_ikr WHERE rikr_key = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    // Pastikan ada yang kehapus
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Tidak ada data yang dihapus.',
            'button' => "Oke",
            'style' => "warning"
        ];
    } else {
        $pdo->commit();
        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Selamat!',
            'text' => 'Data berhasil dihapus.',
            'button' => "Oke",
            'style' => "success"
        ];
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("[DELETE IKR] DB Error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}

redirect("pages/request/ikr/");
