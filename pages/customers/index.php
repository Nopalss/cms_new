<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'customer';
$_SESSION['table'] = 'customer';

// ================= KPI INITIAL LOAD =================
$kpi = [
    'new_count' => 0,
    'total_active' => 0,
    'popular_package' => '-',
    'popular_package_count' => 0
];

try {
    // 1. Total customers (all time default)
    $newStmt = $pdo->query("SELECT COUNT(1) AS cnt FROM customers");
    $kpi['new_count'] = (int) ($newStmt->fetchColumn() ?: 0);

    // 2. Total active customers overall
    $activeStmt = $pdo->query("SELECT COUNT(1) AS cnt FROM customers WHERE LOWER(is_active) = 'active'");
    $kpi['total_active'] = (int) ($activeStmt->fetchColumn() ?: 0);

    // 3. Most popular package overall
    $pkgStmt = $pdo->query("
        SELECT paket_internet, COUNT(1) AS cnt
        FROM customers
        GROUP BY paket_internet
        ORDER BY cnt DESC
        LIMIT 1
    ");
    $pkgRow = $pkgStmt->fetch(PDO::FETCH_ASSOC);
    if ($pkgRow) {
        $kpi['popular_package'] = $pkgRow['paket_internet'] . ' Mbps';
        $kpi['popular_package_count'] = (int) $pkgRow['cnt'];
    }
} catch (PDOException $e) {
    // silent fail
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$paketInternet = [
    "5"   => "5 mbps - 150rb/bln",
    "10"  => "10 mbps - 300rb/bln",
    "30"  => "30 mbps - 650rb/bln",
    "50"  => "50 mbps - 850rb/bln",
    "100" => "100 mbps - 1jt/bln"
];
?>

<style>
    /* ── Page Header Styles ── */
    .customer-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .customer-page-header h1 {
        font-size: 1.65rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.15rem;
    }
    .customer-page-header p {
        font-size: 0.92rem;
        color: #64748b;
        margin-bottom: 0;
    }
    .customer-page-header .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    /* ── Filter Periode card ── */
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

    /* ── KPI cards ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 767.98px) { .kpi-grid { grid-template-columns: 1fr; } }

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
    .kpi-card.kpi-new     { border-left-color: #3B82F6; }
    .kpi-card.kpi-active  { border-left-color: #10B981; }
    .kpi-card.kpi-package { border-left-color: #8B5CF6; }

    .kpi-icon-wrap {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .kpi-new     .kpi-icon-wrap { background: #EFF6FF; color: #3B82F6; }
    .kpi-active  .kpi-icon-wrap { background: #ECFDF5; color: #10B981; }
    .kpi-package .kpi-icon-wrap { background: #F5F3FF; color: #8B5CF6; }

    .kpi-label   { font-size: 0.78rem; color: #64748b; margin-bottom: 2px; }
    .kpi-value   { font-size: 1.6rem; font-weight: 700; color: #0f172a; line-height: 1; }
    .kpi-subtext { font-size: 0.76rem; color: #94a3b8; margin-top: 2px; }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <!--begin::Page Header-->
            <div class="customer-page-header">
                <div>
                    <h1>Data Customers</h1>
                    <p>Kelola data dan informasi paket internet pelanggan.</p>
                </div>
                <div class="header-actions">
                    <a href="<?= BASE_URL ?>pages/customers/export_excel.php" class="btn btn-light-success mr-2 font-weight-bolder">
                        <i class="far fa-file-excel"></i> Export Excel
                    </a>
                    <a href="<?= BASE_URL ?>pages/customers/create.php" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus"></i> Add Customer
                    </a>
                </div>
            </div>
            <!--end::Page Header-->

            <!--begin::Filter Periode-->
            <div class="periode-card">
                <span class="periode-label">Periode Terdaftar</span>
                <div class="periode-btn-group" id="periode-btn-group">
                    <button type="button" class="btn " data-period="all">Semua</button>
                    <button type="button" class="btn" data-period="today">Hari Ini</button>
                    <button type="button" class="btn" data-period="week">Minggu Ini</button>
                    <button type="button" class="btn active" data-period="month">Bulan Ini</button>
                    <button type="button" class="btn" data-period="custom">Custom</button>
                </div>
                <div class="periode-custom-range" id="periode-custom-range">
                    <label>From</label>
                    <input type="date" class="form-control form-control-sm" id="periode-custom-from">
                    <label>To</label>
                    <input type="date" class="form-control form-control-sm" id="periode-custom-to">
                    <button type="button" class="btn btn-primary btn-sm ml-2" id="periode-custom-apply">Apply</button>
                </div>
            </div>
            <!--end::Filter Periode-->

            <!--begin::KPI Grid-->
            <div class="kpi-grid">
                <!-- KPI 1: Baru Terdaftar -->
                <div class="kpi-card kpi-new">
                    <div class="kpi-icon-wrap">
                        <i class="flaticon-users"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Baru Terdaftar</div>
                        <div class="kpi-value" id="kpi-value-new"><?= $kpi['new_count'] ?></div>
                    </div>
                </div>

                <!-- KPI 2: Total Pelanggan Aktif -->
                <div class="kpi-card kpi-active">
                    <div class="kpi-icon-wrap">
                        <i class="flaticon2-check-mark"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Total Pelanggan Aktif</div>
                        <div class="kpi-value" id="kpi-value-active"><?= $kpi['total_active'] ?></div>
                    </div>
                </div>

                <!-- KPI 3: Paket Terbanyak -->
                <div class="kpi-card kpi-package">
                    <div class="kpi-icon-wrap">
                        <i class="flaticon-internet"></i>
                    </div>
                    <div>
                        <div class="kpi-label">Paket Terbanyak</div>
                        <div class="kpi-value" style="font-size: 1.15rem; font-weight: 800;" id="kpi-value-package"><?= htmlspecialchars($kpi['popular_package']) ?></div>
                        <div class="kpi-subtext" id="kpi-subtext-package"><?= $kpi['popular_package_count'] ?> pelanggan</div>
                    </div>
                </div>
            </div>
            <!--end::KPI Grid-->

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-body">
                    <!--begin::Search and Filters-->
                    <div class="mb-7">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-xl-12">
                                <div class="row align-items-center">
                                    <div class="col-md-4 my-2 my-md-0">
                                        <div class="input-icon">
                                            <input type="text" class="form-control" placeholder="Cari pelanggan..." id="kt_datatable_search_query" />
                                            <span><i class="flaticon2-search-1 text-muted"></i></span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Status:</label>
                                            <select class="form-control" id="kt_datatable_search_status">
                                                <option value="">Semua Status</option>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                                <option value="Dismantle">Dismantle</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Paket:</label>
                                            <select class="form-control" id="kt_datatable_search_paket">
                                                <option value="">Semua Paket</option>
                                                <?php foreach ($paketInternet as $key => $data): ?>
                                                    <option value="<?= $key ?>"><?= $key ?> Mbps</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Search and Filters-->

                    <!--begin: Datatable-->
                    <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
                    <!--end: Datatable-->
                </div>
            </div>
            <!--end::Card-->

        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>