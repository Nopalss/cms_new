<?php
// mengambil data issues report berdasarkan id teknisi
$sql = "
    SELECT i.*, s.job_type
    FROM issues_report i
    JOIN schedules s ON i.schedule_id = s.schedule_id
    WHERE i.status = 'Pending'";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$issues_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

// KPI awal server-side (default: tanggal hari ini)
$kpi = ['total' => 0, 'pending' => 0, 'actived' => 0, 'rescheduled' => 0, 'cancelled' => 0, 'done' => 0];

try {
    $todayDate = date('Y-m-d');
    $kpiStmt = $pdo->prepare(
        "SELECT
            SUM(1) AS total,
            SUM(CASE WHEN status = 'Pending'     THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'Actived'     THEN 1 ELSE 0 END) AS actived,
            SUM(CASE WHEN status = 'Rescheduled' THEN 1 ELSE 0 END) AS rescheduled,
            SUM(CASE WHEN status = 'Cancelled'   THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status = 'Done'        THEN 1 ELSE 0 END) AS done
         FROM schedules
         WHERE `date` = :today"
    );
    $kpiStmt->execute([':today' => $todayDate]);
    $kpiRow = $kpiStmt->fetch(PDO::FETCH_ASSOC);
    if ($kpiRow) {
        foreach ($kpi as $k => $_) {
            $kpi[$k] = (int) ($kpiRow[$k] ?? 0);
        }
    }
} catch (PDOException $e) {
    // silent fail; JS akan refresh via AJAX
}
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
        align-items: center;
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
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 1199.98px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 767.98px)  { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575.98px)  { .kpi-grid { grid-template-columns: 1fr; } }

    .kpi-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        border-left: 4px solid;
        transition: box-shadow .15s, transform .15s;
    }
    .kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }

    .kpi-card.kpi-total       { border-left-color: #3B82F6; }
    .kpi-card.kpi-pending     { border-left-color: #06B6D4; }
    .kpi-card.kpi-actived     { border-left-color: #6366F1; }
    .kpi-card.kpi-done        { border-left-color: #10B981; }
    .kpi-card.kpi-rescheduled { border-left-color: #F59E0B; }
    .kpi-card.kpi-cancelled   { border-left-color: #EF4444; }

    .kpi-icon-wrap {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .kpi-total       .kpi-icon-wrap { background: #EFF6FF; color: #3B82F6; }
    .kpi-pending     .kpi-icon-wrap { background: #ECFEFF; color: #06B6D4; }
    .kpi-actived     .kpi-icon-wrap { background: #EEF2FF; color: #6366F1; }
    .kpi-done        .kpi-icon-wrap { background: #ECFDF5; color: #10B981; }
    .kpi-rescheduled .kpi-icon-wrap { background: #FFFBEB; color: #F59E0B; }
    .kpi-cancelled   .kpi-icon-wrap { background: #FEF2F2; color: #EF4444; }

    .kpi-label { font-size: 0.76rem; color: #64748b; margin-bottom: 2px; }
    .kpi-value { font-size: 1.5rem; font-weight: 700; color: #0f172a; line-height: 1; }
</style>

<!--begin::Page Header-->
<div class="registrasi-page-header">
    <div>
        <h1>Schedule</h1>
        <p>Kelola data penjadwalan teknisi.</p>
    </div>
    <div class="header-actions">
        <!-- <a href="<?= BASE_URL ?>pages/schedule/export_excel.php" class="btn btn-outline-success font-weight-bolder">
            <i class="far fa-file-excel mr-1"></i> Export Excel
        </a> -->
        <button type="button" class="btn btn-warning font-weight-bolder" id="btn-issues"
                data-toggle="modal" data-target="#exampleModalScrollable">
            <i class="flaticon2-warning mr-1"></i> Issues Report
            <small id="issueNow" class="ml-2 label label-danger" style="display:none;"></small>
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

<!--begin::KPI-->
<div class="kpi-grid">
    <div class="kpi-card kpi-total" id="kpi-card-total">
        <div class="kpi-icon-wrap"><i class="flaticon2-calendar-8"></i></div>
        <div class="kpi-text">
            <div class="kpi-label">Total</div>
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
    <div class="kpi-card kpi-actived" id="kpi-card-actived">
        <div class="kpi-icon-wrap"><i class="flaticon2-rocket-1"></i></div>
        <div class="kpi-text">
            <div class="kpi-label">Actived</div>
            <div class="kpi-value" id="kpi-value-actived"><?= $kpi['actived'] ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-done" id="kpi-card-done">
        <div class="kpi-icon-wrap"><i class="flaticon2-check-mark"></i></div>
        <div class="kpi-text">
            <div class="kpi-label">Done</div>
            <div class="kpi-value" id="kpi-value-done"><?= $kpi['done'] ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-rescheduled" id="kpi-card-rescheduled">
        <div class="kpi-icon-wrap"><i class="flaticon-event-calendar-symbol"></i></div>
        <div class="kpi-text">
            <div class="kpi-label">Rescheduled</div>
            <div class="kpi-value" id="kpi-value-rescheduled"><?= $kpi['rescheduled'] ?></div>
        </div>
    </div>
    <div class="kpi-card kpi-cancelled" id="kpi-card-cancelled">
        <div class="kpi-icon-wrap"><i class="flaticon2-delete"></i></div>
        <div class="kpi-text">
            <div class="kpi-label">Cancelled</div>
            <div class="kpi-value" id="kpi-value-cancelled"><?= $kpi['cancelled'] ?></div>
        </div>
    </div>
</div>
<!--end::KPI-->

<!--begin::Card-->
<div class="card card-custom">
    <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
            <h3 class="card-label">Data Schedule</h3>
        </div>
    </div>
    <div class="card-body">
        <!--begin::Search Form-->
        <div class="mb-4">
            <div class="row align-items-center">
                <div class="col-lg-12 col-xl-12">
                    <div class="row align-items-center">
                        <div class="col-md-3 my-2 my-md-0">
                            <div class="input-icon">
                                <input type="text" class="form-control" placeholder="Search..." id="kt_datatable_search_query"/>
                                <span><i class="flaticon2-search-1 text-muted"></i></span>
                            </div>
                        </div>
                        <div class="col-md-2 my-2 my-md-0">
                            <div class="d-flex align-items-center">
                                <label class="mr-3 mb-0 d-none d-md-block">Status:</label>
                                <select class="form-control" id="kt_datatable_search_status">
                                    <option value="">All</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Actived">Actived</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 my-2 my-md-0">
                            <div class="d-flex align-items-center">
                                <label class="mr-3 mb-0 d-none d-md-block">Type:</label>
                                <select class="form-control" id="kt_datatable_search_type">
                                    <option value="">All</option>
                                    <option value="Instalasi">Instalasi</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Dismantle">Dismantle</option>
                                    <option value="Service">Service</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 my-2 my-md-0">
                            <div class="d-flex align-items-center">
                                <label class="mr-3 mb-0 d-none d-md-block">Teknisi:</label>
                                <select class="form-control" id="kt_datatable_search_tech">
                                    <option value="">All</option>
                                    <?php foreach ($technicians as $t): ?>
                                        <option value="<?= $t['tech_id'] ?>"><?= $t['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Search Form-->

        <!--begin: Datatable-->
        <div class="table-responsive">
            <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
        </div>
        <!--end: Datatable-->
    </div>
</div>
<!--end::Card-->


<!-- Modal Issues Report -->
<div class="modal fade" id="exampleModalScrollable" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Issues Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body" style="height: 300px;">
                <div class="table-responsive-xl">
                    <table class="table text-sm">
                        <thead>
                            <tr>
                                <th scope="col">Issue Id</th>
                                <th scope="col">Schedule Id</th>
                                <th scope="col">Reported By</th>
                                <th scope="col">Issue Type</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <?php if (count($issues_report) > 0): ?>
                            <tbody>
                                <?php foreach ($issues_report as $i): ?>
                                    <tr>
                                        <th scope="row"><?= $i['issue_id'] ?></th>
                                        <td><?= $i['schedule_id'] ?></td>
                                        <td><?= $i['reported_by'] ?></td>
                                        <td><?= $i['issue_type'] ?></td>
                                        <td class="text-sm">
                                            <span class="badge badge-pill badge-<?= $statusIssueClasses[$i['status']] ?>">
                                                <?= $i['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                                <!-- Detail Button -->
                                                <button class="btn btn-sm btn-icon btn-clean btn-hover-light-info btn-detail3"
                                                        data-id="<?= $i['issue_id'] ?>"
                                                        data-schedule="<?= $i['schedule_id'] ?>"
                                                        data-reported="<?= $i['reported_by'] ?>"
                                                        data-type="<?= $i['issue_type'] ?>"
                                                        data-desc="<?= $i['description'] ?>"
                                                        data-date="<?= $i['created_at'] ?>"
                                                        data-status="<?= $i['status'] ?>"
                                                        data-state="<?= $statusIssueClasses[$i['status']] ?>"
                                                        title="Detail Kendala">
                                                    <i class="flaticon-eye text-info" style="font-size: 1.15rem;"></i>
                                                </button>
                                                
                                                <?php if ($i['status'] === 'Pending'): ?>
                                                    <!-- Approved Button -->
                                                    <button class="btn btn-sm btn-icon btn-clean btn-hover-light-success"
                                                            onclick="confirmApproved('<?= $i['issue_id'] ?>','<?= $i['schedule_id'] ?>', '<?= $i['job_type'] ?>')"
                                                            title="Setujui Kendala (Approve)">
                                                        <i class="flaticon2-check-mark text-success" style="font-size: 0.9rem;"></i>
                                                    </button>
                                                    
                                                    <!-- Rejected Button -->
                                                    <button class="btn btn-sm btn-icon btn-clean btn-hover-light-danger"
                                                            onclick="confirmRejected('<?= $i['issue_id'] ?>','<?= $i['schedule_id'] ?>')"
                                                            title="Tolak Kendala (Reject)">
                                                        <i class="flaticon2-cross text-danger" style="font-size: 0.85rem;"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php else: ?>
                            <tr>
                                <td class="text-center text-muted text-weight-bold" colspan="6">Tidak ada Issue Report</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>pages/schedule/table_issue_report.php" class="btn btn-light-primary font-weight-bold">View All</a>
            </div>
        </div>
    </div>
</div>

<!-- modal detail -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content shadow-lg border-0 rounded-lg">
            <div class="modal-header">
                <h4 class="modal-title"><i class="la la-info-circle text-info"></i> Detail Schedule</h4>
                <button type="button" class="close text-danger" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Schedule ID</div>
                    <div class="col-8" id="detail_id"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Netpay ID</div>
                    <div class="col-8" id="detail_netpayId"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Teknisi</div>
                    <div class="col-8" id="detail_tech"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Tanggal</div>
                    <div class="col-8" id="detail_date"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Job Type</div>
                    <div class="col-8" id="detail_job"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Status</div>
                    <div class="col-8">
                        <div id="detail_status"></div>
                    </div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Location</div>
                    <div class="col-8" id="detail_location"></div>
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