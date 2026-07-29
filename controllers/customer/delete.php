<?php
require_once __DIR__ . "/../../includes/config.php";

$id       = $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Data tidak lengkap.',
        'button' => "Coba Lagi",
        'style' => "warning"
    ];
    header("Location: " . BASE_URL . "pages/customers/");
    exit;
}

try {
    $pdo->beginTransaction();

    // Ambil data ikr
    $sql = "SELECT netpay_key FROM customers WHERE netpay_key = :netpay_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':netpay_key' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Data tidak ditemukan.");
    }


    // Hapus 
    $sql = "DELETE FROM customers WHERE netpay_key = :netpay_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':netpay_key' => $row['netpay_key']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Data customers sudah dihapus sebelumnya.");
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
    error_log("DB Error: " . $e->getMessage());
    $pdo->rollBack();
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}

header("Location: " . BASE_URL . "pages/customers/");
exit;
