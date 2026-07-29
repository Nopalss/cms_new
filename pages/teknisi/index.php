<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
$_SESSION['menu'] = 'teknisi';
$_SESSION['table'] = 'teknisi';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>


<style>
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
</style>

<div class="content  d-flex flex-column flex-column-fluid" id="kt_content">

    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class=" container ">

            <!--begin::Filter Periode-->
            <div class="periode-card">
                <span class="periode-label">Periode Evaluasi</span>
                <div class="periode-btn-group" id="periode-btn-group">
                    <button type="button" class="btn" data-period="today">Hari Ini</button>
                    <button type="button" class="btn" data-period="week">Minggu Ini</button>
                    <button type="button" class="btn active" data-period="month">Bulan Ini (Siklus)</button>
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

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">
                            Data Teknisi
                        </h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-7">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-xl-12">
                                <div class="row align-items-center">
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="input-icon">
                                            <input type="text" class="form-control" placeholder="Search..." id="kt_datatable_search_query" />
                                            <span><i class="flaticon2-search-1 text-muted"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
                    <!--end: Datatable-->
                </div>
            </div>
            <!--end::Card-->
        </div>
        <!-- end::Container -->
    </div>
</div>


<?php
require __DIR__ . '/../../includes/footer.php';
?>