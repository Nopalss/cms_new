<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/redirect.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . '/../../../helper/validatePhone.php';


if (isset($_POST['submit'])) {

    // =============================
    // 🔥 REQUEST DATA
    // =============================
    $rd_id                 = isset($_POST['rd_id']) ? sanitize($_POST['rd_id']) : null;
    $type_dismantle        = isset($_POST['type_dismantle']) ? sanitize($_POST['type_dismantle']) : null;
    $deskripsi_dismantle   = isset($_POST['deskripsi_dismantle']) ? sanitize($_POST['deskripsi_dismantle']) : null;
    $request_by            = $_SESSION['username'];

    // =============================
    // 🔥 CUSTOMER DATA
    // =============================
    $netpay_id      = isset($_POST['netpay_id']) ? sanitize($_POST['netpay_id']) : null;
    $sharelock      = isset($_POST['sharelock']) ? sanitize($_POST['sharelock']) : '';
    $phone_contact  = isset($_POST['phone_contact']) ? sanitize($_POST['phone_contact']) : null; // 🔥 NEW

    // =============================
    // VALIDASI
    // =============================
    if (!$rd_id  || !$type_dismantle || !$deskripsi_dismantle || !$phone_contact) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Update gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/request/dismantle/");
        exit;
    }

    if (!validatePhone($phone_contact)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Request gagal. Pastikan No Telepon sesuai format.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/request/dismantle/");
        exit;
    }
    try {
        $pdo->beginTransaction();

        // =============================
        // 🔄 UPDATE REQUEST DISMANTLE
        // =============================
        $sql = "UPDATE request_dismantle 
                SET type_dismantle = :type_dismantle, 
                    deskripsi_dismantle = :deskripsi_dismantle
                WHERE rd_id = :rd_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":rd_id" => $rd_id,
            ":type_dismantle" => $type_dismantle,
            ":deskripsi_dismantle" => $deskripsi_dismantle,
        ]);

        // =============================
        // 🔥 UPDATE CUSTOMER (SHARELOCK + PHONE CONTACT)
        // =============================
        if ($netpay_id) {
            $sql = "UPDATE customers 
                    SET 
                        sharelock = :sharelock,
                        phone_contact = :phone_contact,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE netpay_id = :netpay_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":sharelock" => $sharelock,
                ":phone_contact" => $phone_contact,
                ":netpay_id" => $netpay_id
            ]);
        }

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data Request berhasil diperbarui',
            'button' => "Oke",
            'style' => "success"
        ];

        redirect("pages/request/dismantle/");
    } catch (PDOException $e) {

        $pdo->rollBack();
        error_log($e->getMessage());

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Silakan coba lagi nanti.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];

        redirect("pages/request/dismantle/");
    }
} else {

    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal melakukan update, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];

    redirect("pages/request/dismantle/");
}
