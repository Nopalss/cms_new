<?php
require_once __DIR__ . '/../../../includes/config.php';

// Base path untuk endpoint API di folder ini
$apiBase = BASE_URL . 'pages/ticketing/service/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JTracks — Komplain &amp; Service</title>
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

  /* Segmented Card Selector for Ticket Types */
  .ticket-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
  }
  .ticket-type-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--bg-body, #F8FAFC);
    border: 1px dashed var(--border, #CBD5E1);
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 0;
    user-select: none;
  }
  .ticket-type-card:hover {
    border-color: var(--primary, #2563EB);
    background: #F1F5F9;
    transform: translateY(-1px);
  }
  .ticket-type-card.active {
    background: #EFF6FF;
    border: 2px solid #2563EB;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
  }
  .ticket-type-icon {
    font-size: 24px;
    line-height: 1;
    flex-shrink: 0;
  }
  .ticket-type-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  .ticket-type-title {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--text, #0F172A);
    line-height: 1.2;
  }
  .ticket-type-sub {
    font-size: 10.5px;
    color: var(--text-muted, #64748B);
    margin-top: 3px;
    line-height: 1.25;
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

  .empty-state { text-align: center; padding: 70px 20px; color: var(--text-muted); }
  .empty-state strong { display: block; color: var(--text); font-size: 15px; margin-bottom: 6px; }

  /* ---------- Overlay ---------- */
  .overlay {
    position: fixed; inset: 0; background: rgba(20, 25, 23, 0.38);
    opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 20;
  }
  .overlay.show { opacity: 1; pointer-events: auto; }

  /* ---------- Modal (Tiket Baru) ---------- */
  .modal {
    position: fixed; top: 50%; left: 50%; width: min(780px, 94vw); max-height: 90vh;
    overflow-y: auto; background: var(--surface); border-radius: 14px; box-shadow: var(--shadow);
    z-index: 30; opacity: 0; pointer-events: none;
    transform: translate(-50%, -46%); transition: opacity 0.2s, transform 0.2s;
  }
  .modal.show { opacity: 1; pointer-events: auto; transform: translate(-50%, -50%); }
  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px; border-bottom: 1px solid var(--border); position: sticky; top: 0;
    background: var(--surface); border-radius: 14px 14px 0 0;
  }
  .modal-header h2 { margin: 0; font-size: 15px; }
  .modal-body { padding: 20px 22px; }
  .modal-footer {
    padding: 16px 22px; border-top: 1px solid var(--border);
    display: flex; justify-content: flex-end; gap: 10px;
  }
  .close-x { background: none; border: none; font-size: 18px; color: var(--text-muted); line-height: 1; }

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
    font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--accent-dark); margin: 22px 0 12px; padding-top: 14px; border-top: 1px solid var(--border);
  }
  .section-title:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }

  .readout { font-size: 13.5px; line-height: 1.5; margin-bottom: 10px; }
  .readout .label { display: block; font-size: 11px; color: var(--text-muted); margin-bottom: 2px; }

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
      <h1>Komplain &amp; Service</h1>
      <div class="sub">Dashboard NOC — JTracks</div>
    </div>
    <div class="topbar-controls">
      <input type="text" id="searchInput" placeholder="🔍 Cari Netpay ID / Nama..." style="width: 220px;">
      <h5>Urutan:</h5>
      <select id="sortPicker">
        <option value="DESC">Terbaru </option>
        <option value="ASC">Terlama</option>
      </select>
      <input type="month" id="bulanPicker">
      <button type="button" class="btn-share-today" id="btnSalinTugasHariIni" title="Salin Gambar Screenshot Tugas Hari Ini">📋 Salin Tugas (Hari Ini)</button>
      <button class="btn-primary" id="btnTiketBaru">+ Tiket Baru</button>
    </div>
  </div>

  <div class="content">

    <!-- KPI Summary Row -->
    <div class="kpi-row">
      <div class="kpi-card kpi-total">
        <div class="kpi-num" id="kpiTotal">0</div>
        <div class="kpi-label">Total Service</div>
      </div>
      <div class="kpi-card kpi-pending">
        <div class="kpi-num" id="kpiPending">0</div>
        <div class="kpi-label">Menunggu</div>
      </div>
      <div class="kpi-card kpi-active">
        <div class="kpi-num" id="kpiActive">0</div>
        <div class="kpi-label">Dikerjakan</div>
      </div>
      <div class="kpi-card kpi-reschedule">
        <div class="kpi-num" id="kpiReschedule">0</div>
        <div class="kpi-label">Rescheduled</div>
      </div>
      <div class="kpi-card kpi-kendala">
        <div class="kpi-num" id="kpiKendala">0</div>
        <div class="kpi-label">Kendala</div>
      </div>
      <div class="kpi-card kpi-done">
        <div class="kpi-num" id="kpiDone">0</div>
        <div class="kpi-label">Selesai</div>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>No Tlp</th>
            <th>Alamat</th>
            <th style="text-align: center;">Server</th>
            <th>Aduan Pelanggan</th>
            <th>Verifikasi NOC</th>
            <th>Tim</th>
            <th>Target Status</th>
            <th>Tgl Service</th>
            <th style="text-align: center;">Status</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="11"><div class="empty-state">Memuat data…</div></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="overlay" id="overlay"></div>

  <!-- ============ MODAL: TIKET BARU ============ -->
  <div class="modal" id="modalTiket" style="width: min(760px, 94vw);">
    <div class="modal-header" style="padding: 14px 20px;">
      <h2>Tiket Komplain Baru</h2>
      <button class="close-x" id="closeModal">&times;</button>
    </div>
    <div class="modal-body" style="padding: 16px 20px;">
      
      <!-- Segmented Card Selector for Ticket Types -->
      <div style="margin-bottom: 14px;">
        <label style="margin-bottom: 8px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); display: block;">Tipe Tiket Service</label>
        
        <div class="ticket-type-selector">
          <label class="ticket-type-card active" id="cardTypeCustomer" onclick="toggleTicketTypeFields('customer')">
            <input type="radio" name="f_ticket_type" value="customer" checked style="display:none;">
            <div class="ticket-type-icon">👤</div>
            <div class="ticket-type-info">
              <div class="ticket-type-title">Pelanggan</div>
              <div class="ticket-type-sub">Aduan Pelanggan (Netpay ID)</div>
            </div>
          </label>

          <label class="ticket-type-card" id="cardTypeNonCustomer" onclick="toggleTicketTypeFields('non_customer')">
            <input type="radio" name="f_ticket_type" value="non_customer" style="display:none;">
            <div class="ticket-type-icon">🏗️</div>
            <div class="ticket-type-info">
              <div class="ticket-type-title">Infrastruktur / Fasum</div>
              <div class="ticket-type-sub">Tiang, FO, ODP (Tanpa Netpay ID)</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Customer fields container -->
      <div id="groupCustomerFields">
        <div class="field-row" style="margin-bottom: 10px;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Netpay ID</label>
            <input type="text" id="f_netpay_id" placeholder="Ketik lalu Enter/klik di luar buat cari data">
            <div id="netpayInfo" style="font-size:11px;margin-top:2px;color:var(--text-muted);min-height:14px;"></div>
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Nama Pelanggan</label>
            <input type="text" id="f_nama" placeholder="Otomatis terisi..." disabled>
          </div>
        </div>

        <div class="field-row" style="margin-bottom: 10px;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">No Tlp Contact</label>
            <input type="text" id="f_no_tlp" placeholder="mis. 0895428474630">
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Server</label>
            <input type="text" id="f_server" placeholder="mis. 251">
          </div>
        </div>

        <div class="field" style="margin-bottom: 10px;">
          <label style="margin-bottom: 4px;">Alamat</label>
          <input type="text" id="f_alamat" placeholder="Otomatis terisi..." disabled>
        </div>
      </div>

      <!-- Non-customer fields container -->
      <div id="groupNonCustomerFields" style="display: none;">
        <div class="field-row" style="margin-bottom: 10px;">
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Perumahan / Wilayah <span style="color:var(--cancel-text)">*</span></label>
            <input type="text" id="f_non_perumahan" placeholder="mis. BCA 2">
          </div>
          <div class="field" style="margin-bottom: 0;">
            <label style="margin-bottom: 4px;">Server Area</label>
            <input type="text" id="f_non_server" placeholder="mis. 251 (Opsional)">
          </div>
        </div>

        <div class="field" style="margin-bottom: 10px;">
          <label style="margin-bottom: 4px;">Alamat Detail / Lokasi <span style="color:var(--cancel-text)">*</span></label>
          <input type="text" id="f_non_alamat" placeholder="mis. Tiang Listrik / FO Blok C No. 12">
        </div>

        <div class="field" style="margin-bottom: 10px;">
          <label style="margin-bottom: 4px;">Link Sharelock (Google Maps)</label>
          <input type="text" id="f_non_sharelock" placeholder="mis. https://maps.app.goo.gl/... (Opsional)">
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Aduan Pelanggan</label>
          <input type="text" id="f_aduan" style="height: 38px;" placeholder="Deskripsi kendala..." list="list_f_aduan">
          <datalist id="list_f_aduan"></datalist>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Verifikasi NOC</label>
          <input type="text" id="f_verifikasi" style="height: 38px;" placeholder="Catatan verifikasi NOC..." list="list_f_verifikasi">
          <datalist id="list_f_verifikasi"></datalist>
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 0;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tim Penanggung Jawab</label>
          <select id="f_tim"><option value="">Pilih tim…</option></select>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tgl Service</label>
          <input type="date" id="f_tanggal">
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">NOC</label>
          <select id="f_noc"><option value="">Pilih NOC…</option></select>
        </div>
      </div>

    </div>
    <div class="modal-footer" style="padding: 12px 20px;">
      <button class="btn-ghost" id="cancelModal">Batal</button>
      <button class="btn-primary" id="submitModal">Simpan Tiket</button>
    </div>
  </div>

  <!-- ============ DRAWER: DETAIL TIKET ============ -->
  <div class="drawer" id="drawerDetail">
    <div class="drawer-header">
      <h2>Detail Tiket</h2>
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
      <h3 id="confirmModalTitle">Konfirmasi</h3>
      <button class="close-x" id="closeConfirmModal">&times;</button>
    </div>
    <div class="modal-body" style="padding: 18px 22px;">
      <p id="confirmModalText" style="font-size: 13.5px; color: var(--text); margin-bottom: 12px; line-height: 1.45;"></p>
      
      <div id="confirmInputsGroup" style="display: none; flex-direction: column; gap: 12px; margin-top: 10px;">
        <div class="field">
          <label>Tanggal Service Baru <span style="color:var(--cancel-text);">*</span></label>
          <input type="date" id="modalRescheduleDate">
        </div>
        <div class="field">
          <label>Alasan Reschedule</label>
          <input type="text" id="modalRescheduleReason" placeholder="mis. Tunda atas permintaan pelanggan...">
        </div>
      </div>
    </div>
    <div class="modal-footer" style="padding: 14px 22px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border);">
      <button class="btn-ghost" id="confirmCancelBtn">Batal</button>
      <button class="btn-primary" id="confirmSubmitBtn">Ya, Lanjutkan</button>
    </div>
  </div>

  <div class="toast" id="toast"></div>

