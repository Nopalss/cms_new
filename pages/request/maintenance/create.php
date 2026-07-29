<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/checkRowExist.php';
require_once __DIR__ . '/../../../helper/generateId.php';

$_SESSION['menu'] = 'request maintenance';

$id = isset($_POST['id']) ? trim($_POST['id']) : null;
$rm_id = "";

$q = "SELECT * FROM type WHERE type = 'rm'";
$stmt = $pdo->prepare($q);
$stmt->execute();
$type = $stmt->fetchAll(PDO::FETCH_ASSOC);

$row = [
    "netpay_id"      => '',
    "netpay_key"     => '',
    "name"           => '',
    "phone"          => '',
    "paket_internet" => '',
    "is_active"      => '',
    "perumahan"      => '',
    "location"       => '',
    "sharelock"      => ''
];

require_once __DIR__ . '/../../../helper/getCustomerData.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($id) {
            $row = getCustomerData($id);
            checkRowExist($row, "pages/request/maintenance/create.php");
            $rm_id = generateId('RM');
        }
        if (!$id) {
            $errorMessage = "Netpay ID tidak boleh kosong";
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

$hasCustomer = !empty($row['netpay_id']);

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';
?>

<style>
    /* ── Minimal custom overrides ── */
    :root {
        --primary: #3B82F6;
        --primary-dark: #1D4ED8;
        --navy: #0F172A;
        --slate: #64748B;
        --emerald: #10B981;
        --bg: #F1F5F9;
        --card-radius: 14px;
    }

    #kt_content {
        background: var(--bg);
    }

    .page-heading {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--navy);
        letter-spacing: -.3px;
    }

    .page-sub {
        font-size: .82rem;
        color: var(--slate);
    }

    /* Card */
    .rs-card {
        border: none;
        border-radius: var(--card-radius);
        box-shadow: 0 1px 4px rgba(15, 23, 42, .07), 0 4px 16px rgba(15, 23, 42, .05);
        background: #fff;
    }

    .rs-card .card-header {
        background: none;
        border-bottom: 1px solid #E2E8F0;
        padding: 1.15rem 1.5rem;
        border-radius: var(--card-radius) var(--card-radius) 0 0;
    }

    .rs-card .card-header .card-title {
        font-size: .9rem;
        font-weight: 700;
        color: var(--navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .rs-card .card-header .card-title i {
        color: var(--primary);
        font-size: 1rem;
    }

    .rs-card .card-body {
        padding: 1.5rem;
    }

    .rs-card .card-footer {
        background: #F8FAFC;
        border-top: 1px solid #E2E8F0;
        border-radius: 0 0 var(--card-radius) var(--card-radius);
        padding: 1rem 1.5rem;
    }

    /* Search bar */
    .search-wrap .form-control {
        border-right: none;
        border-radius: 8px 0 0 8px !important;
        height: 44px;
        font-size: .875rem;
        border-color: #CBD5E1;
    }

    .search-wrap .form-control:focus {
        border-color: var(--primary);
        box-shadow: none;
    }

    .search-wrap .btn-search {
        border-radius: 0 8px 8px 0 !important;
        background: var(--primary);
        color: #fff;
        border: 1px solid var(--primary);
        height: 44px;
        padding: 0 1rem;
        font-size: .875rem;
        transition: background .18s;
    }

    .search-wrap .btn-search:hover {
        background: var(--primary-dark);
    }

    /* Form controls */
    .rs-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: .35rem;
    }

    .rs-input {
        height: 44px;
        border-radius: 8px !important;
        border-color: #CBD5E1;
        font-size: .875rem;
        color: var(--navy);
    }

    .rs-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .rs-select {
        border-radius: 8px !important;
        border-color: #CBD5E1;
        font-size: .875rem;
        color: var(--navy);
    }

    .rs-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    textarea.rs-input {
        height: auto;
        resize: vertical;
    }

    /* ID badge pill */
    .id-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: #EFF6FF;
        color: var(--primary-dark);
        border-radius: 8px;
        padding: .6rem 1rem;
        font-size: .88rem;
        font-weight: 700;
        letter-spacing: .2px;
        border: 1px solid #BFDBFE;
        width: 100%;
    }

    .id-badge span {
        color: var(--slate);
        font-weight: 400;
        font-size: .78rem;
    }

    /* Customer card info rows */
    .info-row {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px solid #F1F5F9;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #F1F5F9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        flex-shrink: 0;
        font-size: .8rem;
    }

    .info-label {
        font-size: .72rem;
        color: var(--slate);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        line-height: 1;
        margin-bottom: .18rem;
    }

    .info-value {
        font-size: .875rem;
        color: var(--navy);
        font-weight: 500;
        line-height: 1.3;
        word-break: break-word;
    }

    .info-value.empty {
        color: #CBD5E1;
        font-style: italic;
        font-weight: 400;
    }

    /* Status badge */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .22rem .7rem;
        border-radius: 99px;
        font-size: .75rem;
        font-weight: 600;
    }

    .status-active {
        background: #D1FAE5;
        color: #065F46;
    }

    .status-inactive {
        background: #FEE2E2;
        color: #991B1B;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--slate);
    }

    .empty-state i {
        font-size: 2.5rem;
        color: #CBD5E1;
        margin-bottom: .75rem;
    }

    .empty-state p {
        font-size: .85rem;
        margin: 0;
    }

    /* Buttons */
    .btn-rs-primary {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .6rem 1.4rem;
        font-size: .875rem;
        font-weight: 600;
        transition: background .18s, transform .1s;
    }

    .btn-rs-primary:hover {
        background: var(--primary-dark);
        color: #fff;
    }

    .btn-rs-primary:active {
        transform: scale(.98);
    }

    .btn-rs-cancel {
        background: #F1F5F9;
        color: var(--slate);
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: .6rem 1.2rem;
        font-size: .875rem;
        font-weight: 600;
        transition: background .18s;
    }

    .btn-rs-cancel:hover {
        background: #ff0000;
        color: #E2E8F0;
        text-decoration: none;
    }

    /* Step indicator */
    .step-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .step-title {
        font-size: .82rem;
        font-weight: 700;
        color: var(--navy);
    }

    /* Divider */
    .step-divider {
        border: none;
        border-top: 1px dashed #E2E8F0;
        margin: 1.25rem 0;
    }

    @media (max-width: 767px) {
        .rs-card .card-body {
            padding: 1.1rem;
        }
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid py-8">
        <div class="container">

            <!-- Page Header -->
            <div class="mb-6 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="page-heading">Buat Request Servis</div>
                    <div class="page-sub mt-1">Isi form di bawah untuk membuat tiket maintenance baru</div>
                </div>
                <a href="<?= BASE_URL ?>pages/request/maintenance" class="btn-rs-cancel" style="text-decoration:none;">
                    <i class="flaticon2-left-arrow-1 mr-1" style="font-size:.75rem;"></i> Kembali
                </a>
            </div>

            <div class="row">

                <!-- ── Left: Form ── -->
                <div class="col-lg-6 mb-6">
                    <div class="rs-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="flaticon2-add-1"></i> Form Request Servis
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Step 1: Cari Customer -->
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="step-badge">1</div>
                                <div class="step-title ml-2">Cari Customer</div>
                            </div>
                            <form method="post" class="form mb-0">
                                <div class="form-group mb-0">
                                    <label class="rs-label">Netpay ID</label>
                                    <div class="input-group search-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="Masukkan Netpay ID..."
                                            name="id"
                                            autocomplete="off"
                                            value="<?= htmlspecialchars($row['netpay_id']) ?>">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn-search">
                                                <i class="flaticon-search"></i> Cari
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <hr class="step-divider">

                            <!-- Step 2: Detail Servis -->
                            <form method="post" action="<?= BASE_URL ?>controllers/request/maintenance/create.php">
                                <!-- Hidden fields -->
                                <input type="hidden" name="rm_id" value="<?= htmlspecialchars($rm_id) ?>">
                                <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($row['netpay_id']) ?>">
                                <input type="hidden" name="netpay_key" value="<?= htmlspecialchars($row['netpay_key']) ?>">
                                <input type="hidden" name="name" value="<?= htmlspecialchars($row['name']) ?>">
                                <input type="hidden" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                                <input type="hidden" name="is_active" value="<?= htmlspecialchars($row['is_active']) ?>">
                                <input type="hidden" name="perumahan" value="<?= htmlspecialchars($row['perumahan']) ?>">
                                <input type="hidden" name="location" value="<?= htmlspecialchars($row['location']) ?>">
                                <input type="hidden" name="paket_internet" value="<?= htmlspecialchars($row['paket_internet']) ?>">

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="step-badge">2</div>
                                    <div class="step-title ml-2">Detail Servis</div>
                                </div>

                                <!-- Request Service ID -->
                                <div class="form-group">
                                    <label class="rs-label">ID Request Servis</label>
                                    <?php if ($rm_id): ?>
                                        <div class="id-badge">
                                            <i class="flaticon2-tag" style="font-size:.9rem;"></i>
                                            <?= htmlspecialchars($rm_id) ?>
                                            <span class="ml-1">— digenerate otomatis</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="id-badge" style="background:#F8FAFC; color:#94A3B8; border-color:#E2E8F0;">
                                            <i class="flaticon2-tag" style="font-size:.9rem;"></i>
                                            <span>Akan digenerate setelah customer dipilih</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Share Location -->
                                <div class="form-group">
                                    <label class="rs-label">Share Location (Google Maps)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" style="border-radius:8px 0 0 8px; border-color:#CBD5E1; background:#F8FAFC; color:var(--slate);">
                                                <i class="flaticon2-location" style="font-size:.85rem;"></i>
                                            </span>
                                        </div>
                                        <input
                                            type="text"
                                            class="form-control rs-input"
                                            name="sharelock"
                                            placeholder="Paste link Google Maps..."
                                            value="<?= htmlspecialchars($row['sharelock']) ?>"
                                            style="border-radius:0 8px 8px 0 !important;">
                                    </div>
                                </div>

                                <!-- No Kontak -->
                                <div class="form-group">
                                    <label class="rs-label">No. yang Menghubungi</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" style="border-radius:8px 0 0 8px; border-color:#CBD5E1; background:#F8FAFC; color:var(--slate);">
                                                <i class="flaticon2-phone" style="font-size:.85rem;"></i>
                                            </span>
                                        </div>
                                        <input
                                            type="text"
                                            name="phone_contact"
                                            class="form-control rs-input"
                                            autocomplete="off"
                                            placeholder="08xxxxxxxxxx"
                                            value="<?= htmlspecialchars($row['phone']) ?>"
                                            style="border-radius:0 8px 8px 0 !important;">
                                    </div>
                                </div>

                                <!-- Type Issue -->
                                <div class="form-group">
                                    <label class="rs-label">Jenis Masalah</label>
                                    <select class="form-control rs-select" id="type_issue_select" name="type_issue" style="height:44px;" required>
                                        <option value="">— Pilih Jenis Masalah —</option>
                                        <?php foreach ($type as $t): ?>
                                            <option value="<?= htmlspecialchars($t['catatan']) ?>"><?= htmlspecialchars($t['catatan']) ?></option>
                                        <?php endforeach; ?>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <div class="form-group mt-3" id="type_issue_lainnya_group" style="display:none;">
                                        <label class="font-weight-bold text-dark-50 font-size-sm">Masalah Lainnya</label>
                                        <input type="text" class="form-control rs-input" id="type_issue_lainnya" placeholder="Masukkan jenis masalah lainnya" style="height:44px;">
                                    </div>
                                </div>

                                <!-- Deskripsi -->
                                <div class="form-group mb-0">
                                    <label class="rs-label">Deskripsi Masalah</label>
                                    <textarea
                                        class="form-control rs-input"
                                        name="deskripsi_issue"
                                        rows="3"
                                        placeholder="Jelaskan masalah yang dialami customer..."></textarea>
                                </div>

                                <!-- Footer Actions -->
                                <div class="d-flex align-items-center justify-content-end mt-4 gap-2 pt-3" style="border-top:1px solid #F1F5F9;">
                                    <a href="<?= BASE_URL ?>pages/request/maintenance" class="btn-rs-cancel">Batal</a>
                                    <button type="submit" name="submit" class="btn-rs-primary">
                                        <i class="flaticon2-check-mark mr-1" style="font-size:.8rem;"></i> Simpan Tiket
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <!-- ── Right: Customer Card ── -->
                <div class="col-lg-6 mb-6">
                    <div class="rs-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="flaticon2-user"></i> Data Customer
                            </div>
                            <?php if ($hasCustomer): ?>
                                <?php
                                $isActive = strtolower(trim($row['is_active']));
                                $active = in_array($isActive, ['1', 'yes', 'active', 'ya', 'aktif', 'true']);
                                ?>
                                <div class="ml-auto">
                                    <span class="status-pill <?= $active ? 'status-active' : 'status-inactive' ?>">
                                        <span class="status-dot"></span>
                                        <?= $active ? 'Aktif' : 'Tidak Aktif' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">

                            <?php if ($hasCustomer): ?>
                                <!-- Info rows -->
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-tag"></i></div>
                                    <div>
                                        <div class="info-label">Netpay ID</div>
                                        <div class="info-value"><?= htmlspecialchars($row['netpay_id']) ?></div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-user"></i></div>
                                    <div>
                                        <div class="info-label">Nama</div>
                                        <div class="info-value"><?= $row['name'] ? htmlspecialchars($row['name']) : '<span class="empty">—</span>' ?></div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-phone"></i></div>
                                    <div>
                                        <div class="info-label">No. HP</div>
                                        <div class="info-value"><?= $row['phone'] ? htmlspecialchars($row['phone']) : '<span class="empty">—</span>' ?></div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-wifi"></i></div>
                                    <div>
                                        <div class="info-label">Paket Internet</div>
                                        <div class="info-value"><?= $row['paket_internet'] ? htmlspecialchars($row['paket_internet']) : '<span class="empty">—</span>' ?></div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-architecture-and-city"></i></div>
                                    <div>
                                        <div class="info-label">Perumahan</div>
                                        <div class="info-value"><?= $row['perumahan'] ? htmlspecialchars($row['perumahan']) : '<span class="empty">—</span>' ?></div>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-map"></i></div>
                                    <div>
                                        <div class="info-label">Alamat</div>
                                        <div class="info-value"><?= $row['location'] ? htmlspecialchars($row['location']) : '<span class="empty">—</span>' ?></div>
                                    </div>
                                </div>
                                <?php if ($row['sharelock']): ?>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="flaticon2-location"></i></div>
                                        <div>
                                            <div class="info-label">Share Location</div>
                                            <div class="info-value">
                                                <a href="<?= htmlspecialchars($row['sharelock']) ?>" target="_blank" rel="noopener" style="color:var(--primary); font-size:.82rem; word-break:break-all;">
                                                    <?= htmlspecialchars($row['sharelock']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <!-- Empty state -->
                                <div class="empty-state">
                                    <div><i class="flaticon2-user"></i></div>
                                    <p>Data customer akan muncul di sini<br>setelah kamu mencari Netpay ID.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>

<script>
    $(function() {
        $('#type_issue_select').on('change', function() {
            if ($(this).val() === 'Lainnya') {
                $('#type_issue_lainnya_group').slideDown();
                $('#type_issue_lainnya')
                    .attr('name', 'type_issue')
                    .prop('required', true);
                $('#type_issue_select').removeAttr('name');
            } else {
                $('#type_issue_lainnya_group').slideUp();
                $('#type_issue_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');
                $('#type_issue_select').attr('name', 'type_issue');
            }
        });
    });
</script>

<?php if (!empty($errorMessage)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: <?= json_encode($errorMessage) ?>,
            confirmButtonColor: '#3B82F6',
            borderRadius: '14px'
        });
    </script>
<?php endif; ?>