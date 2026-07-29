<?php
$tech_id = $_SESSION['id_karyawan'];
$tim_id  = $_SESSION['tim_id'] ?? '';

$sql = "
SELECT
    s.*,
    c.location,
    c.phone,
    c.phone_contact,
    COALESCE(NULLIF(c.phone_contact, ''), c.phone) AS no_tlp,
    c.name,
    c.perumahan,
    c.sharelock,
    COALESCE(NULLIF(c.paket_internet, ''), reg.paket_internet) AS paket_internet,
    rm.server,
    rm.deskripsi_issue AS aduan_pelanggan,
    rm.verifikasi_noc,

    CASE
        WHEN s.job_type='Service' THEN rm.type_issue
        WHEN s.job_type='Dismantle' THEN rd.type_dismantle
        WHEN s.job_type='Instalasi' THEN rikr.catatan
    END AS type_issue,

    EXISTS (
        SELECT 1 FROM issues_report ir
        WHERE ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
    ) AS has_issue

FROM schedules s

JOIN queue_scheduling q
ON s.queue_id=q.queue_id

JOIN customers c
ON q.netpay_id=c.netpay_id

LEFT JOIN request_maintenance rm
ON q.queue_id=rm.queue_id

LEFT JOIN request_dismantle rd
ON q.queue_id=rd.queue_id

LEFT JOIN request_ikr rikr
ON q.queue_id=rikr.queue_id

LEFT JOIN register reg
ON rikr.registrasi_id=reg.registrasi_id
WHERE s.date = CURDATE()
AND (
    s.tech_id = :tech_id
    OR s.tech_id = :tim_id
)
ORDER BY CASE WHEN s.status IN ('Done', 'Cancelled') THEN 1 ELSE 0 END ASC, s.time ASC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':tech_id' => $tech_id,
    ':tim_id'  => $tim_id
]);

$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);


// mengambil data issues report berdasarkan id teknisi
$sql = "SELECT *
        FROM issues_report
        WHERE 
         (
    reported_by = :tech_id
    OR reported_by = :tim_id
)
          AND created_at >= CURDATE()
          AND created_at < CURDATE() + INTERVAL 1 DAY";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':tech_id' => $tech_id,
    ':tim_id'  => $tim_id
]);

$issues_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// HELPER VARIABLES
// ============================================
$total       = count($schedules);
$done_count  = count(array_filter($schedules, function ($s) {
    return $s['status'] === 'Done';
}));
$aktif_count = count(array_filter($schedules, function ($s) {
    return in_array($s['status'], ['Pending', 'Actived', 'Rescheduled']);
}));

$nama_teknisi = $_SESSION['name'] ?? 'Teknisi';

$days_id   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$months_id = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$today_label = $days_id[date('w')] . ', ' . date('d') . ' ' . $months_id[(int)date('n') - 1] . ' ' . date('Y');

$job_accent = [
    'Instalasi' => '#10B981',
    'Service'   => '#F59E0B',
    'Dismantle' => '#EF4444',
];
$job_badge_class = [
    'Instalasi' => 'badge-instalasi',
    'Service'   => 'badge-service',
    'Dismantle' => 'badge-dismantle',
];
$status_badge_class = [
    'Pending'     => 'status-pending',
    'Actived'     => 'status-actived',
    'Rescheduled' => 'status-rescheduled',
    'Cancelled'   => 'status-cancelled',
    'Done'        => 'status-done',
];

// ini buat style (agar kompatibel dengan logic lain di aplikasi)
$badgeClasses = [
    'Instalasi' => 'success',
    'Service'   => 'warning',
    'Dismantle' => 'danger'
];
$statusClasses = [
    'Pending'     => "info",
    'Actived'     => "primary",
    'Rescheduled' => "warning",
    'Cancelled'   => "danger",
    'Done'        => "success"
];
$actionDone = [
    "Instalasi" => "ikr",
    "Service"   => "service_report",
    "Dismantle" => "dismantle"
];
?>

<!-- ============================================================
     FONT + ICON
     ============================================================ -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- ============================================================
     STYLES
     ============================================================ -->
