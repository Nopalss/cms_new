<?php
require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/redirect.php";


$id       = $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Data tidak lengkap.',
        'button' => "Coba Lagi",
        'style' => "warning"
    ];
    redirect("pages/ikr/");
}


try {
    $pdo->beginTransaction();

    // Ambil data ikr
    $sql = "SELECT ikr_key, netpay_id, schedule_id, ikr_id FROM ikr_report WHERE ikr_key = :ikr_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ikr_key' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT rating_id, schedule_id FROM technician_ratings WHERE schedule_id = :schedule_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schedule_id' => $row['schedule_id']]);
    $rating = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception("Data tidak ditemukan.");
    }

    $sql = "DELETE FROM detail_ratings WHERE rating_id = :rating_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rating_id' => $rating['rating_id']]);
    $sql = "DELETE FROM technician_ratings WHERE rating_id = :rating_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rating_id' => $rating['rating_id']]);

    // Update schedule
    $sql = "UPDATE schedules SET status = 'Pending' WHERE schedule_id = :schedule_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schedule_id' => $row['schedule_id']]);

    // Update customer
    $sql = "UPDATE customers SET is_active = 'Pending' WHERE netpay_id = :netpay_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':netpay_id' => $row['netpay_id']]);

    // Hapus dari IKR
    $sql = "DELETE FROM ikr_report_pic WHERE ikr_id = :ikr_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ikr_id' => $row['ikr_id']]);
    $sql = "DELETE FROM ikr_report WHERE ikr_key = :ikr_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ikr_key' => $row['ikr_key']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Data IKR sudah dihapus sebelumnya.");
    }

    $pdo->commit();
    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Selamat!',
        'text' => 'Data berhasil dihapus.',
        'button' => "Oke",
        'style' => "success"
    ];
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => $e->getMessage(),
        'button' => "Oke",
        'style' => "warning"
    ];
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}

redirect("pages/ikr/");
