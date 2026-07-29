<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'ikr';

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = [
        'icon'   => 'warning',
        'title'  => 'Data tidak ditemukan',
        'text'   => 'Parameter ID tidak valid.',
        'button' => 'Oke',
        'style'  => 'warning'
    ];
    redirect("pages/ikr/");
}

try {

    $sql = "
        SELECT 
            ikr.*,
            IF(ikr.netpay_id IS NULL OR ikr.netpay_id = '' OR ikr.netpay_id = '-' OR ikr.netpay_id = 'NULL', '-', ikr.netpay_id) AS netpay_id,
            COALESCE(c.name, reg.name, '-') AS name,
            COALESCE(c.phone, reg.phone, '-') AS phone,
            COALESCE(c.paket_internet, reg.paket_internet, '-') AS paket_internet,
            COALESCE(c.location, reg.location, ikr.alamat, '-') AS location,
            c.is_active,
            s.start_time,
            s.end_time,
            SEC_TO_TIME(TIMESTAMPDIFF(SECOND,s.start_time,s.end_time)) AS durasi
        FROM ikr_report ikr
        LEFT JOIN customers c ON (ikr.netpay_id IS NOT NULL AND ikr.netpay_id != '-' AND ikr.netpay_id != 'NULL' AND ikr.netpay_id = c.netpay_id)
        LEFT JOIN schedules s ON ikr.schedule_id = s.schedule_id
        LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
        LEFT JOIN request_ikr ri ON q.queue_id = ri.queue_id
        LEFT JOIN register reg ON ri.registrasi_id = reg.registrasi_id
        WHERE ikr.ikr_id = :ikr_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ikr_id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) throw new Exception('Data tidak ditemukan');

    /*
    =====================================
    AMBIL DAFTAR PIC (TEKNISI) UNTUK IKR INI
    =====================================
    */
    $sql = "
        SELECT 
            t.name,
            ikr.ikr_id
        FROM ikr_report_pic irp
        JOIN ikr_report ikr ON irp.ikr_id = ikr.ikr_id
        LEFT JOIN technician t ON irp.tech_id = t.tech_id
        WHERE irp.ikr_id = :ikr_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ikr_id' => $id]);
    $pic = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $picNames   = array_filter(array_column($pic, 'name'));
    $picDisplay = $picNames ? implode(', ', $picNames) : '-';
} catch (Exception $e) {

    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops!',
        'text'   => $e->getMessage(),
        'button' => 'Kembali',
        'style'  => 'danger'
    ];
    redirect("pages/ikr/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$start = $row['start_time'] ? date('H:i:s', strtotime($row['start_time'])) : '-';
$end   = $row['end_time']   ? date('H:i:s', strtotime($row['end_time']))   : '-';
$dur   = $row['durasi'] ?? '-';

/* Avatar inisial nama pelanggan */
$customerName = htmlspecialchars($row['name'] ?? '');
$initials     = '';
foreach (explode(' ', trim($customerName)) as $word) {
    $initials .= strtoupper(mb_substr($word, 0, 1));
    if (strlen($initials) >= 2) break;
}
$initials = $initials ?: '??';

/* Status pill */
$statusVal   = strtolower($row['is_active'] ?? '');
$statusClass = ($statusVal === 'active' || $statusVal === 'aktif') ? 'pill-active' : 'pill-inactive';
$statusLabel = htmlspecialchars($row['is_active'] ?? '-');
?>

<style>
    /* ============================================================
   IKR REPORT DETAIL — Custom styles (Bootstrap 4 base)
   Disamakan dengan style Dismantle Report Detail
   ============================================================ */

    .sr-page-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e2a3a;
        margin-bottom: 2px;
    }

    .sr-badge-id {
        display: inline-block;
        background: #f4f6fa;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 11px;
        color: #718096;
        padding: 2px 12px;
        letter-spacing: 0.05em;
    }

    /* --- Stat cards --- */
    .sr-stat-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 1rem 1rem 0.75rem;
        text-align: center;
        transition: box-shadow .15s;
    }

    .sr-stat-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, .07);
    }

    .sr-stat-card.accent {
        background: #fff5f5;
        border-color: #fed7d7;
    }

    .sr-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        margin-bottom: 8px;
    }

    .icon-blue {
        background: #ebf4ff;
        color: #3182ce;
    }

    .icon-amber {
        background: #fefce8;
        color: #b45309;
    }

    .icon-red {
        background: #fff5f5;
        color: #c53030;
    }

    .sr-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #718096;
        margin-bottom: 4px;
    }

    .sr-stat-value {
        font-size: 1.15rem;
        font-weight: 600;
        color: #1e2a3a;
        margin: 0;
    }

    .sr-stat-card.accent .sr-stat-value {
        color: #c53030;
    }

    /* --- Info cards --- */
    .sr-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        overflow: hidden;
    }

    .sr-card-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: .8rem 1.2rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .sr-card-head-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .head-red {
        background: #fed7d7;
        color: #c53030;
    }

    .head-teal {
        background: #d1fae5;
        color: #065f46;
    }

    .sr-card-head span {
        font-size: .85rem;
        font-weight: 600;
        color: #374151;
    }

    /* --- Customer avatar row --- */
    .sr-customer-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: .9rem 1.2rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .sr-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #d1fae5;
        color: #065f46;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .sr-cust-name {
        font-size: .95rem;
        font-weight: 600;
        color: #1e2a3a;
        margin: 0;
        line-height: 1.3;
    }

    .sr-cust-id {
        font-size: 11px;
        color: #718096;
        margin: 0;
    }

    /* --- Detail rows --- */
    .sr-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: .65rem 1.2rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .sr-row:last-child {
        border-bottom: none;
    }

    .sr-label {
        min-width: 145px;
        font-size: 12px;
        color: #718096;
        padding-top: 2px;
        flex-shrink: 0;
        display: flex;
        align-items: flex-start;
        gap: 5px;
    }

    .sr-label i {
        margin-top: 1px;
        flex-shrink: 0;
    }

    .sr-value {
        font-size: .875rem;
        color: #1e2a3a;
        flex: 1;
        line-height: 1.55;
    }

    /* --- Status pills --- */
    .sr-pill {
        display: inline-block;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 12px;
    }

    .pill-active {
        background: #d1fae5;
        color: #065f46;
    }

    .pill-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    /* --- Back button --- */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .875rem;
        font-weight: 500;
        padding: 8px 20px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #374151;
        text-decoration: none;
        transition: background .15s, box-shadow .15s;
    }

    .btn-back:hover {
        background: #f8fafc;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        text-decoration: none;
        color: #1e2a3a;
    }

    .btn-back i {
        font-size: 16px;
    }

    /* Responsive */
    @media (max-width: 767px) {
        .sr-stat-value {
            font-size: 1rem;
        }

        .sr-label {
            min-width: 115px;
        }
    }
