<?php

require_once __DIR__ . "/../../../includes/config.php";
require_once __DIR__ . "/../../../helper/redirect.php";
require_once __DIR__ . "/../../../helper/sanitize.php";
require_once __DIR__ . '/../../../helper/validatePhone.php';


if (isset($_POST['submit'])) {

    // =============================
    // 🔥 REQUEST DATA
    // =============================
    $rm_id           = sanitize($_POST['rm_id'] ?? null);
    $type_issue      = sanitize($_POST['type_issue'] ?? null);
    $deskripsi_issue = sanitize($_POST['deskripsi_issue'] ?? null);
    $request_by      = $_SESSION['username'];

    // =============================
    // 🔥 CUSTOMER DATA
    // =============================
    $netpay_id      = sanitize($_POST['netpay_id'] ?? null);
    $name           = sanitize($_POST['name'] ?? null);
    $phone          = sanitize($_POST['phone'] ?? null);
    $phone_contact  = sanitize($_POST['phone_contact'] ?? null); // 🔥 NEW
    $is_active      = sanitize($_POST['is_active'] ?? null);
    $perumahan      = sanitize($_POST['perumahan'] ?? null);
    $location       = sanitize($_POST['location'] ?? null);
    $paket_internet = sanitize($_POST['paket_internet'] ?? null);
    $sharelock      = sanitize($_POST['sharelock'] ?? '');

    // =============================
    // VALIDASI
    // =============================
    if (!$rm_id || !$netpay_id || !$type_issue || !$deskripsi_issue || !$phone_contact) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Oops! Ada yang Salah',
            'text' => 'Request gagal. Pastikan semua data sudah diisi dengan benar.',
            'button' => "Coba Lagi",
            'style' => "danger"
        ];
        redirect("pages/request/maintenance/create.php");
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
        header("Location: " . BASE_URL . "pages/request/maintenance/create.php");
        exit;
    }
    try {
        $pdo->beginTransaction();

        // =============================
        // 🔥 CEK CUSTOMER
        // =============================
        $check = $pdo->prepare("SELECT * FROM customers WHERE netpay_id = :netpay_id");
        $check->execute([':netpay_id' => $netpay_id]);
        $existingCustomer = $check->fetch(PDO::FETCH_ASSOC);

        if (!$existingCustomer) {

            // =============================
            // ➕ INSERT CUSTOMER BARU
            // =============================
            $sql = "INSERT INTO customers 
                (netpay_id, name, phone, phone_contact, paket_internet, location, perumahan, is_active, sharelock)
                VALUES 
                (:netpay_id, :name, :phone, :phone_contact, :paket_internet, :location, :perumahan, :is_active, :sharelock)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":netpay_id" => $netpay_id,
                ":name" => $name,
                ":phone" => $phone,
                ":phone_contact" => $phone_contact,
                ":paket_internet" => $paket_internet,
                ":location" => $location,
                ":perumahan" => $perumahan,
                ":is_active" => $is_active,
                ":sharelock" => $sharelock
            ]);
        } else {

            // =============================
            // 🔄 UPDATE CUSTOMER + PHONE CONTACT
            // =============================
            $sql = "UPDATE customers SET
                        name = :name,
                        phone = :phone,
                        phone_contact = :phone_contact, -- 🔥 NEW
                        paket_internet = :paket_internet,
                        location = :location,
                        perumahan = :perumahan,
                        is_active = :is_active,
                        sharelock = :sharelock,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE netpay_id = :netpay_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $name,
                ":phone" => $phone,
                ":phone_contact" => $phone_contact,
                ":paket_internet" => $paket_internet,
                ":location" => $location,
                ":perumahan" => $perumahan,
                ":is_active" => $is_active,
                ":sharelock" => $sharelock,
                ":netpay_id" => $netpay_id
            ]);
        }
        // =============================
        // INSERT QUEUE
        // =============================
        $queue_id = "Q" . date("YmdHis");

        $sql = "INSERT INTO queue_scheduling 
            (queue_id, type_queue, netpay_id) 
            VALUES 
            (:queue_id, :type_queue, :netpay_id)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':queue_id' => $queue_id,
            ':type_queue' => "Service",
            ':netpay_id' => $netpay_id
        ]);

        // =============================
        // INSERT REQUEST
        // =============================
        $sql = "INSERT INTO request_maintenance 
            (rm_id, queue_id, type_issue, deskripsi_issue, request_by)
            VALUES 
            (:rm_id, :queue_id, :type_issue, :deskripsi_issue, :request_by)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":rm_id" => $rm_id,
            ":queue_id" => $queue_id,
            ":type_issue" => $type_issue,
            ":deskripsi_issue" => $deskripsi_issue,
            ":request_by" => $request_by
        ]);

        $pdo->commit();

        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Selamat!',
            'text' => 'Pendaftaran Request sukses',
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
        'text' => 'Gagal melakukan request, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];

    redirect("pages/request/maintenance/");
}
