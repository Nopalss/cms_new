<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'customer';

$paketInternet = [
    "5"   => "5 mbps - 150rb/bln",
    "10"  => "10 mbps - 300rb/bln",
    "30"  => "30 mbps - 650rb/bln",
    "50"  => "50 mbps - 850rb/bln",
    "100" => "100 mbps - 1jt/bln"
];
// =============================
// 🔥 HANDLE SUBMIT
// =============================
if (isset($_POST['submit'])) {

    $netpay_id      = sanitize($_POST['netpay_id'] ?? '');
    $name           = sanitize($_POST['name'] ?? '');
    $phone          = sanitize($_POST['phone'] ?? '');
    $paket_internet = sanitize($_POST['paket_internet'] ?? '');
    $location       = sanitize($_POST['location'] ?? '');
    $perumahan      = sanitize($_POST['perumahan'] ?? '');
    $is_active      = sanitize($_POST['is_active'] ?? 'ACTIVE');

    if (!$netpay_id || !$name || !$phone) {
        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Validasi Gagal',
            'text' => 'Netpay ID, Nama, dan No HP wajib diisi',
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];
        redirect("pages/customers/create.php");

        exit;
    }

    try {
        $sql = "INSERT INTO customers 
            (netpay_id, name, phone, paket_internet, location, perumahan, is_active)
            VALUES 
            (:netpay_id, :name, :phone, :paket_internet, :location, :perumahan, :is_active)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':netpay_id' => $netpay_id,
            ':name' => $name,
            ':phone' => $phone,
            ':paket_internet' => $paket_internet,
            ':location' => $location,
            ':perumahan' => $perumahan,
            ':is_active' => $is_active
        ]);

        $_SESSION['alert'] = [
            'icon' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Customer berhasil ditambahkan',
            'button' => 'Oke',
            'style' => 'success'
        ];

        redirect("pages/customers/");
    } catch (PDOException $e) {

        $_SESSION['alert'] = [
            'icon' => 'error',
            'title' => 'Error',
            'text' => 'Gagal menyimpan data ' .  $e->getMessage(),
            'button' => 'Coba Lagi',
            'style' => 'danger'
        ];

        redirect("pages/customers/create.php");
    }
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">➕ Tambah Customer</h3>
                    </div>

                    <div class="card-body">

                        <form method="post">

                            <!-- NETPAY ID -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Netpay ID</label>
                                <input type="text" name="netpay_id" class="form-control"
                                    placeholder="Contoh: 123456789" required>
                            </div>

                            <!-- NAMA -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Nama Customer</label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Masukkan nama customer" required>
                            </div>

                            <!-- PHONE -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">No HP</label>
                                <input type="text" name="phone" class="form-control"
                                    placeholder="08xxxx / 628xxxx" required>
                            </div>

                            <!-- PAKET -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Paket Internet</label>

                                <select class="form-control selectpicker" id="paket_internet" required name="paket_internet" data-size=" 7">
                                    <option value="">Select</option>
                                    <?php foreach ($paketInternet as $key => $value): ?>
                                        <option value='<?= $key ?>'><?= $value ?> Mbps</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- PERUMAHAN -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Perumahan</label>
                                <input type="text" name="perumahan" class="form-control"
                                    placeholder="Nama perumahan">
                            </div>

                            <!-- LOKASI -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Alamat</label>
                                <textarea name="location" class="form-control" rows="3"
                                    placeholder="Alamat lengkap"></textarea>
                            </div>


                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>pages/customer/"
                            class="btn btn-light-danger font-weight-bold">
                            Cancel
                        </a>

                        <button type="submit" name="submit"
                            class="btn btn-primary font-weight-bold px-5">
                            Save Customer
                        </button>
                    </div>

                    </form>

                </div>

            </div>
        </div>

    </div>
</div>


<?php require __DIR__ . '/../../includes/footer.php'; ?>