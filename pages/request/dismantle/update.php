<?php

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/checkRowExist.php';

$_SESSION['menu'] = 'request dismantle';

$q = "SELECT * FROM type WHERE type = 'rd'";
$stmt = $pdo->prepare($q);
$stmt->execute();
$type_dismantle = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rd_id = isset($_GET['id']) ? $_GET['id'] : null;

try {
    if (!$rd_id) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! ID Request Tidak Ditemukan',
            'text'   => 'Silakan coba lagi.',
            'button' => 'Coba Lagi',
            'style'  => 'danger'
        ];
        redirect("pages/request/dismantle/");
    }

    // 🐛 BUG FIX: JOIN kondisi salah (q.netpay_id = q.netpay_id) → diperbaiki ke c.netpay_id
    $sql = "SELECT rd.*, c.*
            FROM request_dismantle rd
            JOIN queue_scheduling q ON rd.queue_id = q.queue_id
            JOIN customers c ON q.netpay_id = c.netpay_id
            WHERE rd_id = :rd_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rd_id' => $rd_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    checkRowExist($row, "pages/request/dismantle/");
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'text'   => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => 'Coba Lagi',
        'style'  => 'danger'
    ];
    redirect("pages/request/dismantle/");
}

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';

$phone_contact = $row['phone_contact'] ?? $row['phone'];

$standardDismantle = array_map(function($t) { return $t['catatan']; }, $type_dismantle);
$isCustomDismantle = !empty($row['type_dismantle']) && !in_array($row['type_dismantle'], $standardDismantle);

$isActive = strtolower(trim($row['is_active'] ?? ''));
$active   = in_array($isActive, ['1', 'yes', 'active', 'ya', 'aktif', 'true']);
?>

