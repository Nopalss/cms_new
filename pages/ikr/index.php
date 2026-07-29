<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
$_SESSION['menu'] = 'ikr';
$_SESSION['table'] = 'ikr';

// KPI awal server-side (default: hari ini)
$kpi = ['total' => 0, 'popular_package' => '-', 'popular_package_count' => 0];

try {
    $today = date('Y-m-d');
    $sumStmt = $pdo->prepare(
        "SELECT COUNT(1) AS total
         FROM ikr_report i
         LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
         WHERE DATE(i.created_at) = :today"
    );

    $pkgStmt = $pdo->prepare(
        "SELECT c.paket_internet, COUNT(1) AS cnt
         FROM ikr_report i
         LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
         JOIN customers c ON i.netpay_id = c.netpay_id
         WHERE DATE(i.created_at) = :today
         GROUP BY c.paket_internet
         ORDER BY cnt DESC
         LIMIT 1"
    );

    if ($_SESSION['role'] == 'teknisi') {
        $sumStmt = $pdo->prepare(
            "SELECT COUNT(1) AS total
             FROM ikr_report i
             LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
             WHERE DATE(i.created_at) = :today AND irp.tech_id = :pic"
        );
        $sumStmt->execute([':today' => $today, ':pic' => $_SESSION['id_karyawan']]);

        $pkgStmt = $pdo->prepare(
            "SELECT c.paket_internet, COUNT(1) AS cnt
             FROM ikr_report i
             LEFT JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
             JOIN customers c ON i.netpay_id = c.netpay_id
             WHERE DATE(i.created_at) = :today AND irp.tech_id = :pic
             GROUP BY c.paket_internet
             ORDER BY cnt DESC
             LIMIT 1"
        );
        $pkgStmt->execute([':today' => $today, ':pic' => $_SESSION['id_karyawan']]);
    } else {
        $sumStmt->execute([':today' => $today]);
        $pkgStmt->execute([':today' => $today]);
    }

    $row = $sumStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $kpi['total']  = (int) ($row['total'] ?? 0);
    }

    $rowPkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    if ($rowPkg) {
        $kpi['popular_package']       = $rowPkg['paket_internet'] ?? '-';
        $kpi['popular_package_count'] = (int) ($rowPkg['cnt'] ?? 0);
    }
} catch (PDOException $e) {
    // silent fail; JS akan refresh via AJAX
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/mobile-report.css">

<style>
    /* ── Page header ──────────────────────────────────────── */
    .registrasi-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 2.25rem;
        margin-bottom: 1.25rem;
    }
    .registrasi-page-header h1 {
        font-size: 1.65rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.15rem;
    }
    .registrasi-page-header p {
        font-size: 0.92rem;
        color: #64748b;
        margin-bottom: 0;
    }
    .registrasi-page-header .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    /* ── Filter Periode card ──────────────────────────────── */
    .periode-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .periode-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
    }
    .periode-btn-group .btn {
        border-radius: 8px !important;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.45rem 0.95rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        margin-right: 0.4rem;
        transition: background .15s, color .15s, border-color .15s;
    }
    .periode-btn-group .btn:last-child { margin-right: 0; }
    .periode-btn-group .btn.active { background: #3B82F6; border-color: #3B82F6; color: #fff; }
    .periode-custom-range { display: none; align-items: center; flex-wrap: wrap; gap: 0.6rem; }
    .periode-custom-range.show { display: flex; }
    .periode-custom-range label { font-size: 0.8rem; color: #64748b; margin: 0 0.35rem 0 0; }
    .periode-custom-range input[type="date"] { height: 38px; font-size: 0.85rem; }

    /* ── KPI cards ────────────────────────────────────────── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 575.98px) { .kpi-grid { grid-template-columns: 1fr; } }

    .kpi-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid;
        cursor: pointer;
        transition: box-shadow .15s, transform .15s;
    }
    .kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }
    .kpi-card.kpi-total   { border-left-color: #3B82F6; }
    .kpi-card.kpi-package { border-left-color: #10B981; }

    .kpi-icon-wrap {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .kpi-total   .kpi-icon-wrap { background: #EFF6FF; color: #3B82F6; }
    .kpi-package .kpi-icon-wrap { background: #ECFDF5; color: #10B981; }

    .kpi-label   { font-size: 0.78rem; color: #64748b; margin-bottom: 2px; }
    .kpi-value   { font-size: 1.6rem; font-weight: 700; color: #0f172a; line-height: 1; }
    .kpi-subtext { font-size: 0.76rem; color: #94a3b8; margin-top: 2px; }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <!--begin::Page Header-->
            <div class="registrasi-page-header">
                <div>
                    <h1>Data IKR Reports</h1>
                    <p>Kelola data laporan pemasangan IKR pelanggan.</p>
                </div>
                <div class="header-actions">
                    <!-- <a href="<?= BASE_URL ?>pages/ikr/export_excel.php" class="btn btn-outline-success font-weight-bolder">
                        <span class="svg-icon svg-icon-md text-center">
                            <i class="far fa-file-excel"></i>
                        </span> Export Excel
                    </a> -->
                </div>
            </div>
            <!--end::Page Header-->

            <!--begin::Filter Periode-->
            <div class="periode-card">
                <span class="periode-label">Periode</span>
                <div class="periode-btn-group" id="periode-btn-group">
                    <button type="button" class="btn " data-period="today">Hari Ini</button>
                    <button type="button" class="btn" data-period="week">Minggu Ini</button>
                    <button type="button" class="btn active" data-period="month">Bulan Ini</button>
                    <button type="button" class="btn" data-period="custom">Custom</button>
                </div>
                <div class="periode-custom-range" id="periode-custom-range">
                    <label>From</label>
                    <input type="date" class="form-control form-control-sm" id="periode-custom-from">
                    <label>To</label>
                    <input type="date" class="form-control form-control-sm" id="periode-custom-to">
                    <button type="button" class="btn btn-primary btn-sm" id="periode-custom-apply">Terapkan</button>
                    <button type="button" class="btn btn-light btn-sm" id="periode-custom-reset">Reset</button>
                </div>
            </div>
            <!--end::Filter Periode-->

            <!--begin::KPI-->
            <div class="kpi-grid">
                <div class="kpi-card kpi-total" id="kpi-card-total">
                    <div class="kpi-icon-wrap"><i class="flaticon2-document"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Total IKR</div>
                        <div class="kpi-value" id="kpi-value-total"><?= $kpi['total'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-package" id="kpi-card-package">
                    <div class="kpi-icon-wrap"><i class="flaticon2-box"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Paket Terpopuler</div>
                        <div class="kpi-value" id="kpi-value-package" style="font-size: 1.25rem; font-weight: 700;"><?= htmlspecialchars($kpi['popular_package']) ?></div>
                        <div class="kpi-subtext" id="kpi-subtext-package"><?= $kpi['popular_package_count'] ?> instalasi</div>
                    </div>
                </div>
            </div>
            <!--end::KPI-->

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">Data IKR Reports</h3>
                    </div>
                </div>
                <div class="card-body">
                    <!--begin::Search Form-->
                    <div class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-xl-12">
                                <div class="row align-items-center">
                                    <div class="col-md-4 my-2 my-md-0">
                                        <div class="input-icon">
                                            <input type="text" class="form-control" placeholder="Search..." id="kt_datatable_search_query"/>
                                            <span><i class="flaticon2-search-1 text-muted"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Search Form-->

                    <!--begin: Desktop table (>= md) -->
                    <div class="datatable datatable-bordered datatable-head-custom d-none d-md-block" id="kt_datatable"></div>
                    <!--end: Desktop table-->

                    <!--begin: Mobile card list (< md), pakai API yang sama-->
                    <div class="d-md-none" id="kt_mobile_list"></div>
                    <!--end: Mobile card list-->
                </div>
            </div>
            <!--end::Card-->

        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>