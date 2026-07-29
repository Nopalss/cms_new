<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'customer';


$id = (int)($_GET['id'] ?? null);

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'ID Customer tidak valid.',
        'button' => "Kembali",
        'style' => "warning"
    ];
    header("Location: " . BASE_URL . "pages/customers/");
    exit;
}
try {
    $sql = "SELECT *
            FROM customers
            WHERE netpay_key = :netpay_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':netpay_key' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Customer tidak ditemukan.',
            'button' => "Kembali",
            'style' => "warning"
        ];
        header("Location: " . BASE_URL . "pages/customers/");
        exit;
    }
    $paketInternet = [
        "5"   => "5 mbps - 150rb/bln",
        "10"  => "10 mbps - 300rb/bln",
        "30"  => "30 mbps - 650rb/bln",
        "50"  => "50 mbps - 850rb/bln",
        "100" => "100 mbps - 1jt/bln"
    ];
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'danger',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <!-- Subheader -->
    <div class="subheader py-2 py-lg-6 subheader-solid" id="kt_subheader">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
            <div class="d-flex align-items-center flex-wrap mr-1">
                <div class="d-flex align-items-baseline flex-wrap mr-5">

                    <h5 class="text-dark font-weight-bold my-1 mr-5"><a class="text-dark " href=" <?= BASE_URL ?>pages/customers/">Customers</a> </h5>
                    <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                        <li class="breadcrumb-item"><a href="" class="text-muted">Detail Customers</a></li>
                        <li class="breadcrumb-item"><a href="" class="text-muted"><?= $row['netpay_id'] ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Entry -->
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Detail Customers -->
                <div class="col-md-6 mt-5 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Detail Customers</h5>
                        </div>
                        <form method="post" class="form " action="<?= BASE_URL ?>controllers/customer/update.php">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Netpay ID</label>
                                    <input id="name" type="text" class="form-control" value="<?= $row['netpay_id'] ?>" disabled='disabled'>
                                    <input id="name" type="hidden" class="form-control" name="netpay_key" value="<?= $row['netpay_key'] ?>">
                                </div>
                                <div class="form-group">
                                    <label for="name">Nama</label>
                                    <input id="name" type="text" class="form-control" name="name" value="<?= $row['name'] ?>">
                                </div>
                                <div class="form-group">
                                    <label for="phone">No HP</label>
                                    <input id="phone" type="tel" class="form-control" name="phone" value="<?= $row['phone'] ?>" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="form-group">
                                    <label for="paket_internet">Paket</label>
                                    <select class="form-control selectpicker" id="paket_internet" name="paket_internet" data-size=" 7">
                                        <option value="">Select</option>
                                        <?php foreach ($paketInternet as $key => $value): ?>
                                            <?php $selected = ($key == $row['paket_internet']) ? 'selected' : ''; ?>
                                            <option value='<?= $key ?>' <?= $selected ?>><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="is_active">Is Active?</label>
                                    <select class="form-control selectpicker" id="is_active" name="is_active" data-size=" 7">
                                        <option value="">Select</option>
                                        <?php
                                        $is_active = ['ACTIVE', 'DISMANTLE'];
                                        foreach ($is_active as $i): ?>
                                            <?php $selected = (strtolower($i) == strtolower($row['is_active'])) ? 'selected' : ''; ?>
                                            <option value='<?= $i ?>' <?= $selected ?>><?= $i ?></option>"
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="exampleTextarea">Alamat</label>
                                    <textarea class="form-control" id="exampleTextarea" name="location" rows="3"><?= $row['location'] ?></textarea>
                                </div>

                            </div>
                            <div class="card-footer text-right">
                                <a href="<?= BASE_URL ?>pages/customers/" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancel</a>
                                <button type="submit" name="submit" class="btn btn-primary font-weight-bold">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>