</style>

<div class="content d-flex flex-column-fluid">
    <div class="container py-6">

        <!-- ── Page Header ── -->
        <div class="mb-6">
            <h1 class="sr-page-title">IKR Report Detail</h1>
            <span class="sr-badge-id">
                <i class="fas fa-hashtag mr-1" style="font-size:10px;"></i>
                <?= htmlspecialchars($row['ikr_id'] ?? '') ?>
            </span>
        </div>

        <!-- ── Stat Cards ── -->
        <div class="row mb-6">

            <div class="col-6 col-md-4 mb-3 mb-md-0">
                <div class="sr-stat-card">
                    <div class="sr-icon-wrap icon-amber mx-auto">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="sr-stat-label">Start Time</div>
                    <p class="sr-stat-value"><?= htmlspecialchars($start) ?></p>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 mb-md-0">
                <div class="sr-stat-card">
                    <div class="sr-icon-wrap icon-amber mx-auto">
                        <i class="fas fa-stop-circle"></i>
                    </div>
                    <div class="sr-stat-label">End Time</div>
                    <p class="sr-stat-value"><?= htmlspecialchars($end) ?></p>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="sr-stat-card accent">
                    <div class="sr-icon-wrap icon-red mx-auto">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="sr-stat-label">Durasi Kerja</div>
                    <p class="sr-stat-value"><?= htmlspecialchars($dur) ?></p>
                </div>
            </div>

        </div><!-- /Stat Cards -->

        <!-- ── Main Info Grid ── -->
        <div class="row align-items-start">

            <!-- IKR Information -->
            <div class="col-md-6 mb-5">
                <div class="sr-card">
                    <div class="sr-card-head">
                        <div class="sr-card-head-icon head-red">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <span>IKR Information</span>
                    </div>


                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-map-marker-alt text-danger"></i> Alamat</div>
                        <div class="sr-value"><?= nl2br(htmlspecialchars($row['alamat'] ?? '-')) ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-map text-secondary"></i> RT / RW</div>
                        <div class="sr-value"><?= htmlspecialchars($row['rt'] ?? '-') ?> / <?= htmlspecialchars($row['rw'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-city text-secondary"></i> Desa</div>
                        <div class="sr-value"><?= htmlspecialchars($row['desa'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-building text-secondary"></i> Kecamatan</div>
                        <div class="sr-value"><?= htmlspecialchars($row['kec'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-landmark text-secondary"></i> Kabupaten</div>
                        <div class="sr-value"><?= htmlspecialchars($row['kab'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-barcode text-info"></i> SN</div>
                        <div class="sr-value"><?= htmlspecialchars($row['sn'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-wifi text-primary"></i> Type ONT</div>
                        <div class="sr-value"><?= htmlspecialchars($row['type_ont'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-signal text-warning"></i> Redaman</div>
                        <div class="sr-value"><?= htmlspecialchars($row['redaman'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-project-diagram text-secondary"></i> ODP / ODC</div>
                        <div class="sr-value"><?= htmlspecialchars($row['odp_no'] ?? '-') ?> / <?= htmlspecialchars($row['odc_no'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-network-wired text-muted"></i> MAC Before</div>
                        <div class="sr-value"><?= htmlspecialchars($row['mac_sebelum'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-network-wired text-muted"></i> MAC After</div>
                        <div class="sr-value"><?= htmlspecialchars($row['mac_sesudah'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-box text-secondary"></i> Enclosure</div>
                        <div class="sr-value"><?= htmlspecialchars($row['enclosure'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-user-check text-info"></i> PIC</div>
                        <div class="sr-value"><?= htmlspecialchars($picDisplay) ?></div>
                    </div>
                </div>
            </div><!-- /IKR Information -->

            <!-- Customer Information -->
            <div class="col-md-6 mb-5">
                <div class="sr-card">
                    <div class="sr-card-head">
                        <div class="sr-card-head-icon head-teal">
                            <i class="fas fa-user"></i>
                        </div>
                        <span>Customer Information</span>
                    </div>

                    <!-- Avatar row -->
                    <div class="sr-customer-row">
                        <div class="sr-avatar"><?= $initials ?></div>
                        <div>
                            <p class="sr-cust-name"><?= $customerName ?></p>
                            <p class="sr-cust-id"><?= htmlspecialchars($row['netpay_id'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-phone-alt text-success"></i> No. HP</div>
                        <div class="sr-value"><?= htmlspecialchars($row['phone'] ?? '-') ?></div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-wifi text-primary"></i> Paket Internet</div>
                        <div class="sr-value">
                            <strong><?= htmlspecialchars($row['paket_internet'] ?? '-') ?> Mbps</strong>
                        </div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-circle text-muted"></i> Status</div>
                        <div class="sr-value">
                            <span class="sr-pill <?= $statusClass ?>"><?= $statusLabel ?></span>
                        </div>
                    </div>
                    <div class="sr-row">
                        <div class="sr-label"><i class="fas fa-map-marker-alt text-danger"></i> Alamat</div>
                        <div class="sr-value"><?= nl2br(htmlspecialchars($row['location'] ?? '-')) ?></div>
                    </div>
                </div>
            </div><!-- /Customer Information -->

        </div><!-- /Main Grid -->

        <!-- ── Footer Action ── -->
        <div class="d-flex justify-content-end mt-2">
            <a href="<?= BASE_URL ?>pages/ikr/" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Daftar
            </a>
        </div>

    </div><!-- /container -->
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>