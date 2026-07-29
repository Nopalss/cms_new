<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
$_SESSION['menu'] = 'queue';
$_SESSION['table'] = 'queue';

// KPI awal server-side (default: hari ini)
$kpi = ['total' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0,
        'type_install' => 0, 'type_maintenance' => 0, 'type_dismantle' => 0, 'type_service' => 0];

try {
    $today = date('Y-m-d');
    $sumStmt = $pdo->prepare(
        "SELECT
            SUM(1) AS total,
            SUM(CASE WHEN status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN type_queue = 'Install'     THEN 1 ELSE 0 END) AS type_install,
            SUM(CASE WHEN type_queue = 'Maintenance' THEN 1 ELSE 0 END) AS type_maintenance,
            SUM(CASE WHEN type_queue = 'Dismantle'   THEN 1 ELSE 0 END) AS type_dismantle,
            SUM(CASE WHEN type_queue = 'Service'     THEN 1 ELSE 0 END) AS type_service
         FROM queue_scheduling
         WHERE DATE(created_at) = :today"
    );
    $sumStmt->execute([':today' => $today]);
    $row = $sumStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($kpi as $k => $_) {
            $kpi[$k] = (int) ($row[$k] ?? 0);
        }
    }
} catch (PDOException $e) {
    // silent fail
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

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
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 1rem;
    }
    .kpi-grid-2 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 991.98px) {
        .kpi-grid, .kpi-grid-2 { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575.98px) {
        .kpi-grid, .kpi-grid-2 { grid-template-columns: 1fr; }
    }

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
    .kpi-card.active-filter { box-shadow: 0 0 0 2px rgba(59,130,246,.35); }

    /* Status KPIs */
    .kpi-card.kpi-total    { border-left-color: #3B82F6; }
    .kpi-card.kpi-pending  { border-left-color: #F59E0B; }
    .kpi-card.kpi-accepted { border-left-color: #10B981; }
    .kpi-card.kpi-rejected { border-left-color: #EF4444; }
    /* Type KPIs */
    .kpi-card.kpi-install     { border-left-color: #6366F1; }
    .kpi-card.kpi-maintenance { border-left-color: #F59E0B; }
    .kpi-card.kpi-dismantle   { border-left-color: #EF4444; }
    .kpi-card.kpi-service     { border-left-color: #06B6D4; }

    .kpi-icon-wrap {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .kpi-total     .kpi-icon-wrap { background: #EFF6FF; color: #3B82F6; }
    .kpi-pending   .kpi-icon-wrap { background: #FFFBEB; color: #F59E0B; }
    .kpi-accepted  .kpi-icon-wrap { background: #ECFDF5; color: #10B981; }
    .kpi-rejected  .kpi-icon-wrap { background: #FEF2F2; color: #EF4444; }
    .kpi-install   .kpi-icon-wrap { background: #EEF2FF; color: #6366F1; }
    .kpi-maintenance .kpi-icon-wrap { background: #FFFBEB; color: #F59E0B; }
    .kpi-dismantle .kpi-icon-wrap { background: #FEF2F2; color: #EF4444; }
    .kpi-service   .kpi-icon-wrap { background: #ECFEFF; color: #06B6D4; }

    .kpi-section-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }
    .kpi-label { font-size: 0.78rem; color: #64748b; margin-bottom: 2px; }
    .kpi-value { font-size: 1.6rem; font-weight: 700; color: #0f172a; line-height: 1; }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <!--begin::Page Header-->
            <div class="registrasi-page-header">
                <div>
                    <h1>Queue Scheduling</h1>
                    <p>Kelola antrian penjadwalan teknisi.</p>
                </div>
                <div class="header-actions">
                    <!-- <a href="<?= BASE_URL ?>pages/queue/export_excel.php" class="btn btn-outline-success font-weight-bolder">
                        <span class="svg-icon svg-icon-md text-center">
                            <i class="far fa-file-excel"></i>
                        </span> Export Excel
                    </a> -->
                    <button type="button" class="btn btn-success font-weight-bolder" id="btn-issues" data-toggle="modal" data-target="#exampleModalScrollable">
                        <i class="flaticon2-warning mr-1"></i> Schedule Now
                        <small id="scheduleNow" class="ml-2 label label-danger" style="display:none;"></small>
                    </button>
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

            <!--begin::KPI Status-->
            <div class="kpi-section-label">Status Queue</div>
            <div class="kpi-grid">
                <div class="kpi-card kpi-total" id="kpi-card-total">
                    <div class="kpi-icon-wrap"><i class="flaticon2-layers-2"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Total Queue</div>
                        <div class="kpi-value" id="kpi-value-total"><?= $kpi['total'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-pending" id="kpi-card-pending">
                    <div class="kpi-icon-wrap"><i class="flaticon2-time"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Pending</div>
                        <div class="kpi-value" id="kpi-value-pending"><?= $kpi['pending'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-accepted" id="kpi-card-accepted">
                    <div class="kpi-icon-wrap"><i class="flaticon2-check-mark"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Accepted</div>
                        <div class="kpi-value" id="kpi-value-accepted"><?= $kpi['accepted'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-rejected" id="kpi-card-rejected">
                    <div class="kpi-icon-wrap"><i class="flaticon2-delete"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Rejected</div>
                        <div class="kpi-value" id="kpi-value-rejected"><?= $kpi['rejected'] ?></div>
                    </div>
                </div>
            </div>
            <!--end::KPI Status-->

            <!--begin::KPI Type-->
            <div class="kpi-section-label">Jenis Queue</div>
            <div class="kpi-grid-2">
                <div class="kpi-card kpi-install" id="kpi-card-install">
                    <div class="kpi-icon-wrap"><i class="flaticon2-add"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Install</div>
                        <div class="kpi-value" id="kpi-value-install"><?= $kpi['type_install'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-maintenance" id="kpi-card-service">
                    <div class="kpi-icon-wrap"><i class="flaticon-settings"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Service</div>
                        <div class="kpi-value" id="kpi-value-service"><?= $kpi['type_service'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-dismantle" id="kpi-card-dismantle">
                    <div class="kpi-icon-wrap"><i class="flaticon-delete"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Dismantle</div>
                        <div class="kpi-value" id="kpi-value-dismantle"><?= $kpi['type_dismantle'] ?></div>
                    </div>
                </div>
               
            </div>
            <!--end::KPI Type-->

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">Data Queue Scheduling</h3>
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
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Type:</label>
                                            <select class="form-control" id="kt_datatable_search_type">
                                                <option value="">All</option>
                                                <option value="Install">Install</option>
                                                <option value="Maintenance">Maintenance</option>
                                                <option value="Dismantle">Dismantle</option>
                                                <option value="Service">Service</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Status:</label>
                                            <select class="form-control" id="kt_datatable_search_status">
                                                <option value="">All</option>
                                                <option value="Accepted">Accepted</option>
                                                <option value="Rejected">Rejected</option>
                                                <option value="Pending">Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Search Form-->

                    <!--begin: Datatable-->
                    <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
                    <!--end: Datatable-->
                </div>
            </div>
            <!--end::Card-->

        </div>
    </div>
</div>

<!-- Modal Schedule Now -->
<div class="modal fade" id="exampleModalScrollable" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Now</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body" style="height: 300px;">
                <ul class="nav nav-light-success nav-bold nav-pills">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#kt_tab_pane_4_1"><span class="nav-text">All</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#kt_tab_pane_4_2"><span class="nav-text">Instalasi</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#kt_tab_pane_4_3"><span class="nav-text">Service</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#kt_tab_pane_4_4"><span class="nav-text">Dismantle</span></a></li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="kt_tab_pane_4_1" role="tabpanel">
                        <div class="table-responsive-xl">
                            <table class="table text-sm">
                                <thead><tr><th>Queue ID</th><th>Type Queue</th><th>Request ID</th><th>Status</th><th>Created At</th><th>Action</th></tr></thead>
                                <tbody id="table-scheduleNowAll"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="kt_tab_pane_4_2" role="tabpanel">
                        <div class="table-responsive-xl">
                            <table class="table text-sm">
                                <thead><tr><th>Queue ID</th><th>Type Queue</th><th>Request ID</th><th>Status</th><th>Created At</th><th>Action</th></tr></thead>
                                <tbody id="table-scheduleNowInstall"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="kt_tab_pane_4_3" role="tabpanel">
                        <div class="table-responsive-xl">
                            <table class="table text-sm">
                                <thead><tr><th>Queue ID</th><th>Type Queue</th><th>Request ID</th><th>Status</th><th>Created At</th><th>Action</th></tr></thead>
                                <tbody id="table-scheduleNowMaintenance"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="kt_tab_pane_4_4" role="tabpanel">
                        <div class="table-responsive-xl">
                            <table class="table text-sm">
                                <thead><tr><th>Queue ID</th><th>Type Queue</th><th>Request ID</th><th>Status</th><th>Created At</th><th>Action</th></tr></thead>
                                <tbody id="table-scheduleNowDismantle"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>