<script>
  // BASE_URL di-inject dari PHP — path selalu bener walau project dipindah folder/server
  const API = {
    list:    '<?= $apiBase ?>list_tickets.php',
    detail:  '<?= $apiBase ?>get_ticket_detail.php',
    create:  '<?= $apiBase ?>create_maintenance_ticket.php',
    lookup:  '<?= $apiBase ?>lookup_customer.php',
    listTim: '<?= $apiBase ?>list_tim.php',
    listNoc: '<?= $apiBase ?>list_noc.php',
    update:  '<?= $apiBase ?>update_ticket.php',
    delete:  '<?= $apiBase ?>delete_ticket.php',
    handleIssue: '<?= $apiBase ?>handle_issue_report.php',
    suggestions: '<?= $apiBase ?>list_suggestions.php',
  };

  const STATUS_LABEL = {
    Pending: 'Menunggu Teknisi',
    Actived: 'Sedang Dikerjakan',
    Rescheduled: 'Dijadwalkan Ulang',
    Cancelled: 'Dibatalkan',
    Done: 'Selesai',
  };
  const STATUS_CLASS = {
    Pending: 'badge-pending',
    Actived: 'badge-active',
    Rescheduled: 'badge-reschedule',
    Cancelled: 'badge-cancel',
    Done: 'badge-done',
  };
  const STATUS_OPTIONS = ['Pending', 'Actived', 'Rescheduled', 'Cancelled', 'Done'];

  const state = {
    bulan: currentMonthStr(),
    order: 'DESC',
    tickets: [],
    searchQuery: '',
    pollTimer: null,
    lastNocId: '',
    timList: [],
    nocList: [],
    activeSchedule: null,
    aduanSuggestions: [],
    nocSuggestions: [],
  };

  function currentMonthStr() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function getTodayStr() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function formatDate(dstr) {
    if (!dstr) return '-';
    const dt = new Date(dstr + 'T00:00:00');
    if (isNaN(dt)) return dstr;
    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  function showToast(msg, isError) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (isError ? ' error' : '');
    setTimeout(() => { t.className = 'toast'; }, 2800);
  }

  async function apiPost(url, data) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, { method: 'POST', body });
    return res.json();
  }

  // ---------------- Custom Confirm Modal Helper ----------------

  function showConfirmDialog({ title, text, confirmText, confirmClass, showRescheduleInputs, onConfirm }) {
    const overlay = document.getElementById('confirmOverlay');
    const modal = document.getElementById('customConfirmModal');
    const titleEl = document.getElementById('confirmModalTitle');
    const textEl = document.getElementById('confirmModalText');
    const inputsGroup = document.getElementById('confirmInputsGroup');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    const submitBtn = document.getElementById('confirmSubmitBtn');

    titleEl.textContent = title || 'Konfirmasi';
    textEl.textContent = text || 'Apakah Anda yakin?';
    submitBtn.textContent = confirmText || 'Ya, Lanjutkan';
    submitBtn.className = confirmClass || 'btn-primary';

    if (showRescheduleInputs) {
      inputsGroup.style.display = 'flex';
      document.getElementById('modalRescheduleDate').value = getTodayStr();
      document.getElementById('modalRescheduleReason').value = '';
    } else {
      inputsGroup.style.display = 'none';
    }

    const closeDialog = () => {
      overlay.classList.remove('show');
      modal.classList.remove('show');
    };

    const cleanup = () => {
      cancelBtn.onclick = null;
      submitBtn.onclick = null;
      document.getElementById('closeConfirmModal').onclick = null;
      overlay.onclick = null;
    };

    cancelBtn.onclick = () => { closeDialog(); cleanup(); };
    document.getElementById('closeConfirmModal').onclick = () => { closeDialog(); cleanup(); };
    overlay.onclick = () => { closeDialog(); cleanup(); };

    submitBtn.onclick = () => {
      let extraData = {};
      if (showRescheduleInputs) {
        const dateVal = document.getElementById('modalRescheduleDate').value;
        const reasonVal = document.getElementById('modalRescheduleReason').value.trim();
        if (!dateVal) {
          showToast('Tanggal reschedule harus diisi', true);
          return;
        }
        extraData = { new_date: dateVal, reason: reasonVal };
      }
      closeDialog();
      cleanup();
      if (onConfirm) onConfirm(extraData);
    };

    overlay.classList.add('show');
    modal.classList.add('show');
  }

  // ---------------- List & polling ----------------

  function updateKpi() {
    const tickets = state.tickets || [];
    document.getElementById('kpiTotal').textContent = tickets.length;
    document.getElementById('kpiPending').textContent = tickets.filter(t => t.status === 'Pending').length;
    document.getElementById('kpiActive').textContent = tickets.filter(t => t.status === 'Actived').length;
    document.getElementById('kpiReschedule').textContent = tickets.filter(t => t.status === 'Rescheduled').length;
    document.getElementById('kpiKendala').textContent = tickets.filter(t => t.issue_id && t.issue_status === 'Pending').length;
    document.getElementById('kpiDone').textContent = tickets.filter(t => t.status === 'Done').length;
  }

  async function loadTickets() {
    try {
      const res = await apiPost(API.list, { bulan: state.bulan, order: state.order });
      if (res.status) {
        state.tickets = res.data;
        updateKpi();
        renderTable();
      }
    } catch (e) {
      console.error('Gagal memuat daftar tiket:', e);
    }
  }

  function formatGroupDate(dstr) {
    if (!dstr || dstr === '0000-00-00') return 'Tanpa Tanggal';
    const dt = new Date(dstr + (dstr.includes('T') ? '' : 'T00:00:00'));
    if (isNaN(dt)) return dstr;
    return dt.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }

  function renderTable() {
    const tbody = document.getElementById('tbody');
    const q = (state.searchQuery || '').trim().toLowerCase();

    const filteredTickets = state.tickets.filter(t => {
      if (!q) return true;
      const netpayId = (t.netpay_id || '').toLowerCase();
      const nama     = (t.nama || '').toLowerCase();
      const noTlp    = (t.no_tlp || '').toLowerCase();
      const alamat   = (t.alamat || '').toLowerCase();
      const server   = (t.server || '').toLowerCase();
      const aduan    = (t.aduan_pelanggan || '').toLowerCase();
      return netpayId.includes(q) || nama.includes(q) || noTlp.includes(q) || alamat.includes(q) || server.includes(q) || aduan.includes(q);
    });

    if (!filteredTickets.length) {
      tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state">
        <strong>${q ? 'Tidak ada tiket yang cocok dengan "' + escapeHtml(q) + '"' : 'Belum ada tiket bulan ini'}</strong>
        ${q ? 'Coba kata kunci lain atau kosongkan kolom pencarian.' : 'Klik "+ Tiket Baru" buat mulai catat komplain.'}
      </div></td></tr>`;
      return;
    }

    // Kelompokkan tiket berdasarkan tanggal pembuatan tiket / tanggal komplain (prioritas: tanggal_dibuat, lalu tanggal_service)
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
          <td colspan="11">
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
            <td style="text-align: center;">${escapeHtml(t.server || '-')}</td>
            <td>${escapeHtml(t.aduan_pelanggan || '-')}</td>
            <td>${escapeHtml(t.verifikasi_noc || '-')}</td>
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
      showToast(`Tidak ada tiket komplain "Menunggu Teknisi" untuk Hari Ini (${formattedToday})`, true);
      return;
    }

    showToast('Memproses screenshot gambar tugas hari ini...');

    // Buat container kartu tersembunyi berdesain modern premium
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
            <!-- Baris Atas: Nomor, Netpay ID, & PIC -->
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
                  background: #F1F5F9; color: #475569; font-size: 11px; font-weight: 700;
                  padding: 3px 9px; border-radius: 6px; border: 1px solid #E2E8F0;
                ">
                  👥 ${escapeHtml(t.tim_nama || 'Belum Ditugaskan')}
                </span>
              </div>
            </div>

            <!-- Baris Tengah: Info Pelanggan & Alamat -->
            <div style="font-size: 12.5px; line-height: 1.5; color: #334155; margin-bottom: 8px;">
              <strong style="color: #0F172A; font-size: 13.5px;">👤 ${escapeHtml(t.nama || '-')}</strong>
              <span style="color: #CBD5E1; margin: 0 6px;">|</span>
              <span>📞 ${escapeHtml(t.no_tlp || '-')}</span>
              <div style="color: #475569; margin-top: 3px;">
                🏠 <strong>Alamat:</strong> ${escapeHtml(t.alamat || '-')}
              </div>
            </div>

            <!-- Baris Bawah: Aduan Pelanggan & Verifikasi NOC (Side-by-Side) -->
            <div style="
              background: #FFFBEB; border: 1px solid #FEF3C7; border-radius: 8px;
              padding: 8px 12px; font-size: 12px; color: #92400E;
              display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start;
            ">
              <div style="flex: 1; min-width: 220px;">⚠️ <strong>Aduan Pelanggan:</strong> ${escapeHtml(t.aduan_pelanggan || '-')}</div>
              ${t.verifikasi_noc ? `
              <div style="flex: 1; min-width: 220px; color: #0E7C7B; font-weight: 700; border-left: 2px solid #FDE68A; padding-left: 10px;">
                🔍 <strong>Verifikasi NOC:</strong> ${escapeHtml(t.verifikasi_noc)}
              </div>` : ''}
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
            📋 LIST TUGAS KOMPLAIN &amp; SERVICE
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
          ${groupTickets.length} TIKET TUGAS
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
          a.download = `List_Service_HariIni_${todayStr}.png`;
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
    if (!state.aduanSuggestions.length) {
      try {
        const res = await fetch(API.suggestions).then(r => r.json());
        if (res.status && res.data) {
          state.aduanSuggestions = res.data.aduan || [];
          state.nocSuggestions   = res.data.verifikasi || [];
        }
      } catch (e) {
        console.error('Gagal memuat saran:', e);
      }
    }
  }

  function renderSuggestions(datalistId, list) {
    const listEl = document.getElementById(datalistId);
    if (!listEl || !list) return;
    listEl.innerHTML = list.map(item => `<option value="${escapeHtml(item)}">`).join('');
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

    toggleTicketTypeFields('customer');

    document.getElementById('f_netpay_id').value = '';
    document.getElementById('f_nama').value = '';
    document.getElementById('f_no_tlp').value = '';
    document.getElementById('f_alamat').value = '';
    document.getElementById('f_server').value = '';
    document.getElementById('f_non_perumahan').value = '';
    document.getElementById('f_non_server').value = '';
    document.getElementById('f_non_alamat').value = '';
    document.getElementById('f_non_sharelock').value = '';
    document.getElementById('f_aduan').value = '';
    document.getElementById('f_verifikasi').value = '';
    document.getElementById('netpayInfo').textContent = '';
    document.getElementById('f_tanggal').value = getTodayStr();

    const defaultTimId = getDefaultTimId(state.timList);
    fillDropdown(document.getElementById('f_tim'), state.timList, 'tim_id', 'nama', defaultTimId);
    fillDropdown(document.getElementById('f_noc'), state.nocList, 'admin_id', 'name', state.lastNocId);

    renderSuggestions('list_f_aduan', state.aduanSuggestions);
    renderSuggestions('list_f_verifikasi', state.nocSuggestions);

    document.getElementById('overlay').classList.add('show');
    document.getElementById('modalTiket').classList.add('show');
  }

  function closeCreateModal() {
    document.getElementById('overlay').classList.remove('show');
    document.getElementById('modalTiket').classList.remove('show');
  }

  let isLookupRunning = false;

  async function handleNetpayLookup() {
    const netpayInput = document.getElementById('f_netpay_id');
    const netpayId = netpayInput.value.trim();
    const info = document.getElementById('netpayInfo');
    if (!netpayId) return;

    if (isLookupRunning) return;
    isLookupRunning = true;

    info.style.color = 'var(--accent-dark)';
    info.textContent = 'Mencari data pelanggan…';
    try {
      const res = await apiPost(API.lookup, { netpay_id: netpayId });
      if (!res.status) {
        info.textContent = res.message || 'Netpay ID tidak ditemukan';
        info.style.color = 'var(--cancel-text)';
        document.getElementById('f_nama').value = '';
        document.getElementById('f_no_tlp').value = '';
        document.getElementById('f_alamat').value = '';
        return;
      }
      document.getElementById('f_nama').value = res.data.nama || '';
      document.getElementById('f_no_tlp').value = res.data.phone_contact || res.data.no_tlp || '';
      document.getElementById('f_alamat').value = res.data.alamat || '';
      info.style.color = 'var(--text-muted)';
      info.textContent = res.data.is_active === 'ACTIVE'
        ? 'Data ditemukan.'
        : 'Data ditemukan — status pelanggan: ' + res.data.is_active;
    } catch (e) {
      info.textContent = 'Gagal menghubungi server.';
      info.style.color = 'var(--cancel-text)';
    } finally {
      isLookupRunning = false;
    }
  }

  function toggleTicketTypeFields(type) {
    const custGroup = document.getElementById('groupCustomerFields');
    const nonCustGroup = document.getElementById('groupNonCustomerFields');
    const cardCust = document.getElementById('cardTypeCustomer');
    const cardNonCust = document.getElementById('cardTypeNonCustomer');

    if (type === 'non_customer') {
      custGroup.style.display = 'none';
      nonCustGroup.style.display = 'block';
      if (cardCust) cardCust.classList.remove('active');
      if (cardNonCust) cardNonCust.classList.add('active');
      const radio = cardNonCust.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    } else {
      custGroup.style.display = 'block';
      nonCustGroup.style.display = 'none';
      if (cardCust) cardCust.classList.add('active');
      if (cardNonCust) cardNonCust.classList.remove('active');
      const radio = cardCust.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    }
  }

  async function handleSubmitTicket() {
    const ticketTypeRadio = document.querySelector('input[name="f_ticket_type"]:checked');
    const ticketType = ticketTypeRadio ? ticketTypeRadio.value : 'customer';

    let payload = {
      ticket_type: ticketType,
      aduan_pelanggan: document.getElementById('f_aduan').value.trim(),
      verifikasi_noc:  document.getElementById('f_verifikasi').value.trim(),
      tim_id:          document.getElementById('f_tim').value,
      tanggal_service: document.getElementById('f_tanggal').value,
      noc_id:          document.getElementById('f_noc').value,
    };

    if (ticketType === 'non_customer') {
      payload.perumahan = document.getElementById('f_non_perumahan').value.trim();
      payload.location  = document.getElementById('f_non_alamat').value.trim();
      payload.sharelock = document.getElementById('f_non_sharelock').value.trim();
      payload.server    = document.getElementById('f_non_server').value.trim();

      if (!payload.perumahan || !payload.location) {
        showToast('Perumahan dan Alamat Detail harus diisi', true);
        return;
      }
    } else {
      payload.netpay_id     = document.getElementById('f_netpay_id').value.trim();
      payload.phone_contact = document.getElementById('f_no_tlp').value.trim();
      payload.server        = document.getElementById('f_server').value.trim();

      if (!payload.netpay_id) {
        showToast('Netpay ID belum diisi', true);
        return;
      }
    }

    if (!payload.aduan_pelanggan || !payload.verifikasi_noc || !payload.tim_id || !payload.tanggal_service || !payload.noc_id) {
      showToast('Mohon lengkapi semua field yang wajib diisi', true);
      return;
    }

    const btn = document.getElementById('submitModal');
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';

    try {
      const res = await apiPost(API.create, payload);
      if (!res.status) {
        showToast(res.message || 'Gagal menyimpan tiket', true);
        return;
      }
      state.lastNocId = payload.noc_id;
      showToast('Tiket berhasil dibuat');
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
    await ensureDropdownsLoaded();
    document.getElementById('overlay').classList.add('show');
    document.getElementById('drawerDetail').classList.add('show');
    document.getElementById('drawerBody').innerHTML = '<div class="empty-state">Memuat…</div>';

    try {
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

    const hasilPengerjaanHtml = d.akar_masalah ? `
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

      <!-- Problem & Action Cards -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:12px;">
        <div style="background:var(--surface); border:1px solid var(--border); border-left:3.5px solid var(--cancel-text); border-radius:8px; padding:10px 12px;">
          <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--cancel-text); margin-bottom:4px; letter-spacing:0.03em;">🔍 Akar Masalah</div>
          <div style="font-size:13px; font-weight:600; color:var(--text); line-height:1.4;">${escapeHtml(d.akar_masalah)}</div>
        </div>
        <div style="background:var(--surface); border:1px solid var(--border); border-left:3.5px solid var(--accent-dark); border-radius:8px; padding:10px 12px;">
          <div style="font-size:10.5px; font-weight:700; text-transform:uppercase; color:var(--accent-dark); margin-bottom:4px; letter-spacing:0.03em;">🛠️ Penanganan</div>
          <div style="font-size:13px; font-weight:600; color:var(--text); line-height:1.4;">${escapeHtml(d.penanganan)}</div>
        </div>
      </div>

      <!-- Technician PIC Pills -->
      <div style="margin-bottom:12px;">
        <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; color:var(--text-muted); margin-bottom:6px; letter-spacing:0.03em;">Teknisi (PIC Lapangan)</div>
        <div style="display:flex; flex-wrap:wrap; gap:6px;">${teknisiPills}</div>
      </div>

      <!-- Notes -->
      ${d.keterangan ? `
        <div style="background:var(--surface-alt); border:1px solid var(--border); border-radius:8px; padding:10px 12px; margin-bottom:12px;">
          <div style="font-size:10.5px; font-weight:600; text-transform:uppercase; color:var(--text-muted); margin-bottom:3px;">📝 Keterangan Tambahan</div>
          <div style="font-size:12.5px; color:var(--text); line-height:1.4;">${escapeHtml(d.keterangan)}</div>
        </div>
      ` : ''}
    ` : `
      <div class="pending-box">
        <strong>Menunggu laporan teknisi</strong>
        Kolom ini keisi otomatis begitu teknisi submit laporan pengerjaan.
      </div>
    `;

    const issueHtml = d.issue_report ? `
      <div class="section-title">Laporan Kendala Teknisi</div>
      <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:10px; padding:14px; margin-bottom:16px;">
        <div style="font-weight:700; color:#B45309; font-size:13px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;">
          <span>⚠️ Kendala Lapangan</span>
          <span class="badge ${d.issue_report.status === 'Pending' ? 'badge-reschedule' : (d.issue_report.status === 'Approved' ? 'badge-cancel' : 'badge-done')}">${escapeHtml(d.issue_report.status)}</span>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:8px;">
          <div class="readout" style="margin-bottom:0;"><span class="label">Tipe Kendala</span>${escapeHtml(d.issue_report.issue_type)}</div>
          <div class="readout" style="margin-bottom:0;"><span class="label">Dilaporkan Oleh</span>${escapeHtml(d.issue_report.reported_by || '-')} (${escapeHtml(d.issue_report.created_at || '')})</div>
        </div>
        <div class="readout" style="margin-bottom:8px;"><span class="label">Deskripsi Kendala</span>${escapeHtml(d.issue_report.description || '-')}</div>
        ${d.issue_report.status === 'Pending' ? `
          <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
            <button class="btn-ghost" id="btnRejectIssue" style="color:var(--cancel-text); border-color:var(--cancel-text); padding:7px 10px; font-size:11.5px;">Tolak Kendala</button>
            <button class="btn-ghost" id="btnRescheduleIssue" style="color:#B45309; border-color:#FDE68A; background:#FEF3C7; padding:7px 10px; font-size:11.5px;">Reschedule Tiket</button>
            <button class="btn-primary" id="btnApproveIssue" style="background:#B45309; padding:7px 10px; font-size:11.5px; flex:1;">Setujui (Batal)</button>
          </div>
        ` : ''}
      </div>
    ` : '';

    document.getElementById('drawerBody').innerHTML = `
      <div class="section-title">Data Pelanggan</div>
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; background:var(--surface-alt); padding:12px; border-radius:10px; border:1px solid var(--border); margin-bottom:16px;">
        <div class="readout" style="margin-bottom:0;"><span class="label">Netpay ID</span><strong style="color:var(--accent-dark);">${escapeHtml(d.netpay_id)}</strong></div>
        <div class="readout" style="margin-bottom:0;"><span class="label">Nama Pelanggan</span><strong>${escapeHtml(d.nama || '-')}</strong></div>
        <div class="readout" style="margin-bottom:0;"><span class="label">No Tlp</span>${escapeHtml(d.no_tlp || '-')}</div>
        <div class="readout" style="margin-bottom:0;"><span class="label">Alamat</span>${escapeHtml(d.alamat || '-')}</div>
      </div>

      ${issueHtml}

      <div class="section-title">Edit Data Komplain &amp; Penugasan</div>
      
      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">No Tlp Contact</label>
          <input type="text" id="d_no_tlp" value="${escapeHtml(d.phone_contact || d.no_tlp || '')}" placeholder="mis. 0895428474630">
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Server</label>
          <input type="text" id="d_server" value="${escapeHtml(d.server || '')}">
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Aduan Pelanggan</label>
          <input type="text" id="d_aduan" style="height: 38px;" value="${escapeHtml(d.aduan_pelanggan || '')}" list="list_d_aduan" placeholder="Deskripsi kendala...">
          <datalist id="list_d_aduan"></datalist>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Verifikasi NOC</label>
          <input type="text" id="d_verifikasi" style="height: 38px;" value="${escapeHtml(d.verifikasi_noc || '')}" list="list_d_verifikasi" placeholder="Catatan verifikasi NOC...">
          <datalist id="list_d_verifikasi"></datalist>
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tim Penanggung Jawab</label>
          <select id="d_tim"><option value="">Pilih tim…</option></select>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Tgl Service</label>
          <input type="date" id="d_tanggal" value="${escapeHtml(d.tanggal_service || '')}">
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 10px;">
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">NOC</label>
          <select id="d_noc"><option value="">Pilih NOC…</option></select>
        </div>
        <div class="field" style="margin-bottom: 0;">
          <label style="margin-bottom: 4px;">Status Tiket</label>
          <select id="d_status">
            ${STATUS_OPTIONS.map(s =>
              `<option value="${s}" ${s === d.status ? 'selected' : ''}>${STATUS_LABEL[s]}</option>`
            ).join('')}
          </select>
        </div>
      </div>

      <div class="field" style="margin-bottom: 16px;">
        <label style="margin-bottom: 4px;">Alasan / Reason</label>
        <input type="text" id="d_reason" value="${escapeHtml(d.reason || '')}" placeholder="Opsional...">
      </div>

      <div class="section-title">Hasil Pengerjaan Teknisi</div>
      ${hasilPengerjaanHtml}
    `;

    fillDropdown(document.getElementById('d_tim'), state.timList, 'tim_id', 'nama', d.tech_id);
    fillDropdown(document.getElementById('d_noc'), state.nocList, 'admin_id', 'name', d.noc_id);

    renderSuggestions('list_d_aduan', state.aduanSuggestions);
    renderSuggestions('list_d_verifikasi', state.nocSuggestions);

    if (d.issue_report && d.issue_report.status === 'Pending') {
      const btnApprove = document.getElementById('btnApproveIssue');
      const btnReschedule = document.getElementById('btnRescheduleIssue');
      const btnReject  = document.getElementById('btnRejectIssue');

      if (btnApprove) {
        btnApprove.addEventListener('click', () => {
          showConfirmDialog({
            title: 'Setujui Kendala & Batalkan Tiket',
            text: 'Apakah Anda yakin ingin menyetujui laporan kendala ini? Status tiket akan diubah menjadi Cancelled (Dibatalkan).',
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
            confirmClass: 'btn-primary',
            showRescheduleInputs: true,
            onConfirm: async (data) => {
              try {
                const res = await apiPost(API.handleIssue, {
                  action: 'reschedule',
                  issue_id: d.issue_report.issue_id,
                  schedule_id: d.schedule_id,
                  new_date: data.new_date,
                  reason: data.reason
                });
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
            title: 'Tolak Laporan Kendala',
            text: 'Apakah Anda yakin ingin menolak kendala ini? Teknisi akan dapat melanjutkan pekerjaannya kembali di lapangan.',
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

    document.getElementById('saveDrawerBtn').onclick = () => handleSaveTicketEdit(d.schedule_id);
    document.getElementById('deleteDrawerBtn').onclick = () => handleDeleteTicket(d.schedule_id);
  }

  async function handleSaveTicketEdit(scheduleId) {
    const payload = {
      schedule_id:     scheduleId,
      phone_contact:   document.getElementById('d_no_tlp').value.trim(),
      server:          document.getElementById('d_server').value.trim(),
      aduan_pelanggan: document.getElementById('d_aduan').value.trim(),
      verifikasi_noc:  document.getElementById('d_verifikasi').value.trim(),
      tim_id:          document.getElementById('d_tim').value,
      tanggal_service: document.getElementById('d_tanggal').value,
      noc_id:          document.getElementById('d_noc').value,
      reason:          document.getElementById('d_reason').value.trim(),
      status:          document.getElementById('d_status').value,
    };

    const btn = document.getElementById('saveDrawerBtn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';

    try {
      const res = await apiPost(API.update, payload);
      if (!res.status) {
        showToast(res.message || 'Gagal menyimpan perubahan', true);
        return;
      }
      showToast('Perubahan berhasil disimpan');
      closeDrawer();
      loadTickets();
    } catch (e) {
      showToast('Gagal menghubungi server', true);
    } finally {
      btn.disabled = false;
      btn.textContent = 'Simpan Perubahan';
    }
  }

  function handleDeleteTicket(scheduleId) {
    showConfirmDialog({
      title: 'Hapus Tiket',
      text: 'Apakah Anda yakin ingin menghapus tiket ini? Data yang dihapus tidak dapat dikembalikan.',
      confirmText: 'Ya, Hapus Tiket',
      confirmClass: 'btn-primary',
      onConfirm: async () => {
        const btn = document.getElementById('deleteDrawerBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Menghapus…'; }
        try {
          const res = await apiPost(API.delete, { schedule_id: scheduleId });
          showToast(res.message, !res.status);
          if (res.status) { closeDrawer(); loadTickets(); }
        } catch(e) { showToast('Gagal menghapus tiket', true); }
      }
    });
  }

  // ---------------- Init ----------------

  document.getElementById('searchInput').addEventListener('input', (e) => {
    state.searchQuery = e.target.value;
    renderTable();
  });

  document.getElementById('sortPicker').value = state.order;
  document.getElementById('sortPicker').addEventListener('change', (e) => {
    state.order = e.target.value;
    loadTickets();
  });

  document.getElementById('bulanPicker').value = state.bulan;
  document.getElementById('bulanPicker').addEventListener('change', (e) => {
    state.bulan = e.target.value;
    loadTickets();
  });

  document.getElementById('btnSalinTugasHariIni')?.addEventListener('click', copyTodayTasksImage);
  document.getElementById('btnTiketBaru').addEventListener('click', openCreateModal);
  document.getElementById('closeModal').addEventListener('click', closeCreateModal);
  document.getElementById('cancelModal').addEventListener('click', closeCreateModal);
  document.getElementById('submitModal').addEventListener('click', handleSubmitTicket);
  document.getElementById('f_netpay_id').addEventListener('blur', handleNetpayLookup);
  document.getElementById('f_netpay_id').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      handleNetpayLookup();
    }
  });

  document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
  document.getElementById('overlay').addEventListener('click', () => {
    closeCreateModal();
    closeDrawer();
  });

  loadTickets();
  startPolling();
</script>

</body>
</html>
