<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/redirect.php';
$_SESSION['menu'] = 'request ikr';
$_SESSION['table'] = 'request ikr';

// KPI awal server-side (default: hari ini)
$kpi = ['total' => 0, 'accepted' => 0, 'pending' => 0, 'rejected' => 0];

try {
    $today = date('Y-m-d');
    $dateRef = "COALESCE(q.created_at, r.updated_at)";
    $sumStmt = $pdo->prepare(
        "SELECT
            SUM(1) AS total,
            SUM(CASE WHEN q.status = 'Accepted' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN q.status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN q.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
         FROM request_ikr r
         LEFT JOIN queue_scheduling q ON r.queue_id = q.queue_id
         WHERE DATE($dateRef) = :today"
    );
    $sumStmt->execute([':today' => $today]);
    $row = $sumStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $kpi['total']    = (int) $row['total'];
        $kpi['accepted'] = (int) $row['accepted'];
        $kpi['pending']  = (int) $row['pending'];
        $kpi['rejected'] = (int) $row['rejected'];
    }
} catch (PDOException $e) {
    // silent fail; JS akan refresh via AJAX
}

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';
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
        margin-bottom: 1.5rem;
    }
    @media (max-width: 991.98px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
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
    .kpi-card.active-filter { box-shadow: 0 0 0 2px rgba(59,130,246,.35); }
    .kpi-card.kpi-total    { border-left-color: #3B82F6; }
    .kpi-card.kpi-accepted { border-left-color: #10B981; }
    .kpi-card.kpi-pending  { border-left-color: #F59E0B; }
    .kpi-card.kpi-rejected { border-left-color: #EF4444; }

    .kpi-icon-wrap {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; flex-shrink: 0;
    }
    .kpi-total    .kpi-icon-wrap { background: #EFF6FF; color: #3B82F6; }
    .kpi-accepted .kpi-icon-wrap { background: #ECFDF5; color: #10B981; }
    .kpi-pending  .kpi-icon-wrap { background: #FFFBEB; color: #F59E0B; }
    .kpi-rejected .kpi-icon-wrap { background: #FEF2F2; color: #EF4444; }

    .kpi-label  { font-size: 0.78rem; color: #64748b; margin-bottom: 2px; }
    .kpi-value  { font-size: 1.6rem; font-weight: 700; color: #0f172a; line-height: 1; }
    .kpi-subtext { font-size: 0.76rem; color: #94a3b8; margin-top: 2px; }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class="container">

            <!--begin::Page Header-->
            <div class="registrasi-page-header">
                <div>
                    <h1>Request IKR</h1>
                    <p>Kelola data request IKR pelanggan.</p>
                </div>
                <div class="header-actions">
                    <!-- <a href="<?= BASE_URL ?>pages/request/ikr/export_excel.php" class="btn btn-outline-success font-weight-bolder">
                        <span class="svg-icon svg-icon-md text-center">
                            <i class="far fa-file-excel"></i>
                        </span> Export Excel
                    </a> -->
                    <a href="<?= BASE_URL ?>pages/request/ikr/create.php" class="btn btn-primary font-weight-bolder">
                        <span class="svg-icon svg-icon-md">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24"/>
                                    <circle fill="#000000" opacity="0.3" cx="12" cy="12" r="10"/>
                                    <path d="M11,11 L11,7 C11,6.44771525 11.4477153,6 12,6 C12.5522847,6 13,6.44771525 13,7 L13,11 L17,11 C17.5522847,11 18,11.4477153 18,12 C18,12.5522847 17.5522847,13 17,13 L13,13 L13,17 C13,17.5522847 12.5522847,18 12,18 C11.4477153,18 11,17.5522847 11,17 L11,13 L7,13 C6.44771525,13 6,12.5522847 6,12 C6,11.4477153 6.44771525,11 7,11 L11,11 Z" fill="#000000"/>
                                </g>
                            </svg>
                        </span> New Request
                    </a>
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
                        <div class="kpi-label">Total Request</div>
                        <div class="kpi-value" id="kpi-value-total"><?= $kpi['total'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-accepted" id="kpi-card-accepted">
                    <div class="kpi-icon-wrap"><i class="flaticon2-check-mark"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Accepted</div>
                        <div class="kpi-value" id="kpi-value-accepted"><?= $kpi['accepted'] ?></div>
                    </div>
                </div>
                <div class="kpi-card kpi-pending" id="kpi-card-pending">
                    <div class="kpi-icon-wrap"><i class="flaticon2-time"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Pending</div>
                        <div class="kpi-value" id="kpi-value-pending"><?= $kpi['pending'] ?></div>
                    </div>
                </div>
                <!-- <div class="kpi-card kpi-rejected" id="kpi-card-rejected">
                    <div class="kpi-icon-wrap"><i class="flaticon2-delete"></i></div>
                    <div class="kpi-text">
                        <div class="kpi-label">Rejected</div>
                        <div class="kpi-value" id="kpi-value-rejected"><?= $kpi['rejected'] ?></div>
                    </div>
                </div> -->
            </div>
            <!--end::KPI-->

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">Data Request IKR</h3>
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
        <!-- end::Container -->
    </div>
</div>
<!-- end::entry -->

<!-- modal detail RIKR -->
<div class="modal fade" id="detailModalRIKR" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content shadow-lg border-0 rounded-lg">
            <div class="modal-header">
                <h4 class="modal-title"><i class="la la-info-circle text-info"></i> Detail Request IKR</h4>
                <button type="button" class="close text-danger" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">RIKR ID</div>
                    <div class="col-8" id="detail_rikrId"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Netpay ID</div>
                    <div class="col-8" id="detail_netpayId"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Registrasi ID</div>
                    <div class="col-8" id="detail_registrasiId"></div>
                </div>
                <div class="row mb-2 pl-2 justify-content-center align-items-center">
                    <div class="col-4 font-weight-bold">Jadwal Pemasangan</div>
                    <div class="col-8">
                        <div id="detail_jadwal"></div>
                    </div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Jam</div>
                    <div class="col-8" id="detail_jam"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Status</div>
                    <div class="col-8" id="detail_status"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Request By</div>
                    <div class="col-8" id="detail_requestBy"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Catatan</div>
                    <div class="col-8">
                        <div id="detail_catatan"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" data-dismiss="modal">
                    <i class="la la-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../../includes/footer.php';
?>