<style>
    /* =============================================
   CSS VARIABLES
   ============================================= */
    :root {
        --font: 'Plus Jakarta Sans', sans-serif;

        --blue-dark: #1E3A8A;
        --blue: #2563EB;
        --blue-mid: #3B82F6;
        --blue-light: #DBEAFE;
        --blue-faint: #EFF6FF;

        --green: #10B981;
        --green-light: #D1FAE5;
        --green-faint: #ECFDF5;

        --amber: #F59E0B;
        --amber-light: #FEF3C7;
        --amber-faint: #FFFBEB;

        --red: #EF4444;
        --red-light: #FEE2E2;
        --red-faint: #FEF2F2;

        --cyan: #06B6D4;
        --cyan-light: #CFFAFE;

        --purple-light: #F3E8FF;
        --purple: #9333EA;

        --bg: #F0F4FA;
        --card: #FFFFFF;
        --border: #E2E8F0;
        --text-1: #0F172A;
        --text-2: #475569;
        --text-3: #94A3B8;

        --shadow-sm: 0 1px 3px rgba(15, 23, 42, .07), 0 1px 8px rgba(15, 23, 42, .04);
        --shadow: 0 2px 8px rgba(15, 23, 42, .08), 0 4px 20px rgba(15, 23, 42, .05);
        --shadow-md: 0 4px 16px rgba(15, 23, 42, .12), 0 8px 32px rgba(15, 23, 42, .07);

        --r-sm: 10px;
        --r: 16px;
        --r-lg: 20px;
    }

    /* =============================================
   BASE
   ============================================= */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    .td-wrap {
        font-family: var(--font) !important;
        background: var(--bg) !important;
        padding: 14px 14px 40px;
        min-height: 100vh;
    }

    a {
        text-decoration: none !important;
    }

    /* =============================================
   HEADER CARD
   ============================================= */
    .td-hero {
        background: linear-gradient(135deg, var(--blue-dark) 0%, var(--blue) 55%, var(--blue-mid) 100%);
        border-radius: var(--r-lg);
        padding: 22px 20px 20px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 14px;
    }

    .td-hero::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -30px;
        width: 180px;
        height: 180px;
        background: rgba(255, 255, 255, .07);
        border-radius: 50%;
        pointer-events: none;
    }

    .td-hero::after {
        content: '';
        position: absolute;
        bottom: -35px;
        left: 20%;
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, .05);
        border-radius: 50%;
        pointer-events: none;
    }

    .td-hero-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, .15);
        backdrop-filter: blur(8px);
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .4px;
        margin-bottom: 10px;
    }

    .td-hero-name {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: -.5px;
        line-height: 1.15;
        margin-bottom: 4px;
    }

    .td-hero-date {
        font-size: 12px;
        opacity: .75;
        font-weight: 500;
    }

    /* =============================================
   STATS ROW
   ============================================= */
    .td-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 22px;
    }

    .td-stat {
        background: var(--card);
        border-radius: var(--r-sm);
        padding: 14px 10px;
        text-align: center;
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: hidden;
    }

    .td-stat::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 0 0 var(--r-sm) var(--r-sm);
    }

    .td-stat.s-total::after {
        background: var(--blue);
    }

    .td-stat.s-done::after {
        background: var(--green);
    }

    .td-stat.s-aktif::after {
        background: var(--amber);
    }

    .td-stat-num {
        font-size: 28px;
        font-weight: 900;
        color: var(--text-1);
        line-height: 1;
        letter-spacing: -1px;
    }

    .td-stat-label {
        font-size: 10px;
        font-weight: 700;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-top: 3px;
    }

    /* =============================================
   SECTION HEADER
   ============================================= */
    .td-sec-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 4px 0 12px;
    }

    .td-sec-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-1);
        letter-spacing: -.3px;
    }

    .td-sec-badge {
        background: var(--blue-faint);
        color: var(--blue);
        font-size: 11px;
        font-weight: 700;
        padding: 3px 11px;
        border-radius: 20px;
    }

    /* =============================================
   SCHEDULE CARDS
   ============================================= */
    .td-cards {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 26px;
    }

    .td-card {
        background: var(--card);
        border-radius: var(--r);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
        animation: fadeUp .35s ease both;
    }

    .td-card:active {
        transform: scale(.985);
        box-shadow: var(--shadow-md);
    }

    .td-card:nth-child(1) {
        animation-delay: .04s;
    }

    .td-card:nth-child(2) {
        animation-delay: .08s;
    }

    .td-card:nth-child(3) {
        animation-delay: .12s;
    }

    .td-card:nth-child(4) {
        animation-delay: .16s;
    }

    .td-card:nth-child(5) {
        animation-delay: .20s;
    }

    .td-card:nth-child(n+6) {
        animation-delay: .24s;
    }

    .td-card.is-cancelled {
        opacity: .55;
    }

    .td-card-stripe {
        height: 5px;
        width: 100%;
        display: block;
    }

    .td-card-inner {
        padding: 16px;
    }

    /* Card top row */
    .td-card-toprow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 13px;
    }

    .td-job-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 5px 11px;
        border-radius: 20px;
    }

    .badge-instalasi {
        background: var(--green-light);
        color: #065F46;
    }

    .badge-service {
        background: var(--amber-light);
        color: #92400E;
    }

    .badge-dismantle {
        background: var(--red-light);
        color: #991B1B;
    }

    .td-time-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-2);
        background: var(--bg);
        padding: 5px 11px;
        border-radius: 20px;
    }

    /* Customer name */
    .td-cust-name {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-1);
        letter-spacing: -.4px;
        line-height: 1.2;
        margin-bottom: 11px;
    }

    /* Info pills */
    .td-info-list {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .td-info-item {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        font-size: 13px;
        color: var(--text-2);
        font-weight: 500;
        line-height: 1.4;
    }

    .td-info-dot {
        width: 22px;
        height: 22px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .dot-home {
        background: var(--green-faint);
        color: var(--green);
    }

    .dot-loc {
        background: var(--blue-faint);
        color: var(--blue);
    }

    .dot-phone {
        background: var(--purple-light);
        color: var(--purple);
    }

    /* Status + divider */
    .td-card-divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 13px 0;
    }

    .td-status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .td-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 5px 11px;
        border-radius: 20px;
    }

    .td-status-chip .s-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-pending {
        background: var(--blue-faint);
        color: #1D4ED8;
    }

    .status-pending .s-dot {
        background: #1D4ED8;
    }

    .status-actived {
        background: var(--green-faint);
        color: #059669;
    }

    .status-actived .s-dot {
        background: #059669;
    }

    .status-rescheduled {
        background: var(--amber-faint);
        color: #B45309;
    }

    .status-rescheduled .s-dot {
        background: #B45309;
    }

    .status-cancelled {
        background: var(--red-faint);
        color: #DC2626;
    }

    .status-cancelled .s-dot {
        background: #DC2626;
    }

    .status-done {
        background: var(--green-faint);
        color: #059669;
    }

    .status-done .s-dot {
        background: #059669;
    }

    /* ---- Action Buttons ---- */
    .td-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-top: 14px;
    }

    .td-actions form {
        margin: 0;
    }

    .td-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 11px 6px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        font-family: var(--font);
        border: none;
        cursor: pointer;
        transition: transform .12s, opacity .12s;
        text-decoration: none !important;
        color: inherit;
    }

    .td-btn i {
        font-size: 13px;
    }

    .td-btn:active {
        transform: scale(.94);
        opacity: .85;
    }

    .td-btn-detail {
        background: var(--blue-faint);
        color: #1D4ED8;
    }

    .td-btn-maps {
        background: var(--green-faint);
        color: #059669;
    }

    .td-btn-wa {
        background: #F0FDF4;
        color: #15803D;
    }

    .td-btn-kendala {
        background: #FFFBEB;
        color: #B45309;
        border: 1px solid #FDE68A;
    }

    .td-btn-mulai {
        background: linear-gradient(135deg, #059669, #10B981);
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(16, 185, 129, .2);
    }

    .td-btn-selesai {
        background: linear-gradient(135deg, #2563EB, #3B82F6);
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, .2);
    }

    /* ---- High-Impact Callout Cards for Field Technicians ---- */
    .tech-callouts-box {
        margin-top: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .callout-card {
        border-radius: 10px;
        padding: 9px 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .callout-aduan {
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-left: 4px solid #F59E0B;
    }

    .callout-noc {
        background: #E4F2F1;
        border: 1px solid #B2DFDB;
        border-left: 4px solid #0E7C7B;
    }

    .callout-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 3px;
    }

    .callout-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .badge-aduan {
        color: #B45309;
    }

    .badge-noc {
        color: #0B6362;
    }

    .callout-content {
        font-size: 13.5px;
        font-weight: 700;
        line-height: 1.35;
        word-break: break-word;
    }

    .callout-aduan .callout-content {
        color: #78350F;
    }

    .callout-noc .callout-content {
        color: #09403F;
    }

    /* =============================================
   EMPTY STATE
   ============================================= */
    .td-empty {
        padding: 36px 16px;
        text-align: center;
        color: var(--text-2);
    }

    .td-empty-ico {
        font-size: 44px;
        opacity: .35;
        margin-bottom: 10px;
    }

    .td-empty-main {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-2);
    }

    .td-empty-sub {
        font-size: 12px;
        color: var(--text-3);
        margin-top: 4px;
    }

    /* =============================================
   TIMELINE
   ============================================= */
    .td-timeline {
        margin-bottom: 26px;
    }

    .td-tl-item {
        display: flex;
        gap: 12px;
        padding-bottom: 18px;
        position: relative;
    }

    .td-tl-item:not(.tl-last)::before {
        content: '';
        position: absolute;
        left: 25px;
        top: 36px;
        bottom: 0;
        width: 2px;
        background: var(--border);
        border-radius: 2px;
    }

    .td-tl-left {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        width: 50px;
        flex-shrink: 0;
    }

    .td-tl-time {
        font-size: 10px;
        font-weight: 700;
        color: var(--text-3);
        white-space: nowrap;
        letter-spacing: .3px;
    }

    .td-tl-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2.5px solid var(--card);
        box-shadow: 0 0 0 2px currentColor;
        flex-shrink: 0;
    }

    .td-tl-content {
        flex: 1;
        background: var(--card);
        border-radius: var(--r-sm);
        padding: 11px 14px;
        box-shadow: var(--shadow-sm);
        border-left: 3px solid transparent;
    }

    .td-tl-job {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-1);
    }

    .td-tl-addr {
        font-size: 11px;
        color: var(--text-3);
        margin-top: 2px;
        font-weight: 500;
    }

    /* =============================================
   ISSUE CARDS
   ============================================= */
    .td-issues {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 26px;
    }

    .td-issue-card {
        background: var(--card);
        border-radius: var(--r);
        padding: 15px;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: 13px;
        animation: fadeUp .3s ease both;
    }

    .td-issue-icon {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .td-issue-body {
        flex: 1;
        min-width: 0;
    }

    .td-issue-type {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-1);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .td-issue-chip {
        display: inline-block;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 3px 9px;
        border-radius: 20px;
        margin-bottom: 4px;
    }

    .td-issue-meta {
        font-size: 11px;
        color: var(--text-3);
        font-weight: 500;
    }

    .td-issue-action {
        flex-shrink: 0;
    }

    .td-menu-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--bg);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--text-3);
        transition: background .15s;
    }

    .td-menu-btn:hover {
        background: var(--border);
    }

    /* =============================================
   DESKTOP LAYOUT
   ============================================= */
    @media (min-width: 768px) {
        .td-wrap {
            padding: 20px;
        }

        .td-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .td-hero-name {
            font-size: 28px;
        }

        .td-stat-num {
            font-size: 32px;
        }
    }

    @media (min-width: 1024px) {
        .td-wrap {
            padding: 24px 28px;
        }

        .td-two-col {
            grid-template-columns: 3fr 2fr;
        }
    }

    /* =============================================
   ANIMATION
   ============================================= */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(14px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .td-hero {
        animation: fadeUp .4s ease;
    }

    .td-stats .td-stat:nth-child(1) {
        animation: fadeUp .35s ease .05s both;
    }

    .td-stats .td-stat:nth-child(2) {
        animation: fadeUp .35s ease .10s both;
    }

    .td-stats .td-stat:nth-child(3) {
        animation: fadeUp .35s ease .15s both;
    }

    /* Direct Issue Actions */
    .td-issue-actions-direct {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-shrink: 0;
    }
    .btn-direct-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: pointer;
    }
    .btn-direct-action i {
        font-size: 1.1rem;
    }
    .btn-direct-action:hover {
        transform: translateY(-1px);
        background: #ffffff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    .btn-detail-action:hover {
        border-color: #3b82f6;
    }
    .btn-edit-action:hover {
        border-color: #f59e0b;
    }
    .btn-delete-action:hover {
        border-color: #ef4444;
    }