<style>
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
        display: flex;
        align-items: center;
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

    /* Labels */
    .rs-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: .35rem;
    }

    /* Inputs */
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

    .rs-input:disabled,
    .rs-input[disabled] {
        background: #F8FAFC;
        color: var(--slate);
        cursor: not-allowed;
    }

    .rs-select {
        border-radius: 8px !important;
        border-color: #CBD5E1;
        font-size: .875rem;
        color: var(--navy);
        height: 44px;
    }

    .rs-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    textarea.rs-input {
        height: auto;
        resize: vertical;
    }

    /* ID badge */
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
        border: 1px solid #BFDBFE;
        width: 100%;
    }

    .id-badge .badge-sub {
        color: var(--slate);
        font-weight: 400;
        font-size: .78rem;
    }

    /* Readonly field */
    .readonly-field {
        display: flex;
        align-items: center;
        gap: .5rem;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: .6rem 1rem;
        font-size: .875rem;
        color: var(--slate);
        min-height: 44px;
    }

    .readonly-field i {
        color: #CBD5E1;
        font-size: .85rem;
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

    .step-divider {
        border: none;
        border-top: 1px dashed #E2E8F0;
        margin: 1.25rem 0;
    }

    /* Customer info rows */
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

    /* Status pill */
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
        cursor: pointer;
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
        text-decoration: none;
        display: inline-block;
    }

    .btn-rs-cancel:hover {
        background: #E2E8F0;
        color: var(--navy);
        text-decoration: none;
    }

    /* Input with icon prepend */
    .input-icon-wrap .input-group-text {
        border-radius: 8px 0 0 8px;
        border-color: #CBD5E1;
        background: #F8FAFC;
        color: var(--slate);
    }

    .input-icon-wrap .rs-input {
        border-radius: 0 8px 8px 0 !important;
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
            <div class="mb-6 d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <div class="page-heading">Update Request Dismantle</div>
                    <div class="page-sub mt-1">Edit detail tiket dismantle yang sudah ada</div>
                </div>
                <a href="<?= BASE_URL ?>pages/request/dismantle/" class="btn-rs-cancel mt-2 mt-md-0">
                    <i class="flaticon2-left-arrow-1 mr-1" style="font-size:.75rem;"></i> Kembali
                </a>
            </div>

            <form method="post" action="<?= BASE_URL ?>controllers/request/dismantle/update.php">
                <input type="hidden" name="rd_id" value="<?= htmlspecialchars($row['rd_id']) ?>">
                <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($row['netpay_id']) ?>">

                <div class="row">

                    <!-- ── Left: Form ── -->
                    <div class="col-lg-6 mb-6">
                        <div class="rs-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="flaticon2-edit"></i> Detail Tiket
                                </div>
                            </div>
                            <div class="card-body">

                                <!-- Step 1: Info Tiket -->
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="step-badge">1</div>
                                    <div class="step-title ml-2">Informasi Tiket</div>
                                </div>

                                <div class="form-group">
                                    <label class="rs-label">ID Request Dismantle</label>
                                    <div class="id-badge">
                                        <i class="flaticon2-tag" style="font-size:.9rem;"></i>
                                        <?= htmlspecialchars($row['rd_id']) ?>
                                        <span class="badge-sub ml-1">— tidak dapat diubah</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="rs-label">Netpay ID</label>
                                    <div class="readonly-field">
                                        <i class="flaticon2-user"></i>
                                        <?= htmlspecialchars($row['netpay_id']) ?>
                                    </div>
                                </div>

                                <hr class="step-divider">

                                <!-- Step 2: Detail Dismantle -->
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="step-badge">2</div>
                                    <div class="step-title ml-2">Detail Dismantle</div>
                                </div>

                                <!-- Share Location -->
                                <div class="form-group">
                                    <label class="rs-label">Share Location (Google Maps)</label>
                                    <div class="input-group input-icon-wrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="flaticon2-location" style="font-size:.85rem;"></i>
                                            </span>
                                        </div>
                                        <input
                                            type="text"
                                            class="form-control rs-input"
                                            name="sharelock"
                                            placeholder="Paste link Google Maps..."
                                            value="<?= htmlspecialchars($row['sharelock'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- No Kontak -->
                                <div class="form-group">
                                    <label class="rs-label">No. yang Menghubungi</label>
                                    <div class="input-group input-icon-wrap">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="flaticon2-phone" style="font-size:.85rem;"></i>
                                            </span>
                                        </div>
                                        <input
                                            type="text"
                                            name="phone_contact"
                                            class="form-control rs-input"
                                            autocomplete="off"
                                            placeholder="08xxxxxxxxxx"
                                            value="<?= htmlspecialchars($phone_contact) ?>">
                                    </div>
                                </div>

                                <!-- Type Dismantle -->
                                <div class="form-group">
                                     <label class="rs-label">Jenis Dismantle</label>
                                     <select class="form-control rs-select" id="type_dismantle_select" <?= !$isCustomDismantle ? 'name="type_dismantle"' : '' ?> style="height:44px;" required>
                                         <option value="">— Pilih Jenis Dismantle —</option>
                                         <?php foreach ($type_dismantle as $t): ?>
                                             <?php $selected = ($t['catatan'] == $row['type_dismantle']) ? 'selected' : '' ?>
                                             <option value="<?= htmlspecialchars($t['catatan']) ?>" <?= $selected ?>>
                                                 <?= htmlspecialchars($t['catatan']) ?>
                                             </option>
                                         <?php endforeach; ?>
                                         <option value="Lainnya" <?= $isCustomDismantle ? 'selected' : '' ?>>Lainnya</option>
                                     </select>
                                     <div class="form-group mt-3" id="type_dismantle_lainnya_group" style="<?= $isCustomDismantle ? '' : 'display:none;' ?>">
                                         <label class="font-weight-bold text-dark-50 font-size-sm">Dismantle Lainnya</label>
                                         <input type="text" class="form-control rs-input" id="type_dismantle_lainnya" <?= $isCustomDismantle ? 'name="type_dismantle"' : '' ?> value="<?= $isCustomDismantle ? htmlspecialchars($row['type_dismantle']) : '' ?>" placeholder="Masukkan jenis dismantle lainnya" style="height:44px;">
                                     </div>
                                 </div>

                                <!-- Deskripsi -->
                                <div class="form-group mb-0">
                                    <label class="rs-label">Deskripsi Dismantle</label>
                                    <textarea
                                        class="form-control rs-input"
                                        name="deskripsi_dismantle"
                                        rows="3"
                                        placeholder="Jelaskan alasan atau detail dismantle..."><?= htmlspecialchars($row['deskripsi_dismantle'] ?? '') ?></textarea>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex align-items-center justify-content-end mt-4 pt-3" style="border-top:1px solid #F1F5F9; gap:.75rem;">
                                    <a href="<?= BASE_URL ?>pages/request/dismantle/" class="btn-rs-cancel">Batal</a>
                                    <button type="submit" name="submit" class="btn-rs-primary">
                                        <i class="flaticon2-check-mark mr-1" style="font-size:.8rem;"></i> Simpan Perubahan
                                    </button>
                                </div>

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
                                <div class="ml-auto">
                                    <span class="status-pill <?= $active ? 'status-active' : 'status-inactive' ?>">
                                        <span class="status-dot"></span>
                                        <?= $active ? 'Aktif' : 'Tidak Aktif' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">

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
                                        <div class="info-value"><?= htmlspecialchars($row['name'] ?? '—') ?></div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-phone"></i></div>
                                    <div>
                                        <div class="info-label">No. HP</div>
                                        <div class="info-value"><?= htmlspecialchars($row['phone'] ?? '—') ?></div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-wifi"></i></div>
                                    <div>
                                        <div class="info-label">Paket Internet</div>
                                        <div class="info-value"><?= htmlspecialchars($row['paket_internet'] ?? '—') ?></div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-architecture-and-city"></i></div>
                                    <div>
                                        <div class="info-label">Perumahan</div>
                                        <div class="info-value"><?= htmlspecialchars($row['perumahan'] ?? '—') ?></div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon"><i class="flaticon2-map"></i></div>
                                    <div>
                                        <div class="info-label">Alamat</div>
                                        <div class="info-value"><?= htmlspecialchars($row['location'] ?? '—') ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($row['sharelock'])): ?>
                                    <div class="info-row">
                                        <div class="info-icon"><i class="flaticon2-location"></i></div>
                                        <div>
                                            <div class="info-label">Share Location</div>
                                            <div class="info-value">
                                                <a href="<?= htmlspecialchars($row['sharelock']) ?>" target="_blank" rel="noopener"
                                                    style="color:var(--primary); font-size:.82rem; word-break:break-all;">
                                                    <?= htmlspecialchars($row['sharelock']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>

<script>
    $(function() {
        $('#type_dismantle_select').on('change', function() {
            if ($(this).val() === 'Lainnya') {
                $('#type_dismantle_lainnya_group').slideDown();
                $('#type_dismantle_lainnya')
                    .attr('name', 'type_dismantle')
                    .prop('required', true);
                $('#type_dismantle_select').removeAttr('name');
            } else {
                $('#type_dismantle_lainnya_group').slideUp();
                $('#type_dismantle_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');
                $('#type_dismantle_select').attr('name', 'type_dismantle');
            }
        });
        // Initial state sync to ensure select vs input has the name attribute
        $('#type_dismantle_select').trigger('change');
    });
</script>