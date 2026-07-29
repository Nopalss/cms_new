<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'service';

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['alert'] = [
        'icon'   => 'warning',
        'title'  => 'ID Tidak Valid',
        'text'   => 'Dismantle Report ID tidak ditemukan.',
        'button' => 'Kembali',
        'style'  => 'warning'
    ];
    redirect("pages/service_report/");
}

try {
    $sql = "
        SELECT 
            srv.*,
            c.*,
            s.start_time,
            s.end_time,
            SEC_TO_TIME(TIMESTAMPDIFF(SECOND,s.start_time,s.end_time)) AS durasi
        FROM service_reports srv
        JOIN customers c ON srv.netpay_id = c.netpay_id
        LEFT JOIN schedules s ON srv.schedule_id = s.schedule_id
        WHERE srv.srv_id = :srv_key
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':srv_key' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) throw new Exception();

    $sql = "
        SELECT 
            t.name,
            srv.srv_id
        FROM service_report_pic srp
        JOIN service_reports srv ON srp.srv_id = srv.srv_id
        LEFT JOIN technician t ON srp.tech_id = t.tech_id
        WHERE srp.srv_id = :srv_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':srv_id' => $id]);
    $pic = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $picNames   = array_filter(array_column($pic, 'name'));
    $picDisplay = $picNames ? implode(', ', $picNames) : '-';
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
    // redirect("pages/service_report/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
// require __DIR__ . '/../../includes/navbar.php';

$start = $row['start_time'] ? date('H:i:s', strtotime($row['start_time'])) : '-';
$end   = $row['end_time']   ? date('H:i:s', strtotime($row['end_time']))   : '-';
$dur   = $row['durasi'] ?? '-';

$customerName   = htmlspecialchars($row['name'] ?? '');
$initials       = '';
foreach (explode(' ', trim($customerName)) as $word) {
    $initials .= strtoupper(mb_substr($word, 0, 1));
    if (strlen($initials) >= 2) break;
}
$initials = $initials ?: '??';

$statusVal   = strtolower($row['is_active'] ?? '');
$statusClass = ($statusVal === 'active' || $statusVal === 'aktif') ? 'pill-active' : 'pill-inactive';
$statusLabel = htmlspecialchars($row['is_active'] ?? '-');
?>

<style>
    /* ============================================================
   SERVICE REPORT DETAIL — Custom styles (Bootstrap 4 base)
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

    /* --- Stat cards (top row) --- */
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
        background: #edf9f0;
        border-color: #b7e4c7;
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

    .icon-green {
        background: #edf9f0;
        color: #276749;
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
        color: #276749;
    }

    /* --- Info cards --- */
    .sr-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        overflow: hidden;
        height: 100%;
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

    .head-purple {
        background: #ede9fe;
        color: #6d28d9;
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
        min-width: 135px;
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
            min-width: 110px;
        }
    }
