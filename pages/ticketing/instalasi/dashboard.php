<?php
require_once __DIR__ . '/../../../includes/config.php';

// Base path untuk endpoint API di folder ini
$apiBase = BASE_URL . 'pages/ticketing/instalasi/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JTracks — Ticketing Instalasi (IKR)</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
  :root {
    --bg: #F3F5F3;
    --surface: #FFFFFF;
    --surface-alt: #FAFAF8;
    --border: #E1E5E1;
    --text: #1B2321;
    --text-muted: #667169;
    --accent: #0E7C7B;
    --accent-dark: #0B6362;
    --accent-soft: #E4F2F1;
    --radius: 10px;
    --shadow: 0 10px 30px rgba(20, 30, 28, 0.10);

    --pending-bg: #FDF3D8; --pending-text: #8A5A00;
    --active-bg:  #E4EEFD; --active-text:  #1B4B91;
    --reschedule-bg: #EFE7FB; --reschedule-text: #5B39A8;
    --cancel-bg:  #FBE6E6; --cancel-text:  #B33A3A;
    --done-bg:    #E3F5E9; --done-text:    #1F7A45;
  }

  * {
    box-sizing: border-box;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.18) transparent;
  }

  /* ---------- Sleek Custom Scrollbar ---------- */
  ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }
  ::-webkit-scrollbar-track {
    background: transparent;
  }
  ::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 10px;
    transition: background 0.2s;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.35);
  }

  html, body {
    height: 100vh;
    max-height: 100vh;
    overflow: hidden !important;
    margin: 0;
    padding: 0;
  }

  body {
    display: flex;
    flex-direction: column;
    background: var(--bg);
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 14px;
  }

  /* ---------- Topbar ---------- */
  .topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 28px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 12px;
  }
  .topbar h1 { font-size: 17px; margin: 0; font-weight: 700; letter-spacing: -0.01em; }
  .topbar .sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
  .topbar-controls { display: flex; gap: 10px; align-items: center; }

  input, select, textarea {
    font-family: inherit;
    font-size: 13px;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 9px 11px;
    background: var(--surface);
    color: var(--text);
  }
  input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
  }
  input:disabled { background: var(--surface-alt); color: var(--text-muted); }

  button { font-family: inherit; cursor: pointer; }
  .btn-primary {
    background: var(--accent); color: #fff; border: none;
    padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px;
  }
  .btn-primary:hover { background: var(--accent-dark); }
  .btn-ghost {
    background: transparent; border: 1px solid var(--border); color: var(--text);
    padding: 10px 14px; border-radius: 8px; font-size: 13px;
  }
  .btn-ghost:hover { background: var(--surface-alt); }

  /* ---------- Content / table ---------- */
  .content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 14px 28px 14px;
    overflow: hidden;
    min-height: 0;
  }
  #kt_footer { display: none !important; }

  /* ---------- KPI Cards ---------- */
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 10px;
    margin-bottom: 10px;
    flex-shrink: 0;
  }
  .kpi-card {
    background: var(--surface);
    border-radius: 8px;
    padding: 8px 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .kpi-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
  }
  .kpi-total::after { background: var(--accent); }
  .kpi-pending::after { background: var(--pending-text); }
  .kpi-active::after { background: var(--active-text); }
  .kpi-reschedule::after { background: var(--reschedule-text); }
  .kpi-done::after { background: var(--done-text); }
  .kpi-kendala::after { background: #F59E0B; }

  .kpi-num {
    font-size: 18px;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
    min-width: 20px;
  }
  .kpi-label {
    font-size: 9.5px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    line-height: 1.2;
  }

  .table-wrap {
    flex: 1;
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow-y: auto;
    min-height: 0;
  }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  thead th {
    text-align: left; padding: 9px 8px; background: var(--surface-alt);
    border-bottom: 1px solid var(--border); font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.02em; color: var(--text-muted); position: sticky; top: 0;
    white-space: nowrap; vertical-align: middle; z-index: 2;
  }
  tbody td { padding: 9px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; line-height: 1.35; font-size: 12px; }
  tbody tr { cursor: pointer; transition: background 0.12s; }
  tbody tr:hover { background: var(--accent-soft); }
  tbody tr:last-child td { border-bottom: none; }

  tbody tr.row-has-issue {
    background: #FFFBEB !important;
  }
  tbody tr.row-has-issue:hover {
    background: #FEF3C7 !important;
  }
  tbody tr.row-has-issue td:first-child {
    box-shadow: inset 4px 0 0 0 #F59E0B;
  }

  tbody tr.group-header-row { cursor: default; }
  tbody tr.group-header-row:hover { background: transparent; }
  tbody tr.group-header-row td {
    padding: 14px 14px 4px 14px;
    border-bottom: none;
  }
  .group-header-content {
    background: #EAF6F5;
    color: var(--accent-dark);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
  }
  .group-count {
    color: #0E7C7B;
    font-size: 12px;
    font-weight: 400;
    opacity: 0.85;
  }

  .btn-share-img {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  }
  .btn-share-img:hover {
    background: var(--accent-dark);
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  }

  .btn-share-today {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(14, 124, 123, 0.2);
  }
  .btn-share-today:hover {
    background: var(--accent-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(14, 124, 123, 0.3);
  }

  .badge {
    display: inline-block; padding: 4px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
  }
  .badge-pending { background: var(--pending-bg); color: var(--pending-text); }
  .badge-active { background: var(--active-bg); color: var(--active-text); }
  .badge-reschedule { background: var(--reschedule-bg); color: var(--reschedule-text); }
  .badge-cancel { background: var(--cancel-bg); color: var(--cancel-text); }
  .badge-done { background: var(--done-bg); color: var(--done-text); }

  /* ---------- Modal & Drawer Overlay ---------- */
  .overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 21, 0.45); backdrop-filter: blur(2px);
    opacity: 0; pointer-events: none; transition: opacity 0.2s ease; z-index: 20;
  }
  .overlay.show { opacity: 1; pointer-events: auto; }

  /* ---------- Modal (tiket baru) ---------- */
  .modal {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -48%) scale(0.96);
    background: var(--surface); border-radius: 12px; box-shadow: var(--shadow);
    width: min(760px, 94vw); opacity: 0; pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease; z-index: 25;
  }
  .modal.show {
    opacity: 1; pointer-events: auto;
    transform: translate(-50%, -50%) scale(1);
  }
  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border);
  }
  .modal-header h2 { margin: 0; font-size: 15px; }
  .close-x {
    background: none; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer;
  }
  .modal-body { padding: 16px 20px; }
  .modal-footer {
    padding: 12px 20px; border-top: 1px solid var(--border);
    display: flex; justify-content: flex-end; gap: 8px; background: var(--surface-alt);
    border-radius: 0 0 12px 12px;
  }

  /* ---------- Drawer (detail tiket) ---------- */
  .drawer {
    position: fixed; top: 0; right: 0; height: 100%; width: min(580px, 92vw);
    background: var(--surface); box-shadow: -10px 0 30px rgba(0,0,0,0.10);
    transform: translateX(100%); transition: transform 0.25s ease; z-index: 30;
    display: flex; flex-direction: column;
  }
  .drawer.show { transform: translateX(0); }
  .drawer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid var(--border);
    background: var(--surface); z-index: 1; flex-shrink: 0;
  }
  .drawer-header h2 { margin: 0; font-size: 15px; }
  .drawer-body { padding: 16px 20px 20px; flex: 1; overflow-y: auto; }
  .drawer-footer {
    padding: 12px 20px; border-top: 1px solid var(--border);
    background: var(--surface); flex-shrink: 0; display: flex; gap: 10px;
  }

  .field { margin-bottom: 14px; }
  .field label { display: block; font-size: 11.5px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
  .field input, .field select, .field textarea { width: 100%; }
  .field-row { display: flex; gap: 10px; }
  .field-row .field { flex: 1; }

  .section-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    color: var(--accent-dark); margin: 18px 0 10px; letter-spacing: 0.04em;
    border-bottom: 1px solid var(--accent-soft); padding-bottom: 4px;
  }
  .section-title:first-child { margin-top: 0; }

  .readout { font-size: 13px; margin-bottom: 10px; }
  .readout .label { display: block; font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }

  .empty-state {
    text-align: center; padding: 40px 20px; color: var(--text-muted); font-size: 13px;
  }

  .pending-box {
    border: 1.5px dashed var(--border); border-radius: 10px; padding: 18px;
    text-align: center; color: var(--text-muted); font-size: 13px; background: var(--surface-alt);
  }
  .pending-box strong { display: block; color: var(--text); font-size: 13.5px; margin-bottom: 4px; }

  .tech-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--surface-alt); border: 1px solid var(--border); border-radius: 20px;
    padding: 3.5px 10px; font-size: 11.5px; font-weight: 600; color: var(--text);
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  .toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: var(--text); color: #fff; padding: 12px 20px; border-radius: 8px;
    font-size: 13px; opacity: 0; pointer-events: none; transition: opacity 0.2s, transform 0.2s; z-index: 50;
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  .toast.error { background: var(--cancel-text); }
</style>
</head>
<body>

  <div class="topbar">
    <div>
      <h1>Ticketing Instalasi (IKR)</h1>
      <div class="sub">Dashboard NOC — Tiket Pengerjaan Instalasi Pelanggan Baru</div>
    </div>
    <div class="topbar-controls">
      <input type="text" id="searchInput" placeholder="🔍 Cari Netpay ID / Nama / Perumahan..." style="width: 240px;">
      <select id="orderSelect" style="width: 110px;">
        <option value="DESC">Terbaru</option>
        <option value="ASC">Terlama</option>
      </select>
      <input type="month" id="monthInput" style="width: 140px;">
      <button type="button" class="btn-share-today" id="btnSalinTugasHariIni" title="Salin Gambar Screenshot Tugas Hari Ini">📋 Salin Tugas (Hari Ini)</button>
      <button class="btn-primary" id="btnOpenCreate">+ Tiket Baru</button>
    </div>
  </div>

  <div class="content">

    <!-- KPI Summary Cards -->
    <div class="kpi-row">
      <div class="kpi-card kpi-total">
        <div class="kpi-num" id="kpiTotal">0</div>
        <div class="kpi-label">Total Tiket</div>
      </div>
      <div class="kpi-card kpi-pending">
        <div class="kpi-num" id="kpiPending">0</div>
        <div class="kpi-label">Menunggu Teknisi</div>
      </div>
      <div class="kpi-card kpi-active">
        <div class="kpi-num" id="kpiActive">0</div>
        <div class="kpi-label">Proses Pengerjaan</div>
      </div>
      <div class="kpi-card kpi-reschedule">
        <div class="kpi-num" id="kpiRescheduled">0</div>
        <div class="kpi-label">Rescheduled</div>
      </div>
      <div class="kpi-card kpi-done">
        <div class="kpi-num" id="kpiDone">0</div>
        <div class="kpi-label">Selesai</div>
      </div>
      <div class="kpi-card kpi-kendala">
        <div class="kpi-num" id="kpiKendala">0</div>
        <div class="kpi-label">⚠️ Kendala Lapangan</div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width: 100px;">NETPAY ID</th>
            <th>NAMA PELANGGAN</th>
            <th style="width: 110px;">NO TLP</th>
            <th>PERUMAHAN &amp; ALAMAT</th>
            <th style="width: 100px;">PAKET</th>
            <th>CATATAN NOC / IKR</th>
            <th style="width: 120px;">TIM PENANGGUNG JAWAB</th>
            <th style="width: 90px;">TARGET</th>
            <th style="width: 100px;">TGL SERVICE</th>
            <th style="width: 110px; text-align: center;">STATUS</th>
          </tr>
        </thead>
        <tbody id="ticketTbody">
          <tr><td colspan="10" class="empty-state">Memuat data tiket instalasi…</td></tr>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Overlay -->
  <div class="overlay" id="overlay"></div>

  <!-- ============ MODAL: TIKET INSTALASI BARU ============ -->
  <div class="modal" id="modalTiket">
    <div class="modal-header">
      <h2>+ Registrasi &amp; Buat Tiket Instalasi (IKR) Baru</h2>
      <button class="close-x" id="closeCreateModal">&times;</button>
    </div>
    <div class="modal-body">
      
      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Nama Pelanggan *</label>
          <input type="text" id="f_nama" placeholder="Nama lengkap customer...">
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">No Tlp Contact *</label>
          <input type="text" id="f_no_tlp" placeholder="mis. 0895428474630">
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0; flex: 1.1;">
          <label style="margin-bottom: 4px;">Wilayah / Kode NetPay *</label>
          <select id="f_netpay_kode">
            <option value="">Pilih Kode Wilayah…</option>
            <option value="20">Cikarang - 20</option>
            <option value="21">Cikarang - 21</option>
            <option value="22">Cikarang - 22</option>
            <option value="52">Tasik Kab - 52</option>
            <option value="55">Tasik Kot - 55</option>
            <option value="27">Cipatat - 27</option>
            <option value="24">Indramayu - 24</option>
            <option value="28">Cibinong - 28</option>
          </select>
        </div>
        <div class="field" style="margin-bottom: 0; flex: 1;">
          <label style="margin-bottom: 4px;">NetPay ID Customer *</label>
          <div style="display: flex; gap: 6px;">
            <input type="text" id="f_netpay_id" placeholder="Pilih kode di samping..." readonly style="background: var(--surface-alt);">
            <button type="button" class="btn-ghost" id="btnEditNetpay" title="Edit Manual NetPay ID" style="padding: 0 12px; height: 38px;">✏️</button>
          </div>
        </div>
        <div class="field" style="margin-bottom: 0; flex: 1;">
          <label style="margin-bottom: 4px;">Paket Internet *</label>
          <select id="f_paket_internet">
            <option value="5">5 Mbps — Rp 150.000/bln</option>
            <option value="10" selected>10 Mbps — Rp 300.000/bln</option>
            <option value="30">30 Mbps — Rp 650.000/bln</option>
            <option value="50">50 Mbps — Rp 850.000/bln</option>
            <option value="100">100 Mbps — Rp 1.000.000/bln</option>
          </select>
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Perumahan (Pilih / Cari / Ketik) *</label>
          <input type="text" id="f_perumahan" list="perumahanList" placeholder="Ketik atau cari perumahan...">
          <datalist id="perumahanList"></datalist>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Alamat / Lokasi Pemasangan *</label>
          <input type="text" id="f_location" placeholder="Blok / No Rumah / RT RW...">
        </div>
      </div>

      <div class="field" style="margin-bottom: 10px;">
        <label style="margin-bottom: 4px;">Catatan / Instuksi IKR</label>
        <textarea id="f_catatan" rows="2" style="height: 48px; resize: vertical;" placeholder="Catatan khusus untuk teknisi IKR..."></textarea>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tim Penanggung Jawab *</label>
          <select id="f_tim"><option value="">Pilih tim…</option></select>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tgl Service *</label>
          <input type="date" id="f_tanggal">
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">NOC *</label>
          <select id="f_noc"><option value="">Pilih NOC…</option></select>
        </div>
      </div>

    </div>
    <div class="modal-footer" style="padding: 12px 20px;">
      <button class="btn-ghost" id="cancelModal">Batal</button>
      <button class="btn-primary" id="submitModal">Simpan &amp; Buat Tiket</button>
    </div>
  </div>

  <!-- ============ DRAWER: DETAIL TIKET INSTALASI ============ -->
  <div class="drawer" id="drawerDetail">
    <div class="drawer-header">
      <h2>Detail Tiket Instalasi (IKR)</h2>
      <button class="close-x" id="closeDrawer">&times;</button>
    </div>
    <div class="drawer-body" id="drawerBody">
      <div class="empty-state">Memuat…</div>
    </div>
    <div class="drawer-footer" id="drawerFooter">
      <button class="btn-ghost" id="deleteDrawerBtn" style="color:var(--cancel-text); border-color:var(--cancel-text);">Hapus Tiket</button>
      <button class="btn-primary" id="saveDrawerBtn" style="flex:1;">Simpan Perubahan</button>
    </div>
  </div>

  <!-- ============ CUSTOM CONFIRMATION MODAL ============ -->
  <div class="overlay" id="confirmOverlay" style="z-index: 100;"></div>
  <div class="modal" id="customConfirmModal" style="z-index: 101; max-width: 440px;">
    <div class="modal-header">
      <h2 id="confirmTitle">Konfirmasi</h2>
      <button class="close-x" id="confirmCloseX">&times;</button>
    </div>
    <div class="modal-body" id="confirmBody" style="font-size: 13px; color: var(--text);">
      Apakah Anda yakin ingin melanjutkan aksi ini?
    </div>
    <div class="modal-footer" style="padding: 12px 16px;">
      <button class="btn-ghost" id="confirmCancelBtn">Batal</button>
      <button class="btn-primary" id="confirmOkBtn">Lanjutkan</button>
    </div>
  </div>

  <div class="toast" id="toast"></div>

