<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'ont_stock';
$_SESSION['table'] = 'ont_inventory';

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<style>
    /* ── Layout & Spacing Fixes ── */
    .ont-main-wrapper {
        padding-top: 1.5rem;
        padding-bottom: 3rem;
    }
    
    .ont-card-kpi {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 18px rgba(0,0,0,0.04);
        background: #FFFFFF;
    }
    .ont-card-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }
    .kpi-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    /* Periode Filter Card */
    .periode-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 0.9rem 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .periode-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
        white-space: nowrap;
    }
    .periode-btn-group .btn {
        border-radius: 8px !important;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.4rem 0.9rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        margin-right: 0.35rem;
        transition: background .15s, color .15s, border-color .15s;
    }
    .periode-btn-group .btn:last-child { margin-right: 0; }
    .periode-btn-group .btn.active { background: #0E7C7B; border-color: #0E7C7B; color: #fff; }
    .periode-custom-range { display: none; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .periode-custom-range.show { display: flex; }
    .periode-custom-range label { font-size: 0.8rem; color: #64748b; margin: 0 0.2rem 0 0; }
    .periode-custom-range input[type="date"] { height: 36px; font-size: 0.85rem; }

    /* Badge Status Colors */
    .badge-status-IN_STOCK { background-color: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; font-weight: 700; }
    .badge-status-IN_USE { background-color: #E3F2FD; color: #1565C0; border: 1px solid #BBDEFB; font-weight: 700; }
    .badge-status-DAMAGED { background-color: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; font-weight: 700; }
    .badge-status-REPAIR { background-color: #FFF8E1; color: #F57F17; border: 1px solid #FFECB3; font-weight: 700; }
    .badge-status-LOST { background-color: #ECEFF1; color: #37474F; border: 1px solid #CFD8DC; font-weight: 700; }

    /* Movement Log Timeline */
    .timeline-ont {
        position: relative;
        padding-left: 24px;
        margin-top: 15px;
    }
    .timeline-ont::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: #E2E8F0;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-dot {
        position: absolute;
        left: -24px;
        top: 3px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #0E7C7B;
        border: 3px solid #FFFFFF;
        box-shadow: 0 0 0 2px #E2E8F0;
    }
    .timeline-dot.INSTALL { background: #10B981; }
    .timeline-dot.DISMANTLE { background: #EF4444; }
    .timeline-dot.SWAP_OUT { background: #F59E0B; }
    .timeline-dot.SWAP_IN { background: #3B82F6; }
    .timeline-dot.RETURN_TO_STOCK { background: #059669; }
    .timeline-dot.REPAIR_OUT { background: #D97706; }
    .timeline-dot.REPAIR_IN { background: #10B981; }
    .timeline-dot.LOST { background: #64748B; }

    /* Side Drawer for ONT Details */
    .ont-drawer {
        position: fixed;
        top: 0;
        right: -540px;
        width: 540px;
        max-width: 100vw;
        height: 100vh;
        background: #FFFFFF;
        box-shadow: -5px 0 25px rgba(0,0,0,0.15);
        z-index: 1050;
        transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    .ont-drawer.open {
        right: 0;
    }
    .ont-drawer-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(2px);
        z-index: 1040;
        display: none;
    }
    .ont-drawer-backdrop.show {
        display: block;
    }

    /* Custom Nav Tabs */
    .nav-tabs-custom .nav-link {
        font-weight: 700;
        color: #64748B;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        border-radius: 0;
    }
    .nav-tabs-custom .nav-link.active {
        color: #0E7C7B;
        border-bottom-color: #0E7C7B;
        background: transparent;
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    
    <div class="ont-main-wrapper container-fluid">
        
        <!-- Header Title Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-5 gap-3">
            <div>
                <h3 class="text-dark font-weight-bolder mb-1">📦 Stok & Inventori ONT</h3>
                <div class="text-muted font-size-sm">Sistem Manajemen Stok Gudang & Penggunaan Modem/Router ONT Pelanggan</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-success font-weight-bold mr-2" id="btnExportCsv">
                    <i class="flaticon-download"></i> Export CSV
                </button>
                <button type="button" class="btn btn-primary font-weight-bold" id="btnOpenStockIn">
                    <i class="flaticon2-plus"></i> + Stock In (Tambah ONT)
                </button>
            </div>
        </div>

        <!-- Filter Periode Bar -->
        <div class="periode-card">
            <span class="periode-label">🗓️ Filter Periode:</span>
            <div class="periode-btn-group" id="periodeBtnGroup">
                <button type="button" class="btn active" data-period="all">Semua</button>
                <button type="button" class="btn" data-period="today">Hari Ini</button>
                <button type="button" class="btn" data-period="week">Minggu Ini</button>
                <button type="button" class="btn" data-period="month">Bulan Ini</button>
                <button type="button" class="btn" data-period="custom">Custom</button>
            </div>
            <div class="periode-custom-range" id="periodeCustomRange">
                <label>Dari</label>
                <input type="date" class="form-control form-control-sm" id="periodeCustomFrom">
                <label>Sampai</label>
                <input type="date" class="form-control form-control-sm" id="periodeCustomTo">
                <button type="button" class="btn btn-primary btn-sm" id="btnApplyCustomDate">Terapkan</button>
            </div>
        </div>

        <!-- KPI Summary Row -->
        <div class="row mb-5">
            <!-- In Stock -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-success text-success mr-3">📦</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">In Stock</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiInStock">0</div>
                    </div>
                </div>
            </div>
            <!-- In Use -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-primary text-primary mr-3">🏠</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">In Use</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiInUse">0</div>
                    </div>
                </div>
            </div>
            <!-- Damaged -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-danger text-danger mr-3">⚠️</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">Rusak</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiDamaged">0</div>
                    </div>
                </div>
            </div>
            <!-- Repair -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-warning text-warning mr-3">🛠️</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">Perbaikan</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiRepair">0</div>
                    </div>
                </div>
            </div>
            <!-- Lost -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-dark text-dark mr-3">❓</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">Hilang</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiLost">0</div>
                    </div>
                </div>
            </div>
            <!-- Total -->
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="ont-card-kpi p-4 d-flex align-items-center">
                    <div class="kpi-icon-badge bg-light-info text-info mr-3">📊</div>
                    <div>
                        <div class="text-muted font-size-xs font-weight-bold text-uppercase">Total Unit</div>
                        <div class="font-size-h4 font-weight-bolder text-dark" id="kpiTotal">0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="card card-custom shadow-sm mb-5">
            <div class="card-header border-bottom-0 pb-0">
                <ul class="nav nav-tabs nav-tabs-custom border-bottom-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tabGroupBtn" data-toggle="tab" href="#tabGroupContent">
                            📊 Ringkasan Brand & Tipe ONT
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tabUnitBtn" data-toggle="tab" href="#tabUnitContent">
                            📋 Daftar Serial Number (SN) Unit
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Contents -->
        <div class="tab-content">
            
            <!-- LEVEL 1: GROUP BY BRAND & TIPE -->
            <div class="tab-pane fade show active" id="tabGroupContent" role="tabpanel">
                <div class="card card-custom shadow-sm">
                    <div class="card-header border-0 py-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label font-weight-bolder text-dark">Ringkasan Jumlah Unit per Brand & Tipe</span>
                            <span class="text-muted mt-1 font-weight-bold font-size-sm" id="groupSubInfo">Memuat kelompok tipe ONT...</span>
                        </h3>
                        <div class="card-toolbar">
                            <div class="input-icon input-icon-right mr-2 my-1" style="width: 260px;">
                                <input type="text" class="form-control" placeholder="🔍 Cari Brand / Tipe ONT..." id="searchGroupInput">
                                <span><i class="flaticon2-search-1 text-muted"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body py-0">
                        <div class="table-responsive">
                            <table class="table table-head-custom table-vertical-center" id="groupTable">
                                <thead>
                                    <tr class="text-uppercase text-muted">
                                        <th style="min-width: 160px">Tipe ONT</th>
                                        <th style="min-width: 140px">Brand / Merek</th>
                                        <th style="min-width: 120px">Total Unit</th>
                                        <th style="min-width: 130px">📦 Stok Gudang</th>
                                        <th style="min-width: 130px">🏠 Dipakai Customer</th>
                                        <th style="min-width: 120px">⚠️ Rusak</th>
                                        <th style="min-width: 120px">🛠️ Perbaikan/Hilang</th>
                                        <th class="text-right" style="min-width: 140px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="groupTbody">
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                            Memuat kelompok tipe ONT...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Level 1 Group Pagination -->
                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap py-4">
                        <div class="text-muted font-size-sm font-weight-bold" id="groupPaginationInfo">
                            Menampilkan 0 kelompok
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-light-primary font-weight-bold mr-2" id="btnPrevGroupPage" disabled>&larr; Sebelumnya</button>
                            <span class="mx-2 font-weight-bold text-dark font-size-sm" id="groupPageIndicator">Halaman 1 dari 1</span>
                            <button class="btn btn-sm btn-light-primary font-weight-bold ml-2" id="btnNextGroupPage" disabled>Selanjutnya &rarr;</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LEVEL 2: DETAILED SERIAL NUMBER UNIT LIST -->
            <div class="tab-pane fade" id="tabUnitContent" role="tabpanel">
                <div class="card card-custom shadow-sm">
                    <!-- Filter Info Bar (if filtered from Level 1) -->
                    <div class="p-3 bg-light-primary rounded-top border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" id="unitFilterBanner" style="display: none;">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-primary mr-2 px-3 py-2">Filter Aktif</span>
                            <span class="font-weight-bold text-dark" id="unitFilterBannerText">Tipe: -</span>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold" id="btnResetGroupFilter">
                            &larr; Lihat Semua Brand & Tipe
                        </button>
                    </div>

                    <div class="card-header border-0 py-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label font-weight-bolder text-dark">Daftar Unit & Serial Number (SN)</span>
                            <span class="text-muted mt-1 font-weight-bold font-size-sm" id="tableSubInfo">Memuat data unit ONT...</span>
                        </h3>
                        <div class="card-toolbar d-flex flex-wrap gap-2">
                            <div class="input-icon input-icon-right mr-2 my-1" style="width: 260px;">
                                <input type="text" class="form-control" placeholder="🔍 Cari SN / ONT ID / Customer..." id="searchInput">
                                <span><i class="flaticon2-search-1 text-muted"></i></span>
                            </div>
                            <select class="form-control mr-2 my-1" id="statusFilter" style="width: 150px;">
                                <option value="ALL">Semua Status</option>
                                <option value="IN_STOCK">IN_STOCK (Gudang)</option>
                                <option value="IN_USE">IN_USE (Customer)</option>
                                <option value="DAMAGED">DAMAGED (Rusak)</option>
                                <option value="REPAIR">REPAIR (Perbaikan)</option>
                                <option value="LOST">LOST (Hilang)</option>
                            </select>
                            <select class="form-control mr-2 my-1" id="brandFilter" style="width: 140px;">
                                <option value="ALL">Semua Brand</option>
                            </select>
                            <select class="form-control my-1" id="typeFilter" style="width: 160px;">
                                <option value="ALL">Semua Tipe</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-body py-0">
                        <div class="table-responsive">
                            <table class="table table-head-custom table-vertical-center" id="ontTable">
                                <thead>
                                    <tr class="text-uppercase text-muted">
                                        <th style="min-width: 110px">ONT ID</th>
                                        <th style="min-width: 160px">Serial Number (SN)</th>
                                        <th style="min-width: 140px">Brand / Tipe</th>
                                        <th style="min-width: 130px">MAC Address</th>
                                        <th style="min-width: 120px">Status</th>
                                        <th style="min-width: 180px">Customer / Lokasi saat ini</th>
                                        <th style="min-width: 140px">Terakhir Update</th>
                                        <th class="text-right" style="min-width: 120px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="ontTbody">
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                            Memuat data unit ONT...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap py-4">
                        <div class="text-muted font-size-sm font-weight-bold" id="paginationInfo">
                            Menampilkan 0 data
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-light-primary font-weight-bold mr-2" id="btnPrevPage" disabled>&larr; Sebelumnya</button>
                            <span class="mx-2 font-weight-bold text-dark font-size-sm" id="pageIndicator">Halaman 1 dari 1</span>
                            <button class="btn btn-sm btn-light-primary font-weight-bold ml-2" id="btnNextPage" disabled>Selanjutnya &rarr;</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ================= LEVEL 3: SIDE DRAWER DETAIL & HISTORY ================= -->
<div class="ont-drawer-backdrop" id="ontDrawerBackdrop"></div>
<div class="ont-drawer" id="ontDrawer">
    <div class="p-5 border-bottom d-flex align-items-center justify-content-between bg-light">
        <div>
            <h4 class="font-weight-bolder text-dark mb-0" id="drawerOntId">Detail Unit ONT</h4>
            <span class="text-muted font-size-sm" id="drawerSn">SN: -</span>
        </div>
        <button type="button" class="btn btn-xs btn-icon btn-light" id="btnCloseDrawer">&times;</button>
    </div>

    <div class="p-5 overflow-auto flex-grow-1" id="drawerBody">
        <div class="text-center py-10" id="drawerSpinner">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted font-weight-bold">Memuat detail unit ONT...</div>
        </div>

        <div id="drawerContent" style="display: none;">
            <div class="p-4 rounded mb-5 d-flex align-items-center justify-content-between" id="drawerStatusBanner">
                <div>
                    <div class="text-uppercase font-size-xs font-weight-bold text-muted">Status Unit</div>
                    <div class="font-size-h5 font-weight-bolder" id="drawerStatusText">IN_STOCK</div>
                </div>
                <button type="button" class="btn btn-sm btn-primary font-weight-bold" id="btnEditStatusDrawer">
                    ✏️ Ubah Status
                </button>
            </div>

            <div class="card card-custom bg-light-secondary border-0 p-4 mb-5">
                <h6 class="font-weight-bolder text-dark mb-3">📋 Spesifikasi Unit ONT</h6>
                <div class="row font-size-sm">
                    <div class="col-6 mb-2"><strong>Brand:</strong> <span id="drawerBrand">-</span></div>
                    <div class="col-6 mb-2"><strong>Tipe ONT:</strong> <span id="drawerType">-</span></div>
                    <div class="col-6 mb-2"><strong>MAC Address:</strong> <span id="drawerMac">-</span></div>
                    <div class="col-6 mb-2"><strong>Tanggal Dibuat:</strong> <span id="drawerCreated">-</span></div>
                </div>
                <div class="mt-2 pt-2 border-top">
                    <strong>Catatan / Kondisi:</strong>
                    <div class="text-muted mt-1 font-size-xs" id="drawerCondition">-</div>
                </div>
            </div>

            <div class="card card-custom border border-primary p-4 mb-5" id="drawerCustomerCard" style="display: none;">
                <h6 class="font-weight-bolder text-primary mb-2">👤 Pengguna / Customer Saat Ini</h6>
                <div class="font-size-sm">
                    <div><strong>Netpay ID:</strong> <span id="drawerNetpayId">-</span></div>
                    <div><strong>Nama Customer:</strong> <span id="drawerCustName">-</span></div>
                    <div><strong>No. Telepon:</strong> <span id="drawerCustPhone">-</span></div>
                    <div><strong>Alamat:</strong> <span id="drawerCustAlamat">-</span></div>
                </div>
            </div>

            <div class="mt-6">
                <h6 class="font-weight-bolder text-dark mb-3">🕒 Riwayat Pergerakan Unit (Audit Trail)</h6>
                <div class="timeline-ont" id="drawerTimeline">
                    <div class="text-muted font-size-sm">Belum ada riwayat pergerakan.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL STOCK IN ================= -->
<div class="modal fade" id="modalStockIn" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bolder">📦 Stock In — Tambah Unit ONT Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="formStockIn">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Serial Number (SN) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="serial_number" id="si_serial_number" placeholder="Contoh: HWTC3175D59E" required>
                        <small class="form-text text-muted">Serial number unik pada perangkat ONT fisik.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Brand Perangkat</label>
                            <input type="text" class="form-control" name="brand" id="si_brand" placeholder="Contoh: Huawei / ZTE / FiberHome" list="brandList">
                            <datalist id="brandList">
                                <option value="Huawei">
                                <option value="ZTE">
                                <option value="FiberHome">
                                <option value="TP-Link">
                                <option value="Nokia">
                            </datalist>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Tipe ONT</label>
                            <input type="text" class="form-control" name="type_ont" id="si_type_ont" placeholder="Contoh: EG8141A5 / F609" list="typeList">
                            <datalist id="typeList">
                                <option value="EG8141A5">
                                <option value="HG6245D">
                                <option value="F660">
                                <option value="F609">
                                <option value="XPON Single Band">
                                <option value="XPON Dual Band">
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">MAC Address</label>
                        <input type="text" class="form-control" name="mac_address" id="si_mac_address" placeholder="Contoh: 00:1A:2B:3C:4D:5E">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Catatan Penerimaan / Kondisi</label>
                        <textarea class="form-control" name="condition_note" id="si_condition_note" rows="2" placeholder="Catatan supplier, kondisi fisik, batch pengiriman..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold" id="btnSubmitStockIn">Simpan ke Stok Gudang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL UBAH STATUS MANUAL ================= -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bolder">✏️ Ubah Status Manual Unit ONT</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="formUpdateStatus">
                <input type="hidden" name="ont_id" id="us_ont_id">
                <div class="modal-body">
                    <div class="p-3 bg-light-primary rounded mb-4 font-size-sm">
                        <div><strong>ONT ID:</strong> <span id="us_display_ont_id">-</span></div>
                        <div><strong>Serial Number:</strong> <span id="us_display_sn">-</span></div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Status Baru <span class="text-danger">*</span></label>
                        <select class="form-control" name="status" id="us_status" required>
                            <option value="IN_STOCK">IN_STOCK (Tersedia di Gudang)</option>
                            <option value="IN_USE">IN_USE (Dipakai Customer)</option>
                            <option value="DAMAGED">DAMAGED (Rusak Total)</option>
                            <option value="REPAIR">REPAIR (Dalam Perbaikan)</option>
                            <option value="LOST">LOST (Hilang)</option>
                        </select>
                    </div>

                    <div class="form-group mb-3" id="groupUsNetpay" style="display: none;">
                        <label class="font-weight-bold">Netpay ID Customer (Jika IN_USE)</label>
                        <input type="text" class="form-control" name="netpay_id" id="us_netpay_id" placeholder="Masukkan Netpay ID Customer">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Alasan / Catatan Perubahan Status <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="condition_note" id="us_condition_note" rows="3" placeholder="Tuliskan alasan perubahan status (misal: Kerusakan petir, dikirim ke teknisi repair, dll)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold" id="btnSubmitUpdateStatus">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

<!-- Data & Interaction Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = {
        groupList: '<?= BASE_URL ?>api/ont/group_list.php',
        list: '<?= BASE_URL ?>api/ont/list.php',
        detail: '<?= BASE_URL ?>api/ont/detail.php',
        create: '<?= BASE_URL ?>api/ont/create.php',
        updateStatus: '<?= BASE_URL ?>api/ont/update_status.php',
        export: '<?= BASE_URL ?>api/ont/export.php'
    };

    const state = {
        groupPage: 1,
        groupLimit: 15,
        groupTotalPages: 1,
        page: 1,
        limit: 15,
        search: '',
        groupSearch: '',
        status: 'ALL',
        brand: 'ALL',
        type: 'ALL',
        period: 'all',
        customFrom: '',
        customTo: '',
        totalPages: 1,
        activeOnt: null
    };

    // Load initial data
    loadGroupData();
    loadUnitData();

    // Periode Filter Event Handling
    const periodeGroup = document.getElementById('periodeBtnGroup');
    periodeGroup.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            periodeGroup.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            state.period = this.dataset.period;
            const customRange = document.getElementById('periodeCustomRange');

            if (state.period === 'custom') {
                customRange.classList.add('show');
            } else {
                customRange.classList.remove('show');
                state.groupPage = 1;
                state.page = 1;
                loadGroupData();
                loadUnitData();
            }
        });
    });

    document.getElementById('btnApplyCustomDate').addEventListener('click', function() {
        state.customFrom = document.getElementById('periodeCustomFrom').value;
        state.customTo = document.getElementById('periodeCustomTo').value;

        if (!state.customFrom || !state.customTo) {
            if (typeof Swal !== 'undefined') Swal.fire('Peringatan', 'Silakan pilih rentang tanggal dari dan sampai', 'warning');
            return;
        }

        state.groupPage = 1;
        state.page = 1;
        loadGroupData();
        loadUnitData();
    });

    // Group Search Input
    let groupTimer;
    document.getElementById('searchGroupInput').addEventListener('input', function(e) {
        clearTimeout(groupTimer);
        groupTimer = setTimeout(() => {
            state.groupSearch = e.target.value.trim();
            state.groupPage = 1;
            loadGroupData();
        }, 300);
    });

    // Group Pagination Click
    document.getElementById('btnPrevGroupPage').addEventListener('click', function() {
        if (state.groupPage > 1) {
            state.groupPage--;
            loadGroupData();
        }
    });

    document.getElementById('btnNextGroupPage').addEventListener('click', function() {
        if (state.groupPage < state.groupTotalPages) {
            state.groupPage++;
            loadGroupData();
        }
    });

    // Level 1: Load Grouped Summary Data
    async function loadGroupData() {
        const tbody = document.getElementById('groupTbody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mr-2"></div>Memuat ringkasan kelompok ONT...</td></tr>`;

        try {
            const queryParams = new URLSearchParams({
                page: state.groupPage,
                limit: state.groupLimit,
                search: state.groupSearch,
                period: state.period,
                custom_from: state.customFrom,
                custom_to: state.customTo
            });

            const res = await fetch(`${API.groupList}?${queryParams.toString()}`);
            const result = await res.json();

            if (!result.status) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Gagal memuat ringkasan: ${escapeHtml(result.message)}</td></tr>`;
                return;
            }

            // Update KPI Summary Cards
            document.getElementById('kpiInStock').textContent = result.kpi.in_stock.toLocaleString();
            document.getElementById('kpiInUse').textContent = result.kpi.in_use.toLocaleString();
            document.getElementById('kpiDamaged').textContent = result.kpi.damaged.toLocaleString();
            document.getElementById('kpiRepair').textContent = result.kpi.repair.toLocaleString();
            document.getElementById('kpiLost').textContent = result.kpi.lost.toLocaleString();
            document.getElementById('kpiTotal').textContent = result.kpi.total.toLocaleString();

            const groups = result.groups || [];
            const pagination = result.pagination || { total_items: groups.length, total_pages: 1 };
            state.groupTotalPages = pagination.total_pages || 1;

            document.getElementById('groupSubInfo').textContent = `Total ${pagination.total_items.toLocaleString()} kelompok Tipe & Brand ONT`;
            document.getElementById('groupPaginationInfo').textContent = `Menampilkan ${groups.length ? (state.groupPage - 1) * state.groupLimit + 1 : 0} - ${Math.min(state.groupPage * state.groupLimit, pagination.total_items)} dari ${pagination.total_items.toLocaleString()} kelompok`;
            document.getElementById('groupPageIndicator').textContent = `Halaman ${state.groupPage} dari ${state.groupTotalPages}`;

            document.getElementById('btnPrevGroupPage').disabled = (state.groupPage <= 1);
            document.getElementById('btnNextGroupPage').disabled = (state.groupPage >= state.groupTotalPages);

            if (!groups.length) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted font-weight-bold">Tidak ada kelompok tipe ONT yang cocok.</td></tr>`;
                return;
            }

            let html = '';
            groups.forEach(g => {
                const brandBadge = g.brand === 'ZTE' 
                    ? `<span class="badge badge-primary px-2 py-1 font-weight-bold">ZTE</span>` 
                    : (g.brand === 'Huawei' 
                        ? `<span class="badge badge-danger px-2 py-1 font-weight-bold">Huawei</span>` 
                        : `<span class="badge badge-secondary px-2 py-1 font-weight-bold">${escapeHtml(g.brand)}</span>`);

                html += `
                    <tr>
                        <td><strong class="font-size-h6 text-dark font-weight-bolder">${escapeHtml(g.type_ont)}</strong></td>
                        <td>${brandBadge}</td>
                        <td><strong class="font-size-h6 text-primary font-weight-bolder">${g.total_count.toLocaleString()}</strong> <small class="text-muted">unit</small></td>
                        <td><span class="badge badge-status-IN_STOCK px-3 py-1">${g.in_stock_count.toLocaleString()} unit</span></td>
                        <td><span class="badge badge-status-IN_USE px-3 py-1">${g.in_use_count.toLocaleString()} unit</span></td>
                        <td><span class="badge badge-status-DAMAGED px-3 py-1">${g.damaged_count.toLocaleString()} unit</span></td>
                        <td>
                            <small class="text-muted">Rep: <strong>${g.repair_count}</strong> | Lost: <strong>${g.lost_count}</strong></small>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-light-primary font-weight-bold btn-drill-down" data-type="${escapeHtml(g.type_ont)}" data-brand="${escapeHtml(g.brand)}">
                                👁️ Lihat SN (${g.total_count}) &rarr;
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            tbody.querySelectorAll('.btn-drill-down').forEach(btn => {
                btn.addEventListener('click', function() {
                    drillDownToUnits(btn.dataset.type, btn.dataset.brand);
                });
            });

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Gagal menghubungi server: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    function drillDownToUnits(typeVal, brandVal) {
        state.type = typeVal;
        state.brand = brandVal;
        state.page = 1;

        document.getElementById('typeFilter').value = typeVal;
        document.getElementById('brandFilter').value = brandVal;

        document.getElementById('unitFilterBanner').style.display = 'flex';
        document.getElementById('unitFilterBannerText').textContent = `Tipe: ${typeVal} | Brand: ${brandVal}`;

        $('#tabUnitBtn').tab('show');

        loadUnitData();
    }

    document.getElementById('btnResetGroupFilter').addEventListener('click', function() {
        state.type = 'ALL';
        state.brand = 'ALL';
        state.page = 1;

        document.getElementById('typeFilter').value = 'ALL';
        document.getElementById('brandFilter').value = 'ALL';
        document.getElementById('unitFilterBanner').style.display = 'none';

        loadUnitData();
    });

    // Level 2 Unit List Logic
    let searchTimer;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.search = e.target.value.trim();
            state.page = 1;
            loadUnitData();
        }, 350);
    });

    document.getElementById('statusFilter').addEventListener('change', function(e) {
        state.status = e.target.value;
        state.page = 1;
        loadUnitData();
    });

    document.getElementById('brandFilter').addEventListener('change', function(e) {
        state.brand = e.target.value;
        state.page = 1;
        checkBannerState();
        loadUnitData();
    });

    document.getElementById('typeFilter').addEventListener('change', function(e) {
        state.type = e.target.value;
        state.page = 1;
        checkBannerState();
        loadUnitData();
    });

    function checkBannerState() {
        const banner = document.getElementById('unitFilterBanner');
        if (state.type !== 'ALL' || state.brand !== 'ALL') {
            banner.style.display = 'flex';
            document.getElementById('unitFilterBannerText').textContent = `Tipe: ${state.type} | Brand: ${state.brand}`;
        } else {
            banner.style.display = 'none';
        }
    }

    document.getElementById('btnPrevPage').addEventListener('click', function() {
        if (state.page > 1) {
            state.page--;
            loadUnitData();
        }
    });

    document.getElementById('btnNextPage').addEventListener('click', function() {
        if (state.page < state.totalPages) {
            state.page++;
            loadUnitData();
        }
    });

    document.getElementById('btnExportCsv').addEventListener('click', function() {
        const queryParams = new URLSearchParams({
            search: state.search,
            status: state.status,
            brand: state.brand,
            type: state.type
        });
        window.location.href = `${API.export}?${queryParams.toString()}`;
    });

    async function loadUnitData() {
        const tbody = document.getElementById('ontTbody');
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mr-2"></div>Memuat data unit...</td></tr>`;

        try {
            const queryParams = new URLSearchParams({
                page: state.page,
                limit: state.limit,
                search: state.search,
                status: state.status,
                brand: state.brand,
                type: state.type
            });

            const res = await fetch(`${API.list}?${queryParams.toString()}`);
            const result = await res.json();

            if (!result.status) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Gagal memuat data: ${escapeHtml(result.message)}</td></tr>`;
                return;
            }

            populateFilterOptions('brandFilter', result.brands, state.brand);
            populateFilterOptions('typeFilter', result.types, state.type);

            const items = result.data || [];
            state.totalPages = result.pagination.total_pages || 1;

            document.getElementById('paginationInfo').textContent = `Menampilkan ${items.length ? (state.page - 1) * state.limit + 1 : 0} - ${Math.min(state.page * state.limit, result.pagination.total_items)} dari ${result.pagination.total_items.toLocaleString()} unit`;
            document.getElementById('pageIndicator').textContent = `Halaman ${state.page} dari ${state.totalPages}`;

            document.getElementById('btnPrevPage').disabled = (state.page <= 1);
            document.getElementById('btnNextPage').disabled = (state.page >= state.totalPages);

            if (!items.length) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted font-weight-bold">Tidak ada unit ONT yang sesuai kriteria.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(item => {
                const statusBadge = `<span class="badge badge-status-${item.status} px-3 py-1">${item.status}</span>`;
                const custInfo = item.current_netpay_id 
                    ? `<div><strong class="text-dark">${escapeHtml(item.customer_name || 'Customer')}</strong></div><small class="text-muted">ID: ${escapeHtml(item.current_netpay_id)}</small>`
                    : `<span class="text-muted font-italic">&mdash; Gudang (Belum Terpasang)</span>`;
                
                html += `
                    <tr>
                        <td><strong class="text-primary font-weight-bold">${escapeHtml(item.ont_id)}</strong></td>
                        <td><code class="text-dark font-weight-bolder font-size-sm">${escapeHtml(item.serial_number)}</code></td>
                        <td>
                            <div class="font-weight-bold text-dark">${escapeHtml(item.brand || '-')}</div>
                            <small class="text-muted">${escapeHtml(item.type_ont || '-')}</small>
                        </td>
                        <td><small class="text-muted font-weight-bold">${escapeHtml(item.mac_address || '-')}</small></td>
                        <td>${statusBadge}</td>
                        <td>${custInfo}</td>
                        <td><small class="text-muted">${formatDate(item.updated_at)}</small></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-light-primary font-weight-bold mr-1 btn-detail" data-id="${escapeHtml(item.ont_id)}" title="Detail Unit & History">👁️ Detail</button>
                            <button type="button" class="btn btn-xs btn-light-warning font-weight-bold btn-edit-status" data-id="${escapeHtml(item.ont_id)}" data-sn="${escapeHtml(item.serial_number)}" data-status="${escapeHtml(item.status)}" data-netpay="${escapeHtml(item.current_netpay_id || '')}" data-note="${escapeHtml(item.condition_note || '')}" title="Ubah Status">✏️ Status</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            tbody.querySelectorAll('.btn-detail').forEach(btn => {
                btn.addEventListener('click', () => openDrawer(btn.dataset.id));
            });

            tbody.querySelectorAll('.btn-edit-status').forEach(btn => {
                btn.addEventListener('click', () => openUpdateStatusModal(btn.dataset));
            });

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-5">Gagal menghubungi server: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    function populateFilterOptions(selectId, items, currentValue) {
        const select = document.getElementById(selectId);
        if (select.children.length > 1) return;

        items.forEach(val => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val;
            if (val === currentValue) opt.selected = true;
            select.appendChild(opt);
        });
    }

    // LEVEL 3: Open Drawer Detail Unit
    async function openDrawer(ontId) {
        const drawer = document.getElementById('ontDrawer');
        const backdrop = document.getElementById('ontDrawerBackdrop');
        const spinner = document.getElementById('drawerSpinner');
        const content = document.getElementById('drawerContent');

        drawer.classList.add('open');
        backdrop.classList.add('show');
        spinner.style.display = 'block';
        content.style.display = 'none';

        try {
            const res = await fetch(`${API.detail}?ont_id=${encodeURIComponent(ontId)}`);
            const result = await res.json();

            if (!result.status) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', result.message, 'error');
                closeDrawer();
                return;
            }

            const ont = result.ont;
            state.activeOnt = ont;

            document.getElementById('drawerOntId').textContent = ont.ont_id;
            document.getElementById('drawerSn').textContent = 'SN: ' + ont.serial_number;
            document.getElementById('drawerStatusText').textContent = ont.status;
            
            const banner = document.getElementById('drawerStatusBanner');
            banner.className = `p-4 rounded mb-5 d-flex align-items-center justify-content-between badge-status-${ont.status}`;

            document.getElementById('drawerBrand').textContent = ont.brand || '-';
            document.getElementById('drawerType').textContent = ont.type_ont || '-';
            document.getElementById('drawerMac').textContent = ont.mac_address || '-';
            document.getElementById('drawerCreated').textContent = formatDate(ont.created_at);
            document.getElementById('drawerCondition').textContent = ont.condition_note || '-';

            const custCard = document.getElementById('drawerCustomerCard');
            if (ont.current_netpay_id) {
                custCard.style.display = 'block';
                document.getElementById('drawerNetpayId').textContent = ont.current_netpay_id;
                document.getElementById('drawerCustName').textContent = ont.customer_name || '-';
                document.getElementById('drawerCustPhone').textContent = ont.customer_phone || '-';
                document.getElementById('drawerCustAlamat').textContent = ont.customer_alamat || '-';
            } else {
                custCard.style.display = 'none';
            }

            const timeline = document.getElementById('drawerTimeline');
            const movements = result.movements || [];

            if (!movements.length) {
                timeline.innerHTML = `<div class="text-muted font-size-sm font-italic">Belum ada riwayat pergerakan tercatat.</div>`;
            } else {
                let timeHtml = '';
                movements.forEach(m => {
                    const eventBadgeClass = `badge-status-${m.event_type === 'INSTALL' ? 'IN_USE' : (m.event_type === 'DISMANTLE' ? 'DAMAGED' : 'IN_STOCK')}`;
                    const custStr = m.netpay_id ? ` &bull; Customer ID: <strong>${escapeHtml(m.netpay_id)}</strong> (${escapeHtml(m.customer_name || '')})` : '';
                    const techStr = m.tech_name ? ` &bull; Teknisi: <strong>${escapeHtml(m.tech_name)}</strong>` : '';

                    timeHtml += `
                        <div class="timeline-item">
                            <div class="timeline-dot ${escapeHtml(m.event_type)}"></div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge ${eventBadgeClass} px-2 py-1 font-size-xs">${escapeHtml(m.event_type)}</span>
                                <small class="text-muted font-weight-bold">${formatDate(m.event_date)}</small>
                            </div>
                            <div class="font-size-xs text-dark mt-1">
                                ${custStr} ${techStr}
                            </div>
                            <div class="text-muted font-size-xs mt-1 bg-light p-2 rounded">
                                ${escapeHtml(m.notes || '-')}
                            </div>
                        </div>
                    `;
                });
                timeline.innerHTML = timeHtml;
            }

            spinner.style.display = 'none';
            content.style.display = 'block';

        } catch (err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memuat detail ONT: ' + err.message, 'error');
            closeDrawer();
        }
    }

    function closeDrawer() {
        document.getElementById('ontDrawer').classList.remove('open');
        document.getElementById('ontDrawerBackdrop').classList.remove('show');
    }

    document.getElementById('btnCloseDrawer').addEventListener('click', closeDrawer);
    document.getElementById('ontDrawerBackdrop').addEventListener('click', closeDrawer);

    document.getElementById('btnEditStatusDrawer').addEventListener('click', function() {
        if (state.activeOnt) {
            closeDrawer();
            openUpdateStatusModal({
                id: state.activeOnt.ont_id,
                sn: state.activeOnt.serial_number,
                status: state.activeOnt.status,
                netpay: state.activeOnt.current_netpay_id || '',
                note: state.activeOnt.condition_note || ''
            });
        }
    });

    // Stock In Modal Handler
    document.getElementById('btnOpenStockIn').addEventListener('click', function() {
        document.getElementById('formStockIn').reset();
        $('#modalStockIn').modal('show');
    });

    document.getElementById('formStockIn').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitStockIn');
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm mr-2"></span>Menyimpan...`;

        try {
            const formData = new FormData(this);
            const res = await fetch(API.create, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (!result.status) {
                if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                return;
            }

            $('#modalStockIn').modal('hide');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil!', result.message, 'success');
            }
            loadGroupData();
            loadUnitData();

        } catch (err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal mengirim data: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan ke Stok Gudang';
        }
    });

    // Update Status Modal Handler
    function openUpdateStatusModal(data) {
        document.getElementById('us_ont_id').value = data.id;
        document.getElementById('us_display_ont_id').textContent = data.id;
        document.getElementById('us_display_sn').textContent = data.sn;
        document.getElementById('us_status').value = data.status;
        document.getElementById('us_netpay_id').value = data.netpay || '';
        document.getElementById('us_condition_note').value = data.note || '';

        toggleNetpayInput(data.status);
        $('#modalUpdateStatus').modal('show');
    }

    document.getElementById('us_status').addEventListener('change', function(e) {
        toggleNetpayInput(e.target.value);
    });

    function toggleNetpayInput(status) {
        const group = document.getElementById('groupUsNetpay');
        group.style.display = (status === 'IN_USE') ? 'block' : 'none';
    }

    document.getElementById('formUpdateStatus').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitUpdateStatus');
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm mr-2"></span>Menyimpan...`;

        try {
            const formData = new FormData(this);
            const res = await fetch(API.updateStatus, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();

            if (!result.status) {
                if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                return;
            }

            $('#modalUpdateStatus').modal('hide');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Berhasil!', result.message, 'success');
            }
            loadGroupData();
            loadUnitData();

        } catch (err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal memperbarui status: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Simpan Perubahan';
        }
    });

    // Helper utilities
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
});
</script>