</style>
<!--begin::Wrapper-->
<div class="d-flex flex-column flex-row-fluid wrapper pt-20" id="kt_wrapper">
    <!--begin::Header-->
    <div id="kt_header" class="header  header-fixed ">
        <!--begin::Container-->
        <div class=" container-fluid  d-flex align-items-stretch justify-content-end">
            <!--begin::Header Menu Wrapper-->
            <!--end::Header Menu Wrapper-->
            <!--begin::Topbar-->
            <div class="topbar">
                <!--begin::User-->
                <div class="topbar-item">
                    <div class="btn btn-icon btn-icon-mobile w-auto btn-clean d-flex align-items-center btn-lg px-2" id="kt_quick_user_toggle">
                        <span class="text-muted font-weight-bold font-size-base d-none d-md-inline mr-1">Hi,</span>
                        <span class="text-dark-50 font-weight-bolder font-size-base d-none d-md-inline mr-3"><?= $_SESSION['username'] ?></span>
                        <span class="symbol symbol-lg-35 symbol-25 symbol-light-success">
                            <span class="symbol-label font-size-h5 font-weight-bold">S</span>
                        </span>
                    </div>
                </div>
                <!--end::User-->
            </div>
            <!--end::Topbar-->
        </div>
        <!--end::Container-->
    </div>
    <div class="content d-flex flex-column-fluid">
        <div class="container py-6">

            <!-- ── Page Header ── -->
            <div class="mb-6">
                <h1 class="sr-page-title">Service Report Detail</h1>
                <span class="sr-badge-id">
                    <i class="fas fa-hashtag mr-1" style="font-size:10px;"></i>
                    <?= htmlspecialchars($row['srv_id'] ?? '') ?>
                </span>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="row mb-6">

                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="sr-stat-card">
                        <div class="sr-icon-wrap icon-blue mx-auto">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="sr-stat-label">Tanggal</div>
                        <p class="sr-stat-value" style="font-size:.95rem;">
                            <?= date("d M Y", strtotime($row['tanggal'])) ?>
                        </p>
                    </div>
                </div>

                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="sr-stat-card">
                        <div class="sr-icon-wrap icon-amber mx-auto">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="sr-stat-label">Start Time</div>
                        <p class="sr-stat-value"><?= htmlspecialchars($start) ?></p>
                    </div>
                </div>

                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="sr-stat-card">
                        <div class="sr-icon-wrap icon-amber mx-auto">
                            <i class="fas fa-stop-circle"></i>
                        </div>
                        <div class="sr-stat-label">End Time</div>
                        <p class="sr-stat-value"><?= htmlspecialchars($end) ?></p>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="sr-stat-card accent">
                        <div class="sr-icon-wrap icon-green mx-auto">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="sr-stat-label">Durasi Kerja</div>
                        <p class="sr-stat-value"><?= htmlspecialchars($dur) ?></p>
                    </div>
                </div>

            </div><!-- /Stat Cards -->

            <!-- ── Main Info Grid ── -->
            <div class="row">

                <!-- Service Information -->
                <div class="col-md-6 mb-5">
                    <div class="sr-card">
                        <div class="sr-card-head">
                            <div class="sr-card-head-icon head-purple">
                                <i class="fas fa-tools"></i>
                            </div>
                            <span>Service Information</span>
                        </div>

                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-exclamation-circle text-danger"></i> Problem</div>
                            <div class="sr-value"><?= nl2br(htmlspecialchars($row['problem'] ?? '-')) ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-check-circle text-success"></i> Action</div>
                            <div class="sr-value"><?= nl2br(htmlspecialchars($row['action'] ?? '-')) ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-box text-secondary"></i> Part</div>
                            <div class="sr-value"><?= htmlspecialchars($row['part'] ?? '-') ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-wifi text-primary"></i>ONT Lama</div>
                            <div class="sr-value"><?= htmlspecialchars($row['ont_bef'] ?? '-') ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-wifi text-success"></i>ONT Baru</div>
                            <div class="sr-value"><?= htmlspecialchars($row['ont_aft'] ?? '-') ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-arrow-down text-warning"></i> Redaman Before</div>
                            <div class="sr-value"><?= htmlspecialchars($row['red_bef'] ?? '-') ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-arrow-up text-primary"></i> Redaman After</div>
                            <div class="sr-value"><?= htmlspecialchars($row['red_aft'] ?? '-') ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-user-check text-info"></i> PIC</div>
                            <div class="sr-value"><?= htmlspecialchars($picDisplay) ?></div>
                        </div>
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-sticky-note text-muted"></i> Keterangan</div>
                            <div class="sr-value"><?= nl2br(htmlspecialchars($row['keterangan'] ?? '-')) ?></div>
                        </div>
                    </div>
                </div><!-- /Service Information -->

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
                            <div class="sr-label"><i class="fas fa-wifi text-primary"></i> Paket</div>
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
                        <div class="sr-row">
                            <div class="sr-label"><i class="fas fa-home text-secondary"></i> Perumahan</div>
                            <div class="sr-value"><?= htmlspecialchars($row['perumahan'] ?? '-') ?></div>
                        </div>
                    </div>
                </div><!-- /Customer Information -->

            </div><!-- /Main Grid -->

            <!-- ── Footer Action ── -->
            <div class="d-flex justify-content-end mt-2">
                <a href="<?= BASE_URL ?>pages/service_report/" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Daftar
                </a>
            </div>

        </div><!-- /container -->
    </div>

    <?php require __DIR__ . '/../../includes/footer.php'; ?>