<script>
  const API_BASE = '<?= $apiBase ?>';
  const API = {
    list:          API_BASE + 'list_tickets.php',
    detail:        API_BASE + 'get_ticket_detail.php',
    create:        API_BASE + 'create_installation_ticket.php',
    update:        API_BASE + 'update_ticket.php',
    delete:        API_BASE + 'delete_ticket.php',
    listTim:       API_BASE + 'list_tim.php',
    listNoc:       API_BASE + 'list_noc.php',
    listPerumahan: API_BASE + 'list_perumahan.php',
    packages:      '<?= BASE_URL ?>api/packages.php',
    handleIssue:   API_BASE + 'handle_issue_report.php',
  };

  const STATUS_LABEL = {
    Pending:     'Menunggu Teknisi',
    Actived:     'Proses Pengerjaan',
    Rescheduled: 'Dijadwalkan Ulang',
    Cancelled:   'Dibatalkan',
    Done:        'Selesai',
  };
  const STATUS_CLASS = {
    Pending:     'badge-pending',
    Actived:     'badge-active',
    Rescheduled: 'badge-reschedule',
    Cancelled:   'badge-cancel',
    Done:        'badge-done',
  };
  const STATUS_OPTIONS = ['Pending', 'Actived', 'Rescheduled', 'Cancelled', 'Done'];

  const state = {
    tickets: [],
    timList: [],
    nocList: [],
    perumahanList: [],
    packageList: [],
    lastNocId: '',
    activeSchedule: null,
    pollTimer: null,
  };

  function fillPackageDropdown(selectEl, packages, selectedValue) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">Pilih Paket Internet…</option>';
    packages.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.paket;
      const priceFormatted = parseInt(p.harga).toLocaleString('id-ID');
      opt.textContent = `${p.name} — Rp ${priceFormatted}/bln`;
      if (String(p.paket) === String(selectedValue) || p.name === selectedValue) {
        opt.selected = true;
      }
      selectEl.appendChild(opt);
    });
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.toggle('error', isError);
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  function showConfirmDialog({ title, text, htmlBody, confirmText, confirmClass, onConfirm }) {
    document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
    const bodyEl = document.getElementById('confirmBody');
    if (htmlBody) {
      bodyEl.innerHTML = htmlBody;
    } else {
      bodyEl.textContent = text || 'Apakah Anda yakin?';
    }
    const okBtn = document.getElementById('confirmOkBtn');
    okBtn.textContent = confirmText || 'Lanjutkan';
    okBtn.className = 'btn-primary ' + (confirmClass || '');

    const overlay = document.getElementById('confirmOverlay');
    const modal = document.getElementById('customConfirmModal');

    function close() {
      overlay.classList.remove('show');
      modal.classList.remove('show');
      okBtn.onclick = null;
    }

    document.getElementById('confirmCancelBtn').onclick = close;
    document.getElementById('confirmCloseX').onclick = close;

    okBtn.onclick = async () => {
      close();
      if (onConfirm) await onConfirm();
    };

    overlay.classList.add('show');
    modal.classList.add('show');
  }

  function getTodayStr() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function formatDate(dStr) {
    if (!dStr) return '-';
    const d = new Date(dStr);
    if (isNaN(d)) return dStr;
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  function formatGroupDate(dStr) {
    if (!dStr || dStr === 'Tanpa Tanggal') return 'Tanpa Tanggal';
    const d = new Date(dStr);
    if (isNaN(d)) return dStr;
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  async function apiPost(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    return res.json();
  }

  // ---------------- Core App Logic ----------------

  document.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    const currentLocalMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    document.getElementById('monthInput').value = currentLocalMonth;

    loadTickets();
    startPolling();

    document.getElementById('monthInput').addEventListener('change', loadTickets);
    document.getElementById('orderSelect').addEventListener('change', loadTickets);
    document.getElementById('searchInput').addEventListener('input', renderTable);

    document.getElementById('btnOpenCreate').addEventListener('click', openCreateModal);
    document.getElementById('closeCreateModal').addEventListener('click', closeCreateModal);
    document.getElementById('cancelModal').addEventListener('click', closeCreateModal);
    document.getElementById('submitModal').addEventListener('click', handleSubmitTicket);

    document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
    document.getElementById('overlay').addEventListener('click', () => {
      closeCreateModal();
      closeDrawer();
    });

    document.getElementById('btnSalinTugasHariIni')?.addEventListener('click', copyTodayTasksImage);
    document.getElementById('saveDrawerBtn').onclick = handleSaveDrawer;
    document.getElementById('deleteDrawerBtn').onclick = handleDeleteDrawer;

    // Event saat Kode NetPay Wilayah dipilih
    document.getElementById('f_netpay_kode').addEventListener('change', async (e) => {
      const kode = e.target.value;
      const netpayInput = document.getElementById('f_netpay_id');
      if (!kode) {
        netpayInput.value = '';
        return;
      }
      try {
        const res = await fetch(`<?= BASE_URL ?>api/get_netpay_id.php?kode=${kode}`);
        const data = await res.json();
        if (data.status) {
          netpayInput.value = data.netpay_id;
        }
      } catch (err) {
        console.error(err);
      }
    });

    // Toggle edit manual NetPay ID
    let isNetpayEditable = false;
    document.getElementById('btnEditNetpay').addEventListener('click', () => {
      isNetpayEditable = !isNetpayEditable;
      const netpayInput = document.getElementById('f_netpay_id');
      const kodeSelect = document.getElementById('f_netpay_kode');
      const btn = document.getElementById('btnEditNetpay');

      if (isNetpayEditable) {
        netpayInput.readOnly = false;
        netpayInput.style.background = 'var(--surface)';
        netpayInput.focus();
        kodeSelect.disabled = true;
        btn.textContent = '✔️';
        btn.style.borderColor = 'var(--accent)';
        btn.style.color = 'var(--accent)';
      } else {
        netpayInput.readOnly = true;
        netpayInput.style.background = 'var(--surface-alt)';
        kodeSelect.disabled = false;
        btn.textContent = '✏️';
        btn.style.borderColor = 'var(--border)';
        btn.style.color = 'var(--text)';
      }
    });
  });

  async function loadTickets() {
    const bulan = document.getElementById('monthInput').value;
    const order = document.getElementById('orderSelect').value;

    try {
      const res = await apiPost(API.list, { bulan, order });
      if (!res.status) {
        showToast(res.message || 'Gagal memuat tiket', true);
        return;
      }
      state.tickets = res.data;
      updateKpis(state.tickets);
      renderTable();
    } catch (e) {
      showToast('Gagal terhubung ke server', true);
    }
  }

  function updateKpis(tickets) {
    const counts = { total: tickets.length, Pending: 0, Actived: 0, Rescheduled: 0, Cancelled: 0, Done: 0, Kendala: 0 };
    tickets.forEach(t => {
      if (counts[t.status] !== undefined) counts[t.status]++;
      if (t.issue_id && t.issue_status === 'Pending') counts.Kendala++;
    });

    document.getElementById('kpiTotal').textContent       = counts.total;
    document.getElementById('kpiPending').textContent     = counts.Pending;
    document.getElementById('kpiActive').textContent      = counts.Actived;
    document.getElementById('kpiRescheduled').textContent = counts.Rescheduled;
    document.getElementById('kpiDone').textContent        = counts.Done;
    document.getElementById('kpiKendala').textContent     = counts.Kendala;
  }

  function renderTable() {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    const tbody = document.getElementById('ticketTbody');

    const filteredTickets = state.tickets.filter(t => {
      if (!q) return true;
      return (
        (t.netpay_id && t.netpay_id.toLowerCase().includes(q)) ||
        (t.nama && t.nama.toLowerCase().includes(q)) ||
        (t.no_tlp && t.no_tlp.toLowerCase().includes(q)) ||
        (t.alamat && t.alamat.toLowerCase().includes(q)) ||
        (t.catatan && t.catatan.toLowerCase().includes(q))
      );
    });

    if (!filteredTickets.length) {
      tbody.innerHTML = '<tr><td colspan="10" class="empty-state">Tidak ada tiket instalasi ditemukan.</td></tr>';
      return;
    }

    // Kelompokkan tiket berdasarkan tanggal pembuatan tiket (prioritas: tanggal_dibuat, lalu tanggal_service)
    const groups = {};
    filteredTickets.forEach(t => {
      const dateKey = (t.tanggal_dibuat ? t.tanggal_dibuat.slice(0, 10) : (t.tanggal_service ? t.tanggal_service.slice(0, 10) : 'Tanpa Tanggal'));
      if (!groups[dateKey]) groups[dateKey] = [];
      groups[dateKey].push(t);
    });

    let html = '';
    Object.keys(groups).forEach(dateKey => {
      const groupTickets = groups[dateKey];
      const countStr = groupTickets.length + ' tiket';
      const formattedDate = formatGroupDate(dateKey);

      html += `
        <tr class="group-header-row">
          <td colspan="10">
            <div class="group-header-content">
              <strong>${escapeHtml(formattedDate)}</strong>
              <span class="group-count">(${countStr})</span>
            </div>
          </td>
        </tr>
      `;

      groupTickets.forEach(t => {
        const hasIssuePending = (t.issue_id && t.issue_status === 'Pending');
        const issueBadgeHtml = hasIssuePending
          ? `<span class="badge badge-reschedule" style="font-size:10px; margin-left:5px;" title="${escapeHtml(t.issue_type)}">⚠️ Kendala</span>`
          : '';
        const rowClassAttr = hasIssuePending ? 'class="row-has-issue"' : '';

        html += `
          <tr data-schedule="${escapeHtml(t.schedule_id)}" ${rowClassAttr}>
            <td style="font-weight: 600; white-space: nowrap;">${escapeHtml(t.netpay_id)}${issueBadgeHtml}</td>
            <td style="font-weight: 600;">${escapeHtml(t.nama || '-')}</td>
            <td style="white-space: nowrap;">${escapeHtml(t.no_tlp || '-')}</td>
            <td>${escapeHtml(t.alamat || '-')}</td>
            <td style="white-space: nowrap;">${escapeHtml(t.paket_internet)} Mbps</td>
            <td>${escapeHtml(t.catatan || '-')}</td>
            <td>${escapeHtml(t.tim_nama || '-')}</td>
            <td style="white-space: nowrap;">${escapeHtml(t.target_status || '-')}</td>
            <td style="white-space: nowrap;">${formatDate(t.tanggal_service)}</td>
            <td style="text-align: center;"><span class="badge ${STATUS_CLASS[t.status] || ''}">${STATUS_LABEL[t.status] || t.status}</span></td>
          </tr>
        `;
      });
    });

    tbody.innerHTML = html;

    tbody.querySelectorAll('tr[data-schedule]').forEach(row => {
      row.addEventListener('click', () => openDrawer(row.dataset.schedule));
    });
  }

  async function copyTodayTasksImage() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayStr = `${year}-${month}-${day}`;

    // Filter tiket berstatus 'Pending' (Menunggu Teknisi) berdasarkan TANGGAL SERVICE Hari Ini
    const groupTickets = state.tickets.filter(t => {
      const tDate = (t.tanggal_service ? t.tanggal_service.slice(0, 10) : (t.tanggal_dibuat ? t.tanggal_dibuat.slice(0, 10) : ''));
      return tDate === todayStr && t.status === 'Pending';
    });

    const formattedToday = formatGroupDate(todayStr);

    if (!groupTickets.length) {
      showToast(`Tidak ada tiket instalasi "Menunggu Teknisi" untuk Hari Ini (${formattedToday})`, true);
      return;
    }

    showToast('Memproses screenshot gambar tugas hari ini...');

    const card = document.createElement('div');
    card.style.position = 'fixed';
    card.style.left = '-9999px';
    card.style.top = '-9999px';
    card.style.width = '720px';
    card.style.background = '#F8FAFC';
    card.style.padding = '24px';
    card.style.borderRadius = '16px';
    card.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    card.style.boxShadow = '0 10px 30px rgba(0,0,0,0.08)';
    card.style.color = '#0F172A';

    let itemsHtml = '';
    groupTickets.forEach((t, idx) => {
      itemsHtml += `
        <div style="
          background: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0;
          padding: 14px 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; gap: 12px;
          position: relative; overflow: hidden; margin-bottom: 10px;
        ">
          <!-- Status Left Bar (Teal Accent) -->
          <div style="width: 5px; background: #0E7C7B; border-radius: 4px; flex-shrink: 0;"></div>

          <div style="flex: 1;">
            <!-- Baris Atas: Nomor, Netpay ID, Paket, & PIC -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <span style="
                  background: #0E7C7B; color: #FFFFFF; font-size: 11px; font-weight: 800;
                  width: 22px; height: 22px; border-radius: 50%; display: inline-flex;
                  align-items: center; justify-content: center;
                ">${idx + 1}</span>
                <span style="font-size: 14px; font-weight: 800; color: #0F172A; letter-spacing: -0.01em;">
                  ${escapeHtml(t.netpay_id)}
                </span>
                <span style="
                  background: #E0F2FE; color: #0284C7; font-size: 11px; font-weight: 800;
                  padding: 3px 9px; border-radius: 6px; border: 1px solid #BAE6FD;
                ">
                  📶 ${escapeHtml(t.paket_internet || '-')} Mbps
                </span>
                <span style="
                  background: #F1F5F9; color: #475569; font-size: 11px; font-weight: 700;
                  padding: 3px 9px; border-radius: 6px; border: 1px solid #E2E8F0;
                ">
                  👥 ${escapeHtml(t.tim_nama || 'Belum Ditugaskan')}
                </span>
              </div>
            </div>

            <!-- Baris Tengah: Info Pelanggan & Alamat -->
            <div style="font-size: 12.5px; line-height: 1.5; color: #334155; margin-bottom: 6px;">
              <strong style="color: #0F172A; font-size: 13.5px;">👤 ${escapeHtml(t.nama || '-')}</strong>
              <span style="color: #CBD5E1; margin: 0 6px;">|</span>
              <span>📞 ${escapeHtml(t.no_tlp || '-')}</span>
              <div style="color: #475569; margin-top: 3px;">
                🏠 <strong>Alamat:</strong> ${escapeHtml(t.alamat || '-')}
              </div>
            </div>

            <!-- Baris Bawah: Catatan IKR -->
            <div style="
              background: #F0FDF4; border: 1px solid #DCFCE7; border-radius: 8px;
              padding: 10px 12px; font-size: 12px; color: #166534; font-weight: 600;
            ">
              📝 <strong>Catatan IKR:</strong> ${escapeHtml(t.catatan || '-')}
            </div>
          </div>
        </div>
      `;
    });

    card.innerHTML = `
      <div style="
        background: linear-gradient(135deg, #0B6362 0%, #0E7C7B 100%);
        padding: 20px 24px; border-radius: 12px; color: #FFFFFF; margin-bottom: 16px;
        box-shadow: 0 4px 14px rgba(14, 124, 123, 0.25);
        display: flex; justify-content: space-between; align-items: center;
      ">
        <div>
          <div style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 800; opacity: 0.85; margin-bottom: 4px;">
            JTRACKS NOC DISPATCH SYSTEM
          </div>
          <h2 style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.01em;">
            📋 LIST TUGAS INSTALASI (IKR)
          </h2>
          <div style="font-size: 13.5px; font-weight: 700; margin-top: 4px; opacity: 0.95;">
            📅 Hari Ini (${escapeHtml(formattedToday)})
          </div>
        </div>
        <div style="
          background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px);
          padding: 8px 16px; border-radius: 20px; font-weight: 800; font-size: 13px;
          border: 1px solid rgba(255, 255, 255, 0.3); text-align: center; white-space: nowrap;
        ">
          ${groupTickets.length} TIKET INSTALASI
        </div>
      </div>

      <div style="display: flex; flex-direction: column;">
        ${itemsHtml}
      </div>

      <div style="
        margin-top: 14px; padding-top: 10px; border-top: 1px dashed #CBD5E1;
        display: flex; justify-content: space-between; align-items: center;
        font-size: 11px; color: #64748B; font-weight: 500;
      ">
        <span>🔒 Dokumen Resmi NOC JTracks System</span>
        <span>Dicetak: ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB</span>
      </div>
    `;

    document.body.appendChild(card);

    try {
      const canvas = await html2canvas(card, { scale: 2, useCORS: true });
      document.body.removeChild(card);

      canvas.toBlob(async (blob) => {
        if (!blob) {
          showToast('Gagal memproses gambar', true);
          return;
        }

        try {
          await navigator.clipboard.write([
            new ClipboardItem({ 'image/png': blob })
          ]);
          showToast('📋 Gambar Tugas Hari Ini disalin ke Clipboard! Silakan paste (Ctrl+V) di grup WhatsApp');
        } catch (clipErr) {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = `List_Instalasi_HariIni_${todayStr}.png`;
          a.click();
          URL.revokeObjectURL(url);
          showToast('📁 Gambar terdownload ke komputer! Silakan upload ke WhatsApp');
        }
      }, 'image/png');

    } catch (err) {
      if (document.body.contains(card)) document.body.removeChild(card);
      showToast('Gagal menyalin gambar: ' + err.message, true);
    }
  }

  function startPolling() {
    if (state.pollTimer) clearInterval(state.pollTimer);
    state.pollTimer = setInterval(loadTickets, 12000);
  }

  // ---------------- Modal: Tiket Baru ----------------

  async function ensureDropdownsLoaded() {
    if (!state.timList.length) {
      const res = await apiPost(API.listTim, {});
      if (res.status) state.timList = res.data;
    }
    if (!state.nocList.length) {
      const res = await apiPost(API.listNoc, {});
      if (res.status) state.nocList = res.data;
    }
    if (!state.perumahanList.length) {
      const res = await apiPost(API.listPerumahan, {});
      if (res.status) state.perumahanList = res.data;
    }
    if (!state.packageList.length) {
      try {
        const res = await fetch(API.packages);
        const data = await res.json();
        if (data.status) state.packageList = data.data;
      } catch (err) { console.error('Failed to load packages:', err); }
    }
  }

  function fillDropdown(selectEl, items, valueKey, labelKey, selectedValue) {
    const placeholder = selectEl.options[0];
    selectEl.innerHTML = '';
    selectEl.appendChild(placeholder);
    items.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item[valueKey];
      opt.textContent = item[labelKey];
      if (item[valueKey] === selectedValue) opt.selected = true;
      selectEl.appendChild(opt);
    });
  }

  function getDefaultTimId(timList) {
    if (!timList || !timList.length) return '';
    // 1. Cek berdasarkan nama mengandung "IKR" & "SERVICE" (bebas huruf kecil/besar)
    const foundByName = timList.find(t => {
      if (!t.nama) return false;
      const upper = t.nama.toUpperCase();
      return upper.includes('IKR') && upper.includes('SERVICE');
    });
    if (foundByName) return foundByName.tim_id;
    // 2. Fallback berdasarkan ID TIM20260225115126
    const foundById = timList.find(t => t.tim_id === 'TIM20260225115126');
    if (foundById) return foundById.tim_id;
    return '';
  }

  async function openCreateModal() {
    await ensureDropdownsLoaded();

    document.getElementById('f_nama').value = '';
    document.getElementById('f_no_tlp').value = '';
    document.getElementById('f_netpay_kode').value = '';
    document.getElementById('f_netpay_kode').disabled = false;
    
    fillPackageDropdown(document.getElementById('f_paket_internet'), state.packageList, '10');
    
    const netpayInput = document.getElementById('f_netpay_id');
    netpayInput.value = '';
    netpayInput.readOnly = true;
    netpayInput.style.background = 'var(--surface-alt)';

    const btnEdit = document.getElementById('btnEditNetpay');
    btnEdit.textContent = '✏️';
    btnEdit.style.borderColor = 'var(--border)';
    btnEdit.style.color = 'var(--text)';

    document.getElementById('f_perumahan').value = '';
    document.getElementById('f_location').value = '';
    document.getElementById('f_catatan').value = '';
    document.getElementById('f_tanggal').value = getTodayStr();

    // Populate perumahan datalist options
    const listEl = document.getElementById('perumahanList');
    listEl.innerHTML = '';
    state.perumahanList.forEach(pName => {
      const opt = document.createElement('option');
      opt.value = pName;
      listEl.appendChild(opt);
    });

    const defaultTimId = getDefaultTimId(state.timList);
    fillDropdown(document.getElementById('f_tim'), state.timList, 'tim_id', 'nama', defaultTimId);
    fillDropdown(document.getElementById('f_noc'), state.nocList, 'admin_id', 'name', state.lastNocId);

    document.getElementById('overlay').classList.add('show');
    document.getElementById('modalTiket').classList.add('show');
  }

  function closeCreateModal() {
    document.getElementById('overlay').classList.remove('show');
    document.getElementById('modalTiket').classList.remove('show');
  }

  async function handleSubmitTicket() {
    const payload = {
      name:            document.getElementById('f_nama').value.trim(),
      phone_contact:   document.getElementById('f_no_tlp').value.trim(),
      netpay_id:       document.getElementById('f_netpay_id').value.trim(),
      paket_internet:  document.getElementById('f_paket_internet').value,
      perumahan:       document.getElementById('f_perumahan').value.trim(),
      location:        document.getElementById('f_location').value.trim(),
      catatan:         document.getElementById('f_catatan').value.trim(),
      tim_id:          document.getElementById('f_tim').value,
      tanggal_service: document.getElementById('f_tanggal').value,
      noc_id:          document.getElementById('f_noc').value,
    };

    if (!payload.name || !payload.phone_contact || !payload.netpay_id || !payload.perumahan || !payload.location || !payload.tim_id || !payload.tanggal_service || !payload.noc_id) {
      showToast('Semua field wajib (Nama, No Tlp, Netpay ID, Perumahan, Alamat, Tim, Tgl Service, NOC) harus diisi', true);
      return;
    }

    const btn = document.getElementById('submitModal');
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';

    try {
      const res = await apiPost(API.create, payload);
      if (!res.status) {
        showToast(res.message || 'Gagal menyimpan registrasi & tiket', true);
        return;
      }
      state.lastNocId = payload.noc_id;
      showToast('Registrasi & tiket instalasi berhasil disimpan');
      closeCreateModal();
      loadTickets();
    } catch (e) {
      showToast('Gagal menghubungi server', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Simpan Tiket';
    }
  }

  // ---------------- Drawer: Detail Tiket ----------------

  async function openDrawer(scheduleId) {
    state.activeSchedule = scheduleId;
    document.getElementById('overlay').classList.add('show');
    document.getElementById('drawerDetail').classList.add('show');
    document.getElementById('drawerBody').innerHTML =
      '<div class="empty-state"><span class="spinner"></span> Memuat detail tiket…</div>';

    try {
      await ensureDropdownsLoaded();
      const res = await apiPost(API.detail, { schedule_id: scheduleId });
      if (!res.status) {
        document.getElementById('drawerBody').innerHTML =
          `<div class="empty-state">${escapeHtml(res.message || 'Gagal memuat detail')}</div>`;
        return;
      }
      renderDrawer(res.data);
    } catch (e) {
      document.getElementById('drawerBody').innerHTML =
        '<div class="empty-state">Gagal menghubungi server.</div>';
    }
  }

  function closeDrawer() {
    document.getElementById('overlay').classList.remove('show');
    document.getElementById('drawerDetail').classList.remove('show');
    state.activeSchedule = null;
  }

  function renderDrawer(d) {
    const teknisiPills = (d.teknisi && d.teknisi.length)
      ? d.teknisi.map(tName => `<span class="tech-pill">👤 ${escapeHtml(tName)}</span>`).join('')
      : '<span style="color:var(--text-muted); font-size:12px;">-</span>';

    const hasilPengerjaanHtml = d.sn ? `
      <!-- Time Stats Row -->
      <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:12px; background:var(--surface-alt); padding:10px 12px; border-radius:10px; border:1px solid var(--border);">
        <div style="text-align:center;">
          <div style="font-size:10.5px; font-weight:600; color:var(--text-muted); text-transform:uppercase; margin-bottom:2px;">Jam Mulai</div>
          <div style="font-size:13px; font-weight:700; color:var(--text);">${escapeHtml(d.jam_mulai || '-')}</div>
        </div>
        <div style="text-align:center; border-left:1px solid var(--border); border-right:1px solid var(--border);">
          <div style="font-size:10.5px; font-weight:600; color:var(--text-muted); text-transform:uppercase; margin-bottom:2px;">Jam Selesai</div>
          <div style="font-size:13px; font-weight:700; color:var(--text);">${escapeHtml(d.jam_selesai || '-')}</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:10.5px; font-weight:600; color:var(--text-muted); text-transform:uppercase; margin-bottom:2px;">Durasi</div>
          <div style="font-size:12.5px; font-weight:700; color:var(--accent-dark);">${escapeHtml(d.durasi || '-')}</div>
        </div>
      </div>

      <!-- Hardware Specs Box -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px; background:var(--surface-alt); padding:12px; border-radius:10px; border:1px solid var(--border);">
        <div class="readout" style="margin-bottom:0;"><span class="label">SN ONT</span><strong>${escapeHtml(d.sn || '-')}</strong></div>
        <div class="readout" style="margin-bottom:0;"><span class="label">Tipe ONT</span><strong>${escapeHtml(d.type_ont || '-')}</strong></div>
        <div class="readout" style="margin-bottom:0;"><span class="label">Redaman</span><strong>${escapeHtml(d.redaman || '-')} dBm</strong></div>
        <div class="readout" style="margin-bottom:0;"><span class="label">ODP No</span>${escapeHtml(d.odp_no || '-')}</div>
        <div class="readout" style="margin-bottom:0;"><span class="label">ODC No</span>${escapeHtml(d.odc_no || '-')}</div>
        <div class="readout" style="margin-bottom:0;"><span class="label">JC No</span>${escapeHtml(d.jc_no || '-')}</div>
        <div class="readout" style="margin-bottom:0;"><span class="label">MAC Sebelum</span>${escapeHtml(d.mac_sebelum || '-')}</div>
        <div class="readout" style="margin-bottom:0;"><span class="label">MAC Sesudah</span>${escapeHtml(d.mac_sesudah || '-')}</div>
      </div>

      <!-- Technician PIC Pills -->
      <div style="margin-bottom:12px;">
        <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; color:var(--text-muted); margin-bottom:6px; letter-spacing:0.03em;">Teknisi (PIC Lapangan)</div>
        <div style="display:flex; flex-wrap:wrap; gap:6px;">${teknisiPills}</div>
      </div>
    ` : `
      <div class="pending-box">
        <strong>Menunggu laporan pengerjaan IKR</strong>
        Kolom ini keisi otomatis begitu teknisi submit laporan hasil pengerjaan instalasi.
      </div>
    `;

    const issueHtml = d.issue_report ? `
      <div class="section-title">Laporan Kendala Teknisi</div>
      <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:14px; margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span style="background:#FEF3C7; color:#92400E; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px;">
            ⚠️ KENDALA LAPANGAN (${escapeHtml(d.issue_report.issue_type)})
          </span>
          <span style="font-size:11px; color:#B45309; font-weight:600;">STATUS: ${escapeHtml(d.issue_report.status).toUpperCase()}</span>
        </div>
        <div style="font-size:12.5px; color:#78350F; margin-bottom:10px; line-height:1.4;">
          ${escapeHtml(d.issue_report.deskripsi || 'Tidak ada deskripsi.')}
        </div>
        ${d.issue_report.status === 'Pending' ? `
        <div style="display:flex; gap:8px; margin-top:10px;">
          <button type="button" class="btn-ghost" id="btnApproveIssue" style="flex:1; font-size:11.5px; padding:6px; background:#FEF2F2; color:#991B1B; border-color:#FCA5A5;">
            ✖️ Setujui Kendala &amp; Batalkan
          </button>
          <button type="button" class="btn-ghost" id="btnRescheduleIssue" style="flex:1; font-size:11.5px; padding:6px; background:#FFFBEB; color:#92400E; border-color:#FCD34D;">
            📅 Reschedule Service
          </button>
        </div>` : ''}
      </div>
    ` : '';

    document.getElementById('drawerBody').innerHTML = `
      <div class="section-title">Edit Data Pelanggan &amp; Registrasi</div>
      <div style="background:var(--surface-alt); padding:14px; border-radius:10px; border:1px solid var(--border); margin-bottom:16px;">
        <div class="field-row" style="margin-bottom: 10px;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Netpay ID (Readonly)</label>
            <input type="text" value="${escapeHtml(d.netpay_id)}" disabled style="background:#F1F5F9; font-weight:700; color:var(--accent-dark);">
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Nama Pelanggan</label>
            <input type="text" id="d_nama" value="${escapeHtml(d.nama || '')}" placeholder="Nama Pelanggan">
          </div>
        </div>

        <div class="field-row" style="margin-bottom: 10px;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">No Tlp Contact</label>
            <input type="text" id="d_no_tlp" value="${escapeHtml(d.phone_contact || d.no_tlp || '')}" placeholder="Nomor Telepon">
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Paket Internet</label>
            <select id="d_paket_internet"><option value="">Pilih Paket Internet…</option></select>
          </div>
        </div>

        <div class="field-row" style="margin-bottom: 0;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Perumahan</label>
            <input type="text" id="d_perumahan" value="${escapeHtml(d.perumahan || '')}" placeholder="Nama Perumahan">
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Detail Alamat / Lokasi</label>
            <input type="text" id="d_location" value="${escapeHtml(d.location || '')}" placeholder="Blok / Keterangan Lokasi">
          </div>
        </div>
      </div>

      ${issueHtml}

      <div class="section-title">Edit Data Penugasan Instalasi</div>
      
      <div class="field" style="margin-bottom: 10px;">
        <label style="margin-bottom: 4px;">Tim Penanggung Jawab</label>
        <select id="d_tim"><option value="">Pilih tim…</option></select>
      </div>

      <div class="field" style="margin-bottom: 10px;">
        <label style="margin-bottom: 4px;">Catatan NOC / Instuksi IKR</label>
        <textarea id="d_catatan" rows="2" style="height: 48px; resize: vertical;">${escapeHtml(d.catatan_noc || d.catatan_ikr || '')}</textarea>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tgl Service</label>
          <input type="date" id="d_tanggal" value="${escapeHtml(d.tanggal_service || '')}">
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">NOC</label>
          <select id="d_noc"><option value="">Pilih NOC…</option></select>
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 16px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Status Tiket</label>
          <select id="d_status">
            ${STATUS_OPTIONS.map(s =>
              `<option value="${s}" ${s === d.status ? 'selected' : ''}>${STATUS_LABEL[s]}</option>`
            ).join('')}
          </select>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Alasan / Reason</label>
          <input type="text" id="d_reason" value="${escapeHtml(d.reason || '')}" placeholder="Opsional...">
        </div>
      </div>

      <div class="section-title">Hasil Pengerjaan Teknisi (IKR)</div>
      ${hasilPengerjaanHtml}
    `;

    fillDropdown(document.getElementById('d_tim'), state.timList, 'tim_id', 'nama', d.tech_id);
    fillDropdown(document.getElementById('d_noc'), state.nocList, 'admin_id', 'name', d.noc_id);
    fillPackageDropdown(document.getElementById('d_paket_internet'), state.packageList, d.paket_internet);

    if (d.issue_report && d.issue_report.status === 'Pending') {
      const btnApprove = document.getElementById('btnApproveIssue');
      const btnReschedule = document.getElementById('btnRescheduleIssue');
      const btnReject  = document.getElementById('btnRejectIssue');

      if (btnApprove) {
        btnApprove.addEventListener('click', () => {
          showConfirmDialog({
            title: 'Setujui Kendala & Batalkan Tiket',
            text: 'Apakah Anda yakin ingin menyetujui laporan kendala ini? Status tiket instalasi akan diubah menjadi Cancelled (Dibatalkan).',
            confirmText: 'Setujui & Batalkan Tiket',
            confirmClass: 'btn-primary',
            onConfirm: async () => {
              try {
                const res = await apiPost(API.handleIssue, { action: 'approve', issue_id: d.issue_report.issue_id, schedule_id: d.schedule_id });
                showToast(res.message, !res.status);
                if (res.status) { openDrawer(d.schedule_id); loadTickets(); }
              } catch(e) { showToast('Gagal memproses permintaan', true); }
            }
          });
        });
      }

      if (btnReschedule) {
        btnReschedule.addEventListener('click', () => {
          showConfirmDialog({
            title: 'Setujui Kendala & Reschedule Tiket',
            text: 'Tentukan tanggal service baru dan alasan untuk mereschedule tiket ini:',
            confirmText: 'Simpan Reschedule',
            htmlBody: `
              <div style="margin-bottom:12px;">
                <label style="font-size:11.5px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">TGL SERVICE BARU</label>
                <input type="date" id="dlg_new_date" style="width:100%;" value="${getTodayStr()}">
              </div>
              <div>
                <label style="font-size:11.5px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">ALASAN RESCHEDULE</label>
                <input type="text" id="dlg_reason" style="width:100%;" value="Reschedule Kendala: ${escapeHtml(d.issue_report.issue_type)}">
              </div>
            `,
            onConfirm: async () => {
              const new_date = document.getElementById('dlg_new_date').value;
              const reason = document.getElementById('dlg_reason').value;
              if (!new_date) { showToast('Tanggal baru belum dipilih', true); return; }
              try {
                const res = await apiPost(API.handleIssue, { action: 'reschedule', issue_id: d.issue_report.issue_id, schedule_id: d.schedule_id, new_date, reason });
                showToast(res.message, !res.status);
                if (res.status) { openDrawer(d.schedule_id); loadTickets(); }
              } catch(e) { showToast('Gagal memproses permintaan', true); }
            }
          });
        });
      }

      if (btnReject) {
        btnReject.addEventListener('click', () => {
          showConfirmDialog({
            title: 'Tolak Kendala Lapangan',
            text: 'Apakah Anda yakin ingin menolak laporan kendala ini? Teknisi diharapkan melanjutkan pengerjaan.',
            confirmText: 'Tolak Kendala',
            confirmClass: 'btn-ghost',
            onConfirm: async () => {
              try {
                const res = await apiPost(API.handleIssue, { action: 'reject', issue_id: d.issue_report.issue_id, schedule_id: d.schedule_id });
                showToast(res.message, !res.status);
                if (res.status) { openDrawer(d.schedule_id); loadTickets(); }
              } catch(e) { showToast('Gagal memproses permintaan', true); }
            }
          });
        });
      }
    }
  }

  async function handleSaveDrawer() {
    if (!state.activeSchedule) return;

    const payload = {
      schedule_id:    state.activeSchedule,
      name:           document.getElementById('d_nama') ? document.getElementById('d_nama').value.trim() : '',
      phone_contact:  document.getElementById('d_no_tlp') ? document.getElementById('d_no_tlp').value.trim() : '',
      paket_internet: document.getElementById('d_paket_internet') ? document.getElementById('d_paket_internet').value.trim() : '',
      perumahan:      document.getElementById('d_perumahan') ? document.getElementById('d_perumahan').value.trim() : '',
      location:       document.getElementById('d_location') ? document.getElementById('d_location').value.trim() : '',
      catatan:        document.getElementById('d_catatan').value.trim(),
      tim_id:         document.getElementById('d_tim').value,
      tanggal_service:document.getElementById('d_tanggal').value,
      noc_id:         document.getElementById('d_noc').value,
      status:         document.getElementById('d_status').value,
      reason:         document.getElementById('d_reason').value.trim(),
    };

    const btn = document.getElementById('saveDrawerBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';

    try {
      const res = await apiPost(API.update, payload);
      if (!res.status) {
        showToast(res.message || 'Gagal memperbarui tiket', true);
        return;
      }
      state.lastNocId = payload.noc_id;
      showToast('Perubahan tiket instalasi berhasil disimpan');
      closeDrawer();
      loadTickets();
    } catch (e) {
      showToast('Gagal menghubungi server', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Simpan Perubahan';
    }
  }

  async function handleDeleteDrawer() {
    if (!state.activeSchedule) return;

    showConfirmDialog({
      title: 'Hapus Tiket Instalasi',
      text: 'Apakah Anda yakin ingin menghapus tiket pengerjaan instalasi ini? Tindakan ini tidak dapat dibatalkan.',
      confirmText: 'Hapus Tiket',
      confirmClass: 'btn-primary',
      onConfirm: async () => {
        try {
          const res = await apiPost(API.delete, { schedule_id: state.activeSchedule });
          if (!res.status) {
            showToast(res.message || 'Gagal menghapus tiket', true);
            return;
          }
          showToast('Tiket instalasi berhasil dihapus');
          closeDrawer();
          loadTickets();
        } catch (e) {
          showToast('Gagal menghubungi server', true);
        }
      }
    });
  }
</script>
</body>
</html>
