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
    // Join to get the latest IKR Report details for the customer
    $sql = "SELECT c.*, 
                   i.rt, i.rw, i.desa, i.kec, i.kab, i.alamat as ikr_alamat,
                   i.sn, i.type_ont, i.redaman, i.odp_no, i.odc_no, i.jc_no,
                   i.mac_sebelum, i.mac_sesudah, i.odp, i.odc, i.enclosure
            FROM customers c
            LEFT JOIN ikr_report i ON i.ikr_id = (
                SELECT ikr_id FROM ikr_report 
                WHERE netpay_id = c.netpay_id 
                ORDER BY created_at DESC 
                LIMIT 1
            )
            WHERE c.netpay_key = :netpay_key";
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
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$initials = strtoupper(substr($row['name'] ?? 'C', 0, 2));
$statusClass = 'secondary';
$statusText = $row['is_active'] ?? 'Inactive';
if (strtolower($statusText) === 'active') {
    $statusClass = 'success';
} elseif (strtolower($statusText) === 'dismantle') {
    $statusClass = 'danger';
}
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <!-- Subheader -->
    <div class="subheader py-2 py-lg-6 subheader-solid" id="kt_subheader">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
            <div class="d-flex align-items-center flex-wrap mr-1">
                <div class="d-flex align-items-baseline flex-wrap mr-5">
                    <h5 class="text-dark font-weight-bold my-1 mr-5"><a class="text-dark " href="<?= BASE_URL ?>pages/customers/">Customers</a></h5>
                    <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                        <li class="breadcrumb-item"><a href="" class="text-muted">Detail Customers</a></li>
                        <li class="breadcrumb-item"><a href="" class="text-muted"><?= htmlspecialchars($row['netpay_id']) ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Entry -->
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="row">
                
                <!-- Left Column: Profile Card -->
                <div class="col-lg-4 mt-5">
                    <div class="card card-custom card-stretch">
                        <div class="card-body pt-15 pb-10 text-center">
                            <!-- Avatar / Initials -->
                            <div class="symbol symbol-60 symbol-circle symbol-xl-90 mb-5">
                                <span class="symbol-label font-size-h2 font-weight-boldest text-primary bg-light-primary" style="width: 90px; height: 90px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                    <?= $initials ?>
                                </span>
                            </div>
                            <!-- Name & ID -->
                            <h4 class="font-weight-boldest text-dark mb-1"><?= htmlspecialchars($row['name']) ?></h4>
                            <span class="text-muted font-weight-bold d-block mb-4">ID: <?= htmlspecialchars($row['netpay_id']) ?></span>
                            
                            <!-- Status Badge -->
                            <span class="btn btn-sm btn-light-<?= $statusClass ?> font-weight-bolder text-uppercase px-4 py-1.5 mb-8">
                                <?= $statusText ?>
                            </span>

                            <!-- Info List -->
                            <div class="text-left mt-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted font-weight-bold">No. Telp:</span>
                                    <span class="text-dark-75 font-weight-bolder"><?= htmlspecialchars($row['phone']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted font-weight-bold">Paket Internet:</span>
                                    <span class="label label-inline label-light-primary font-weight-bold"><?= htmlspecialchars($row['paket_internet']) ?> Mbps</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted font-weight-bold">Tanggal Registrasi:</span>
                                    <span class="text-dark-75 font-weight-bolder">
                                        <?= !empty($row['created_at']) ? date('d F Y', strtotime($row['created_at'])) : '-' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Detail Tabs & Information -->
                <div class="col-lg-8 mt-5">
                    
                    <!-- Card 1: Address Details -->
                    <div class="card card-custom gutter-b">
                        <div class="card-header border-0 pt-6 pb-2">
                            <div class="card-title">
                                <h3 class="card-label font-weight-bolder text-dark">
                                    <i class="flaticon2-pin text-primary mr-2"></i> Detail Alamat Lengkap
                                </h3>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted font-weight-bold d-block">RT / RW</label>
                                    <span class="text-dark font-weight-bolder font-size-lg">
                                        <?= !empty($row['rt']) ? 'RT ' . htmlspecialchars($row['rt']) : '-' ?> / 
                                        <?= !empty($row['rw']) ? 'RW ' . htmlspecialchars($row['rw']) : '-' ?>
                                    </span>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted font-weight-bold d-block">Desa / Kelurahan</label>
                                    <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['desa']) ? htmlspecialchars($row['desa']) : '-' ?></span>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted font-weight-bold d-block">Kecamatan</label>
                                    <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['kec']) ? htmlspecialchars($row['kec']) : '-' ?></span>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted font-weight-bold d-block">Kabupaten / Kota</label>
                                    <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['kab']) ? htmlspecialchars($row['kab']) : '-' ?></span>
                                </div>
                                <div class="col-12 mb-4">
                                    <label class="text-muted font-weight-bold d-block">Alamat Pemasangan (IKR)</label>
                                    <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['ikr_alamat']) ? htmlspecialchars($row['ikr_alamat']) : '-' ?></span>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted font-weight-bold d-block">Alamat Utama Customer</label>
                                    <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['location']) ? htmlspecialchars($row['location']) : '-' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Infrastructure & Network -->
                    <div class="card card-custom gutter-b">
                        <div class="card-header border-0 pt-6 pb-2">
                            <div class="card-title">
                                <h3 class="card-label font-weight-bolder text-dark">
                                    <i class="flaticon-network text-success mr-2"></i> Detail Infrastruktur & Jaringan
                                </h3>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <?php if (empty($row['sn']) && empty($row['type_ont']) && empty($row['redaman'])): ?>
                                <div class="alert alert-custom alert-light-warning fade show mb-0" role="alert">
                                    <div class="alert-icon"><i class="flaticon-warning text-warning"></i></div>
                                    <div class="alert-text font-weight-bold">
                                        Belum ada Laporan IKR yang terdaftar untuk pelanggan ini. Data perangkat ONT dan detail jaringan infrastruktur tidak tersedia.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">SN ONT</label>
                                        <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['sn']) ? htmlspecialchars($row['sn']) : '-' ?></span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">Tipe ONT</label>
                                        <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['type_ont']) ? htmlspecialchars($row['type_ont']) : '-' ?></span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">Redaman (Attenuation)</label>
                                        <span class="text-dark font-weight-bolder font-size-lg">
                                            <?= !empty($row['redaman']) ? htmlspecialchars($row['redaman']) . ' dBm' : '-' ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">No ODP / No ODC</label>
                                        <span class="text-dark font-weight-bolder font-size-lg">
                                            ODP: <?= !empty($row['odp_no']) ? htmlspecialchars($row['odp_no']) : '-' ?> / 
                                            ODC: <?= !empty($row['odc_no']) ? htmlspecialchars($row['odc_no']) : '-' ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">Joint Closure No.</label>
                                        <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['jc_no']) ? htmlspecialchars($row['jc_no']) : '-' ?></span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">Enclosure / Splitter</label>
                                        <span class="text-dark font-weight-bolder font-size-lg"><?= !empty($row['enclosure']) ? htmlspecialchars($row['enclosure']) : '-' ?></span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">ODP / ODC Name</label>
                                        <span class="text-dark font-weight-bolder font-size-lg">
                                            ODP: <?= !empty($row['odp']) ? htmlspecialchars($row['odp']) : '-' ?> / 
                                            ODC: <?= !empty($row['odc']) ? htmlspecialchars($row['odc']) : '-' ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="text-muted font-weight-bold d-block">MAC Address (Sebelum / Sesudah)</label>
                                        <span class="text-dark font-weight-bolder font-size-sm">
                                            <?= !empty($row['mac_sebelum']) ? htmlspecialchars($row['mac_sebelum']) : '-' ?> / 
                                            <?= !empty($row['mac_sesudah']) ? htmlspecialchars($row['mac_sesudah']) : '-' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>