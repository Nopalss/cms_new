<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . "/../../../helper/redirect.php";
require_once __DIR__ . '/../../../helper/validatePhone.php';

if (isset($_POST['submit'])) {

    // =============================
    // 🔥 REQUEST DATA
    // =============================
    $rm_id           = isset($_POST['rm_id']) ? $_POST['rm_id'] : null;
    $type_issue       = isset($_POST['type_issue']) ? sanitize($_POST['type_issue']) : null;
    $deskripsi_issue  = isset($_POST['deskripsi_issue']) ? sanitize($_POST['deskripsi_issue']) : null;

    // =============================
    // 🔥 CUSTOMER DATA
    // =============================
    $netpay_id        = isset($_POST['netpay_id']) ? sanitize($_POST['netpay_id']) : null;
    $sharelock        = isset($_POST['sharelock']) ? sanitize($_POST['sharelock']) : '';
    $phone_contact    = isset($_POST['phone_contact']) ? sanitize($_POST['phone_contact']) : null; // 🔥 NEW

    // =============================
    // VALIDASI
    // =============================
    if (!$rm_id ||  !$type_issue || !$deskripsi_issue || !$phone_contact) {

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Update gagal. Pastikn semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/request/maintenance/");
        exit;
    }
    if (!validatePhone($phone_contact)) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Update  gagal. Pastikan No Telepon sesuai format.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        header("Location: " . BASE_URL . "pages/request/maintenance/");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // =============================
        // 🔄 UPDATE REQUEST
        // =============================
        $sql = "UPDATE request_maintenance 
                SET type_issue = :type_issue, 
                    deskripsi_issue = :deskripsi_issue
                WHERE rm_id = :rm_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":rm_id" => $rm_id,
            ":type_issue" => $type_issue,
            ":deskripsi_issue" => $deskripsi_issue,
        ]);

        // =============================
        // 🔥 UPDATE CUSTOMER (SHARELOCK + PHONE CONTACT)
        // =============================
        if ($netpay_id) {
            $sql = "UPDATE customers 
                    SET 
                        sharelock = :sharelock,
                        phone_contact = :phone_contact, -- 🔥 NEW
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

        redirect("pages/request/maintenance/");
    } catch (PDOException $e) {

        $pdo->rollBack();

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
            'button' => "Coba Lagi",
            'style' => "danger"
        ];

        redirect("pages/request/maintenance/");
    }
} else {

    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal melakukan update, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];

    redirect("pages/request/maintenance/");
}