</style>


<!-- ============================================================
     HTML
     ============================================================ -->
<div class="td-wrap">

    <!-- ── HEADER ─────────────────────────────────────────── -->
    <div class="td-hero">
        <div class="td-hero-tag">
            <i class="far fa-calendar-check"></i> Hari Kerja
        </div>
        <div class="td-hero-name"><?= htmlspecialchars($nama_teknisi) ?></div>
        <div class="td-hero-date"><?= $today_label ?></div>
    </div>

    <!-- ── STATS ──────────────────────────────────────────── -->
    <div class="td-stats">
        <div class="td-stat s-total">
            <div class="td-stat-num"><?= $total ?></div>
            <div class="td-stat-label">Total</div>
        </div>
        <div class="td-stat s-done">
            <div class="td-stat-num"><?= $done_count ?></div>
            <div class="td-stat-label">Selesai</div>
        </div>
        <div class="td-stat s-aktif">
            <div class="td-stat-num"><?= $aktif_count ?></div>
            <div class="td-stat-label">Aktif</div>
        </div>
    </div>

    <!-- ── TWO-COLUMN LAYOUT (desktop) ───────────────────── -->
    <div class="td-two-col">

        <!-- ════════════════════════════════════════
             KOLOM KIRI — JADWAL HARI INI
             ════════════════════════════════════════ -->
        <div>
            <div class="td-sec-header">
                <span class="td-sec-title">📋 Jadwal Hari Ini</span>
                <span class="td-sec-badge"><?= $total ?> jadwal</span>
            </div>

            <div class="td-cards">
                <?php if ($total > 0): ?>
                    <?php foreach ($schedules as $s):
                        $jt      = strtolower($s['job_type']);
                        $st      = strtolower($s['status']);
                        $accent  = $job_accent[$s['job_type']] ?? '#94A3B8';
                        $jcls    = $job_badge_class[$s['job_type']] ?? '';
                        $scls    = $status_badge_class[$s['status']] ?? '';

                        // Maps URL
                        if (!empty($s['sharelock'])) {
                            $maps_url = $s['sharelock'];
                        } else {
                            $maps_url = "https://www.google.com/maps/search/?api=1&query="
                                . urlencode($s['perumahan']) . "+" . urlencode($s['location']);
                        }

                        // WhatsApp
                        $phone_number = !empty($s['phone_contact']) ? $s['phone_contact'] : ($s['phone'] ?? '');
                        $phone = preg_replace('/[^0-9]/', '', (string)$phone_number);
                        if (!empty($phone) && substr($phone, 0, 1) === '0') {
                            $phone = '62' . substr($phone, 1);
                        }
                        $message  = "Halo Bapak/Ibu {$s['name']},\n\n";
                        $message .= "Perkenalkan saya {$nama_teknisi} dari tim teknisi JABBAR23.\n";
                        $message .= "Saya ingin melakukan kunjungan untuk pekerjaan *{$s['job_type']}* hari ini.\n";
                        $message .= "Alamat: {$s['perumahan']} {$s['location']}\n";
                        $message .= "Mohon konfirmasi apakah Bapak/Ibu tersedia.\n\nTerima kasih";
                        $wa_url   = "https://wa.me/{$phone}?text=" . urlencode($message);
                    ?>

                        <div class="td-card <?= $st === 'cancelled' ? 'is-cancelled' : '' ?>">
                            <!-- Top accent stripe -->
                            <span class="td-card-stripe" style="background:<?= $accent ?>"></span>

                            <div class="td-card-inner">
                                <!-- Job type + time -->
                                <div class="td-card-toprow">
                                    <span class="td-job-pill <?= $jcls ?>">
                                        <?php if ($s['job_type'] === 'Instalasi'): ?><i class="fa fa-plug mr-1"></i>
                                        <?php elseif ($s['job_type'] === 'Service'): ?><i class="fa fa-tools mr-1"></i>
                                        <?php else: ?><i class="fa fa-unlink mr-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($s['job_type']) ?>
                                    </span>
                                    <span class="td-time-pill">
                                        <i class="far fa-clock"></i>
                                        <?= substr($s['time'], 0, 5) ?>
                                    </span>
                                </div>

                                <!-- Customer name -->
                                <div class="td-cust-name"><?= htmlspecialchars($s['name']) ?></div>

                                <!-- Info -->
                                <div class="td-info-list">
                                    <div class="td-info-item">
                                        <span class="td-info-dot dot-home"><i class="fa fa-home"></i></span>
                                        <span><?= htmlspecialchars($s['perumahan']) ?></span>
                                    </div>
                                    <div class="td-info-item">
                                        <span class="td-info-dot dot-loc"><i class="fa fa-map-marker-alt"></i></span>
                                        <span><?= htmlspecialchars($s['location']) ?></span>
                                    </div>
                                    <div class="td-info-item">
                                        <span class="td-info-dot dot-phone"><i class="fa fa-phone"></i></span>
                                        <span><?= htmlspecialchars($phone_number) ?></span>
                                    </div>
                                    <?php if ($s['job_type'] === 'Instalasi'): ?>
                                        <?php if (!empty($s['paket_internet'])): ?>
                                            <div class="td-info-item">
                                                <span class="td-info-dot" style="background:#E0F2FE; color:#0284C7;"><i class="fa fa-wifi"></i></span>
                                                <span><strong>Paket:</strong> <?= htmlspecialchars($s['paket_internet']) ?> Mbps</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($s['catatan'])): ?>
                                            <div class="td-info-item">
                                                <span class="td-info-dot dot-phone"><i class="fa fa-tools"></i></span>
                                                <span><?= htmlspecialchars($s['catatan']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (!empty($s['server'])): ?>
                                            <div class="td-info-item">
                                                <span class="td-info-dot dot-loc"><i class="fa fa-server"></i></span>
                                                <span><strong>Server:</strong> <?= htmlspecialchars($s['server']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($s['aduan_pelanggan']) || !empty($s['verifikasi_noc'])): ?>
                                             <div class="tech-callouts-box">
                                                 <?php if (!empty($s['aduan_pelanggan'])): ?>
                                                     <div class="callout-card callout-aduan">
                                                         <div class="callout-header">
                                                             <span class="callout-badge badge-aduan">
                                                                 <i class="fa fa-exclamation-triangle"></i> Aduan Pelanggan
                                                             </span>
                                                         </div>
                                                         <div class="callout-content"><?= htmlspecialchars($s['aduan_pelanggan']) ?></div>
                                                     </div>
                                                 <?php endif; ?>

                                                 <?php if (!empty($s['verifikasi_noc'])): ?>
                                                     <div class="callout-card callout-noc">
                                                         <div class="callout-header">
                                                             <span class="callout-badge badge-noc">
                                                                 <i class="fa fa-user-shield"></i> Verifikasi NOC
                                                             </span>
                                                         </div>
                                                         <div class="callout-content"><?= htmlspecialchars($s['verifikasi_noc']) ?></div>
                                                     </div>
                                                 <?php endif; ?>
                                             </div>
                                         <?php endif; ?>
                                         
                                    <?php endif; ?>
                                </div>

                                <hr class="td-card-divider">

                                <!-- Status -->
                                <div class="td-status-row">
                                    <span class="td-status-chip <?= $scls ?>">
                                        <span class="s-dot"></span>
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="td-actions">
                                    <!-- <form action="<?= BASE_URL ?>pages/schedule/detail.php" method="post">
                                        <input type="hidden" name="job_type" value="<?= htmlspecialchars($s['job_type']) ?>">
                                        <button type="submit" name="id"
                                            value="<?= htmlspecialchars($s['schedule_id']) ?>"
                                            class="td-btn td-btn-detail">
                                            <i class="flaticon-eye"></i> Detail
                                        </button>
                                    </form> -->
                                    <a href="<?= BASE_URL ?>pages/schedule/detail.php?id=<?= $s['schedule_id']?>&job_type=<?= $s['job_type']?>"  
                                    
                                        class="td-btn td-btn-detail">
                                        <i class="flaticon-eye"></i> Detail
                                    </a>

                                    <a href="<?= $maps_url ?>" target="_blank" class="td-btn td-btn-maps">
                                        <i class="fa fa-map-marker-alt"></i> Maps
                                    </a>

                                    <a href="<?= $wa_url ?>" target="_blank" class="td-btn td-btn-wa">
                                        <i class="fab fa-whatsapp"></i> WA
                                    </a>
                                </div>

                                <?php
                                $is_pending_resh = in_array($s['status'], ['Pending', 'Rescheduled']);
                                $is_actived      = ($s['status'] === 'Actived');
                                $show_task_actions = ($is_pending_resh || $is_actived) && empty($s['has_issue']);
                                ?>
                                <?php if ($show_task_actions): ?>
                                    <div class="td-actions-task" style="display: grid; grid-template-columns: 1fr 2fr; gap: 8px; margin-top: 8px;">
                                        <!-- Kendala Button -->
                                        <a href="<?= BASE_URL ?>pages/schedule/issue_report.php?id=<?= htmlspecialchars($s['schedule_id']) ?>"
                                            class="td-btn td-btn-kendala">
                                            <i class="flaticon2-warning"></i> Kendala
                                        </a>

                                        <!-- Mulai Kerja or Selesai Button -->
                                        <?php if ($is_pending_resh): ?>
                                            <button onclick="confirmActiveTask('<?= htmlspecialchars(addslashes($s['schedule_id'])) ?>', 'controllers/schedules/actived.php')"
                                                class="td-btn td-btn-mulai">
                                                <i class="fas fa-hourglass-start"></i> Mulai Kerja
                                            </button>
                                        <?php elseif ($is_actived): ?>
                                            <?php if (!empty($actionDone[$s['job_type']])): ?>
                                                <a href="<?= BASE_URL ?>pages/<?= htmlspecialchars($actionDone[$s['job_type']]) ?>/create.php?id=<?= urlencode($s['schedule_id']) ?>"
                                                    class="td-btn td-btn-selesai">
                                                    <i class="flaticon2-check-mark"></i> Selesai
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="td-card">
                        <div class="td-empty">
                            <div class="td-empty-ico">📅</div>
                            <div class="td-empty-main">Tidak ada jadwal hari ini</div>
                            <div class="td-empty-sub">Jadwal baru akan muncul di sini</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ════════════════════════════════════════
             KOLOM KANAN — TIMELINE + ISSUES
             ════════════════════════════════════════ -->
        <div>

            <!-- ── TIMELINE ──────────────────────── -->
            <div class="td-sec-header">
                <span class="td-sec-title">⏱️ Aktivitas Saya</span>
            </div>

            <?php
            $tl_items = array_filter($schedules, function ($s) {
                return $s['status'] !== 'Cancelled';
            });
            $tl_list  = array_values($tl_items);
            $tl_total = count($tl_list);
            ?>

            <?php if ($tl_total > 0): ?>
                <div class="td-timeline">
                    <?php foreach ($tl_list as $idx => $s):
                        $tl_color = $job_accent[$s['job_type']] ?? '#94A3B8';
                        $is_last  = ($idx === $tl_total - 1);
                    ?>
                        <div class="td-tl-item <?= $is_last ? 'tl-last' : '' ?>">
                            <div class="td-tl-left">
                                <span class="td-tl-time"><?= substr($s['time'], 0, 5) ?></span>
                                <span class="td-tl-dot" style="color:<?= $tl_color ?>;background:<?= $tl_color ?>"></span>
                            </div>
                            <a href="<?= BASE_URL ?>pages/schedule/detail.php?id=<?= $s['schedule_id'] ?>"
                                class="td-tl-content"
                                style="border-left-color:<?= $tl_color ?>">
                                <div class="td-tl-job"><?= htmlspecialchars($s['job_type']) ?> — <?= htmlspecialchars($s['name']) ?></div>
                                <div class="td-tl-addr">📍 <?= htmlspecialchars($s['location']) ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="td-card" style="margin-bottom:26px">
                    <div class="td-empty" style="padding:24px 16px">
                        <div class="td-empty-main">Belum ada aktivitas</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ── ISSUE REPORTS ─────────────────── -->
            <div class="td-sec-header">
                <span class="td-sec-title">⚠️ Laporan Kendala</span>
                <?php if (count($issues_report) > 0): ?>
                    <span class="td-sec-badge"><?= count($issues_report) ?></span>
                <?php endif; ?>
            </div>

            <div class="td-issues">
                <?php if (count($issues_report) > 0):
                    $issue_palette = [
                        'Pending'    => ['icon_bg' => '#FEF3C7', 'icon_color' => '#D97706', 'chip_bg' => '#FEF3C7', 'chip_color' => '#92400E', 'ico' => '⏳', 'border' => '#F59E0B'],
                        'On Process' => ['icon_bg' => '#DBEAFE', 'icon_color' => '#2563EB', 'chip_bg' => '#EFF6FF', 'chip_color' => '#1D4ED8', 'ico' => '🔧', 'border' => '#3B82F6'],
                        'Resolved'   => ['icon_bg' => '#D1FAE5', 'icon_color' => '#059669', 'chip_bg' => '#ECFDF5', 'chip_color' => '#065F46', 'ico' => '✅', 'border' => '#10B981'],
                    ];
                    foreach ($issues_report as $i):
                        $ip = $issue_palette[$i['status']] ?? ['icon_bg' => '#F1F5F9', 'icon_color' => '#64748B', 'chip_bg' => '#F1F5F9', 'chip_color' => '#64748B', 'ico' => '📋', 'border' => '#CBD5E1'];
                ?>
                        <div class="td-issue-card"
                            style="border-left: 3.5px solid <?= $ip['border'] ?>">
                            <div class="td-issue-icon" style="background:<?= $ip['icon_bg'] ?>">
                                <?= $ip['ico'] ?>
                            </div>
                            <div class="td-issue-body">
                                <div class="td-issue-type"><?= htmlspecialchars($i['issue_type']) ?></div>
                                <span class="td-issue-chip"
                                    style="background:<?= $ip['chip_bg'] ?>;color:<?= $ip['chip_color'] ?>">
                                    <?= htmlspecialchars($i['status']) ?>
                                </span>
                                <div class="td-issue-meta">
                                    #<?= htmlspecialchars($i['issue_id']) ?> · Sched #<?= htmlspecialchars($i['schedule_id']) ?>
                                </div>
                            </div>
                            <div class="td-issue-actions-direct">
                                <!-- Detail Button -->
                                <button class="btn-direct-action btn-detail-action btn-detail3"
                                    data-id="<?= $i['issue_id'] ?>"
                                    data-schedule="<?= $i['schedule_id'] ?>"
                                    data-reported="<?= $i['reported_by'] ?>"
                                    data-type="<?= $i['issue_type'] ?>"
                                    data-desc="<?= $i['description'] ?>"
                                    data-date="<?= $i['created_at'] ?>"
                                    data-status="<?= $i['status'] ?>"
                                    data-state="<?= $statusIssueClasses[$i['status']] ?>"
                                    title="Detail">
                                    <i class="flaticon-eye text-info"></i>
                                </button>
                                
                                <?php if ($i['status'] == "Pending"): ?>
                                    <!-- Edit Button -->
                                    <a href="<?= BASE_URL ?>pages/schedule/update_issue_report.php?id=<?= $i['issue_id'] ?>"
                                       class="btn-direct-action btn-edit-action"
                                       title="Edit">
                                        <i class="la la-pencil-alt text-warning"></i>
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <button onclick="confirmDeleteTemplate('<?= $i['issue_id'] ?>', 'controllers/schedules/delete_issue_report.php')"
                                       class="btn-direct-action btn-delete-action cursor-pointer"
                                       title="Hapus">
                                        <i class="la la-trash text-danger"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="td-card">
                        <div class="td-empty" style="padding:26px 16px">
                            <div class="td-empty-ico">✅</div>
                            <div class="td-empty-main">Tidak ada laporan kendala</div>
                            <div class="td-empty-sub">Semua berjalan lancar hari ini 🎉</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- end kolom kanan -->
    </div><!-- end td-two-col -->

</div><!-- end td-wrap -->


<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<?php if (!empty($_SESSION['info'])): ?>
    <script>
        Swal.fire({
            icon: "info",
            title: "Schedule sudah dilaporkan",
            text: '<?= addslashes($_SESSION['info']) ?>',
            confirmButtonText: 'Oke',
            customClass: {
                confirmButton: "btn font-weight-bold btn-outline-warning",
                icon: 'm-auto'
            }
        });
    </script>
<?php unset($_SESSION['info']);
endif; ?>

<!-- ============================================================
     FIREBASE WEB PUSH NOTIFICATION SETUP
     ============================================================ -->
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js"></script>
<script>
  (function() {
    const firebaseConfig = {
      apiKey: "AIzaSyBaeK0tPltGdm-KSBGd7mcPtYYbnh2Jik4",
      authDomain: "jtracks-c83ff.firebaseapp.com",
      projectId: "jtracks-c83ff",
      storageBucket: "jtracks-c83ff.firebasestorage.app",
      messagingSenderId: "939022827073",
      appId: "1:939022827073:web:61c4d85ffa95c28ce750c0"
    };

    const vapidKey = "BBTkc48LJwDMlh2tcmF7tZUcned3h5YVb9jqunLDoVMrkRzLpAoDxYP_oWISWygAwMB_4U9avKW0mYuSvVrSE7A";

    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
      console.warn('[FCM] Browser tidak mendukung Service Worker atau Push Notification.');
      return;
    }

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    navigator.serviceWorker.register('<?= BASE_URL ?>firebase-messaging-sw.js')
      .then((registration) => {
        Notification.requestPermission().then((permission) => {
          if (permission === 'granted') {
            messaging.getToken({
              vapidKey: vapidKey,
              serviceWorkerRegistration: registration
            }).then((currentToken) => {
              if (currentToken) {
                // Send FCM Token to server via AJAX
                fetch('<?= BASE_URL ?>pages/schedule/save_fcm_token.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: new URLSearchParams({ fcm_token: currentToken })
                })
                .then(res => res.json())
                .then(data => {
                  console.log('[FCM] Status simpan token:', data.message);
                })
                .catch(err => console.error('[FCM] Gagal simpan token:', err));
              } else {
                console.warn('[FCM] No registration token available.');
              }
            }).catch((err) => console.error('[FCM] Error retrieving token:', err));
          } else {
            console.warn('[FCM] Izin notifikasi ditolak oleh pengguna.');
          }
        });
      })
      .catch((err) => console.error('[FCM] Service Worker registration failed:', err));

    function showCustomFcmNotification(title, body, payload) {
      function escapeHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      // Sound chime effect
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.3);
      } catch (e) {
        console.log('Audio chime error:', e);
      }

      const existing = document.getElementById('fcmPushBanner');
      if (existing) existing.remove();

      const banner = document.createElement('div');
      banner.id = 'fcmPushBanner';
      banner.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999;
        width: 380px;
        max-width: calc(100vw - 40px);
        background: #FFFFFF;
        border-left: 5px solid #0E7C7B;
        border-radius: 12px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18), 0 2px 6px rgba(0, 0, 0, 0.04);
        padding: 16px 18px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #0F172A;
        transform: translateX(120%);
        transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      `;

      banner.innerHTML = `
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 6px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="
              background: #EAF6F5; color: #0E7C7B; width: 32px; height: 32px;
              border-radius: 8px; display: flex; align-items: center; justify-content: center;
              font-size: 16px; flex-shrink: 0;
            ">🔔</div>
            <div style="font-weight: 800; font-size: 13.5px; color: #0F172A; line-height: 1.3;">
              ${escapeHtml(title)}
            </div>
          </div>
          <button type="button" onclick="this.closest('#fcmPushBanner').remove()" style="
            background: none; border: none; color: #94A3B8; font-size: 18px;
            cursor: pointer; padding: 0 4px; line-height: 1; font-weight: 700;
          ">&times;</button>
        </div>

        <div style="
          font-size: 12.5px; color: #475569; line-height: 1.5; margin-bottom: 12px;
          padding-left: 42px; word-break: break-word;
        ">
          ${escapeHtml(body)}
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px; padding-left: 42px;">
          <button type="button" id="btnFcmAction" style="
            background: #0E7C7B; color: #FFFFFF; border: none;
            padding: 6px 14px; border-radius: 6px; font-size: 12px;
            font-weight: 700; cursor: pointer; transition: background 0.15s;
            display: inline-flex; align-items: center; gap: 5px;
          ">
            📲 Buka Jadwal &rarr;
          </button>
        </div>
      `;

      document.body.appendChild(banner);

      requestAnimationFrame(() => {
        banner.style.transform = 'translateX(0)';
      });

      banner.querySelector('#btnFcmAction').addEventListener('click', () => {
        banner.style.transform = 'translateX(120%)';
        setTimeout(() => banner.remove(), 350);
        if (typeof loadTechnicianData === 'function') {
          loadTechnicianData();
        } else {
          window.location.reload();
        }
      });

      setTimeout(() => {
        if (document.body.contains(banner)) {
          banner.style.transform = 'translateX(120%)';
          setTimeout(() => {
            if (document.body.contains(banner)) banner.remove();
          }, 350);
        }
      }, 10000);
    }

    messaging.onMessage((payload) => {
      console.log('[FCM] Foreground Message Received:', payload);
      const title = (payload.notification && payload.notification.title) ? payload.notification.title : 'Notifikasi JTracks';
      const body  = (payload.notification && payload.notification.body) ? payload.notification.body : '';
      showCustomFcmNotification(title, body, payload);
    });
  })();
</script>