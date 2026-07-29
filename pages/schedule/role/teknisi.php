<?php
$tech_id = $_SESSION['id_karyawan'];
$tim_id  = $_SESSION['tim_id'] ?? '';

$sql = "
SELECT
    s.*,
    COALESCE(c.location, rm.location, '-') AS location,
    c.phone,
    c.phone_contact,
    COALESCE(NULLIF(c.phone_contact, ''), c.phone, '-') AS no_tlp,
    COALESCE(c.name, 'Fasilitas Umum / Jaringan') AS name,
    COALESCE(c.perumahan, rm.perumahan, '-') AS perumahan,
    COALESCE(c.sharelock, rm.sharelock, '') AS sharelock,
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

LEFT JOIN customers c
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
ORDER BY CASE WHEN s.status IN ('Done', 'Cancelled') THEN 1 ELSE 0 END ASC, COALESCE(c.perumahan, rm.perumahan, '-') ASC, s.time ASC
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
$total           = count($schedules);
$done_count      = count(array_filter($schedules, fn($s) => $s['status'] === 'Done'));
$aktif_count     = count(array_filter($schedules, fn($s) => in_array($s['status'], ['Pending', 'Actived', 'Rescheduled'])));
$cancelled_count = count(array_filter($schedules, fn($s) => $s['status'] === 'Cancelled'));

// Job type breakdown counts
$instalasi_count = count(array_filter($schedules, fn($s) => $s['job_type'] === 'Instalasi'));
$service_count   = count(array_filter($schedules, fn($s) => $s['job_type'] === 'Service'));
$dismantle_count = count(array_filter($schedules, fn($s) => $s['job_type'] === 'Dismantle'));

$nama_teknisi = $_SESSION['name'] ?? 'Teknisi';

// Fetch list of technicians in the same team for partner selection modal
$all_technicians = [];
if (!empty($tim_id)) {
    $qAllTech = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tim_id = :tim_id OR tech_id = :tech_id ORDER BY name ASC");
    $qAllTech->execute([':tim_id' => $tim_id, ':tech_id' => $tech_id]);
    $all_technicians = $qAllTech->fetchAll(PDO::FETCH_ASSOC);
} else {
    $qAllTech = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tech_id = :tech_id ORDER BY name ASC");
    $qAllTech->execute([':tech_id' => $tech_id]);
    $all_technicians = $qAllTech->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch saved daily shift team from DB (Zero Waste: 1 row per team)
$saved_daily_team = getDailyShiftTeam($pdo, $tim_id, $tech_id);

// Fetch saved daily task claims from DB
$taskClaims = getTaskClaims($pdo, $tim_id);

// Count claimed tasks by current user/team
$my_schedules = array_filter($schedules, function($s) use ($taskClaims, $tech_id, $saved_daily_team) {
    $sid = $s['schedule_id'];
    $c = $taskClaims[$sid] ?? null;
    return ($c && ($c['claimed_by_tech_id'] === $tech_id || in_array($c['claimed_by_tech_id'], $saved_daily_team)));
});
$my_total = count($my_schedules);
$has_my_claims = ($my_total > 0);

$unclaimed_schedules = array_filter($schedules, function($s) use ($taskClaims) {
    return !isset($taskClaims[$s['schedule_id']]);
});
$unclaimed_total = count($unclaimed_schedules);

// Group schedules by Perumahan for Task Modal (sorted alphabetically)
$perumahan_groups = [];
foreach ($schedules as $s_item) {
    $p_name = !empty($s_item['perumahan']) ? trim($s_item['perumahan']) : 'Lain-lain';
    if (!isset($perumahan_groups[$p_name])) {
        $perumahan_groups[$p_name] = [];
    }
    $perumahan_groups[$p_name][] = $s_item;
}
ksort($perumahan_groups);

$days_id   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$months_id = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$today_label = $days_id[date('w')] . ', ' . date('d') . ' ' . $months_id[(int)date('n') - 1] . ' ' . date('Y');

// Progress percentage
$progress_pct = $total > 0 ? round(($done_count / $total) * 100) : 0;

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

// Avatar initials
$initials = '';
foreach (explode(' ', $nama_teknisi) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}
$initials = substr($initials, 0, 2);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* =============================================
   DESIGN SYSTEM TOKENS
   ============================================= */
:root {
    --fn: 'Inter', system-ui, sans-serif;

    /* Neutrals */
    --n-950: #0A0F1E;
    --n-900: #0F172A;
    --n-800: #1E293B;
    --n-700: #334155;
    --n-600: #475569;
    --n-500: #64748B;
    --n-400: #94A3B8;
    --n-300: #CBD5E1;
    --n-200: #E2E8F0;
    --n-100: #F1F5F9;
    --n-50:  #F8FAFC;

    /* Blue — primary */
    --b-700: #1D4ED8;
    --b-600: #2563EB;
    --b-500: #3B82F6;
    --b-100: #DBEAFE;
    --b-50:  #EFF6FF;

    /* Green — instalasi / done */
    --g-700: #047857;
    --g-600: #059669;
    --g-500: #10B981;
    --g-400: #34D399;
    --g-100: #D1FAE5;
    --g-50:  #ECFDF5;

    /* Amber — service / pending */
    --a-700: #B45309;
    --a-600: #D97706;
    --a-500: #F59E0B;
    --a-100: #FEF3C7;
    --a-50:  #FFFBEB;

    /* Red — dismantle / cancel */
    --r-700: #B91C1C;
    --r-600: #DC2626;
    --r-500: #EF4444;
    --r-100: #FEE2E2;
    --r-50:  #FEF2F2;

    /* Purple */
    --p-600: #9333EA;
    --p-100: #F3E8FF;
    --p-50:  #FAF5FF;

    /* Teal */
    --t-700: #0F766E;
    --t-600: #0D9488;
    --t-500: #14B8A6;
    --t-100: #CCFBF1;
    --t-50:  #F0FDFA;

    /* Surfaces */
    --bg:     #EEF2F7;
    --card:   #FFFFFF;
    --topbar: #0F172A;

    /* Shadows */
    --sh-xs: 0 1px 2px rgba(15,23,42,.06);
    --sh-sm: 0 1px 4px rgba(15,23,42,.08), 0 2px 12px rgba(15,23,42,.04);
    --sh:    0 4px 16px rgba(15,23,42,.10), 0 1px 4px rgba(15,23,42,.06);
    --sh-md: 0 8px 28px rgba(15,23,42,.14), 0 2px 8px rgba(15,23,42,.06);
    --sh-lg: 0 20px 48px rgba(15,23,42,.18), 0 4px 12px rgba(15,23,42,.06);

    /* Radii */
    --r-xs: 8px;
    --r-sm: 12px;
    --r:    16px;
    --r-lg: 20px;
    --r-xl: 24px;

    --ease: .2s cubic-bezier(.4,0,.2,1);
}

/* =============================================
   WRAPPER RESET
   ============================================= */
.ops-wrap *, .ops-wrap *::before, .ops-wrap *::after {
    box-sizing: border-box;
}
.ops-wrap {
    font-family: var(--fn);
    background: var(--bg);
    margin: -20px 0 0 !important;
    padding: 0 0 80px !important;
    min-height: 100vh;
    color: var(--n-900);
    overflow-x: hidden;
}
.ops-wrap a { text-decoration: none !important; }

/* =============================================
   TOP BAR (STICKY)
   ============================================= */
.ops-topbar {
    background: var(--topbar);
    border-radius: var(--r-lg);
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 18px;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    margin: 28px 0 14px;
}

.ops-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--b-500), var(--p-600));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    letter-spacing: -.5px;
}

.ops-topbar-info {
    flex: 1;
    min-width: 0;
}

.ops-topbar-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ops-topbar-date {
    font-size: 11px;
    color: rgba(255,255,255,.5);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ops-partner-badge {
    margin-left: auto;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff;
    font-size: 10.5px;
    font-weight: 700;
    padding: 5px 9px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: all 0.2s ease;
    white-space: nowrap;
    max-width: 140px;
    overflow: hidden;
}

.ops-partner-badge span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ops-partner-badge:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.ops-task-badge-btn {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34D399;
    font-size: 10.5px;
    font-weight: 700;
    padding: 5px 9px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: all 0.2s ease;
    white-space: nowrap;
    margin-left: 4px;
    max-width: 110px;
    overflow: hidden;
}

.ops-task-badge-btn span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 480px) {
    .ops-topbar-date {
        display: none !important;
    }
    .ops-topbar {
        padding: 8px 10px !important;
    }
    .ops-partner-badge, .ops-task-badge-btn {
        max-width: 105px !important;
        font-size: 9.5px !important;
        padding: 4px 7px !important;
    }
}

.ops-task-badge-btn:hover {
    background: rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

/* Task Claim Markers */
.ops-claim-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}
.claim-mine { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
.claim-other { background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; }
.claim-free { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; cursor: pointer; }

.btn-claim-action {
    background: linear-gradient(135deg, #2563EB, #1D4ED8);
    color: #FFFFFF !important;
    font-size: 10.5px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 20px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
    transition: all 0.2s ease;
    white-space: nowrap;
}
.btn-claim-action:hover {
    background: linear-gradient(135deg, #1D4ED8, #1E40AF);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.45);
}
.btn-claim-action i {
    font-size: 11px;
}



/* =============================================
   CONTENT AREA
   ============================================= */
.ops-content {
    padding: 0 16px;
    max-width: 900px;
    margin: 0 auto;
}

/* =============================================
   SUMMARY STRIP — Progress + Counts
   ============================================= */
.ops-summary {
    background: var(--card);
    border-radius: var(--r-lg);
    box-shadow: var(--sh-sm);
    padding: 18px;
    margin-bottom: 14px;
    animation: fadeUp .3s ease both;
}

.ops-progress-section {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

/* Circular progress ring */
.ops-ring-wrap {
    position: relative;
    flex-shrink: 0;
}

.ops-ring-svg {
    width: 72px;
    height: 72px;
    transform: rotate(-90deg);
}

.ops-ring-track {
    fill: none;
    stroke: var(--n-100);
    stroke-width: 6;
}

.ops-ring-fill {
    fill: none;
    stroke: url(#ringGrad);
    stroke-width: 6;
    stroke-linecap: round;
    transition: stroke-dashoffset 1s cubic-bezier(.4,0,.2,1) .2s;
}

.ops-ring-label {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.ops-ring-pct {
    font-size: 17px;
    font-weight: 900;
    color: var(--n-900);
    line-height: 1;
    letter-spacing: -1px;
}

.ops-ring-sub {
    font-size: 9px;
    font-weight: 700;
    color: var(--n-400);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 2px;
}

.ops-progress-info {
    flex: 1;
}

.ops-progress-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--n-900);
    letter-spacing: -.4px;
    margin-bottom: 4px;
}

.ops-progress-sub {
    font-size: 12.5px;
    color: var(--n-500);
    font-weight: 500;
    margin-bottom: 10px;
}

.ops-progress-bar {
    height: 5px;
    background: var(--n-100);
    border-radius: 99px;
    overflow: hidden;
}

.ops-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--g-600), var(--g-400));
    border-radius: 99px;
    transition: width 1s cubic-bezier(.4,0,.2,1) .3s;
}

/* Status pills row */
.ops-stat-pills {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.ops-stat-pill {
    border-radius: var(--r-sm);
    padding: 10px 8px;
    text-align: center;
    cursor: default;
}

.ops-pill-num {
    font-size: 22px;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -1px;
    margin-bottom: 3px;
}

.ops-pill-lbl {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    opacity: .7;
}

.pill-total     { background: var(--n-100);    color: var(--n-700); }
.pill-aktif     { background: var(--b-50);     color: var(--b-700); }
.pill-done      { background: var(--g-50);     color: var(--g-700); }
.pill-issue     { background: var(--r-50);     color: var(--r-700); }

/* Job breakdown strip in summary card */
.ops-job-breakdown {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 5px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed var(--n-200);
}

.ops-job-stat {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 5px;
    border-radius: var(--r-sm);
    background: var(--n-50);
    border: 1px solid var(--n-100);
}

.ops-job-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.ops-job-lbl {
    font-size: 10px;
    font-weight: 700;
    color: var(--n-600);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ops-job-count {
    font-size: 11px;
    font-weight: 800;
    color: var(--n-900);
    margin-left: auto;
    flex-shrink: 0;
}



/* =============================================
   FILTER PILLS
   ============================================= */
.ops-filters-wrap {
    margin-bottom: 14px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
    animation: fadeUp .3s ease .1s both;
}
.ops-filters-wrap::-webkit-scrollbar { display: none; }

.ops-filters {
    display: flex;
    gap: 8px;
    white-space: nowrap;
    align-items: center;
}

.ops-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    border: 1.5px solid var(--n-200);
    background: var(--card);
    color: var(--n-600);
    cursor: pointer;
    transition: all var(--ease);
    white-space: nowrap;
    user-select: none;
}
.ops-filter-pill:hover {
    border-color: var(--n-400);
    color: var(--n-900);
    transform: translateY(-1px);
}
.ops-filter-pill.active {
    background: var(--n-900);
    border-color: var(--n-900);
    color: #fff;
    box-shadow: var(--sh-sm);
}
.ops-filter-pill.active.fp-instalasi { background: var(--g-700); border-color: var(--g-700); }
.ops-filter-pill.active.fp-service   { background: var(--a-600); border-color: var(--a-600); }
.ops-filter-pill.active.fp-dismantle { background: var(--r-600); border-color: var(--r-600); }
.ops-filter-pill.active.fp-pending   { background: var(--b-600); border-color: var(--b-600); }
.ops-filter-pill.active.fp-actived   { background: var(--g-600); border-color: var(--g-600); }
.ops-filter-pill.active.fp-done      { background: var(--n-700); border-color: var(--n-700); }

.ops-filter-count {
    background: rgba(0,0,0,.1);
    border-radius: 99px;
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 800;
}
.ops-filter-pill.active .ops-filter-count {
    background: rgba(255,255,255,.2);
}

/* =============================================
   MISSIONS SECTION HEADER
   ============================================= */
.ops-section-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 0 2px;
}
.ops-section-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--n-500);
    text-transform: uppercase;
    letter-spacing: .7px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ops-section-badge {
    font-size: 11px;
    font-weight: 700;
    color: var(--n-500);
    background: var(--n-100);
    padding: 2px 10px;
    border-radius: 99px;
}

/* =============================================
   MISSION CARDS
   ============================================= */
.ops-missions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.ops-card {
    background: var(--card);
    border-radius: var(--r-lg);
    box-shadow: var(--sh);
    overflow: hidden;
    transition: transform var(--ease), box-shadow var(--ease), opacity var(--ease);
    animation: fadeUp .35s ease both;
    position: relative;
    cursor: pointer;
}
.ops-card:hover { transform: translateY(-2px); box-shadow: var(--sh-md); }
.ops-card:active { transform: scale(.985); }

.ops-card:nth-child(1) { animation-delay: .04s; }
.ops-card:nth-child(2) { animation-delay: .08s; }
.ops-card:nth-child(3) { animation-delay: .12s; }
.ops-card:nth-child(4) { animation-delay: .16s; }
.ops-card:nth-child(5) { animation-delay: .20s; }
.ops-card:nth-child(n+6) { animation-delay: .24s; }

.ops-card.is-done, .ops-card.is-cancelled {
    opacity: .6;
}

/* Left accent bar */
.ops-card-accent-bar {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 5px;
    border-radius: 0;
}

.ops-card-inner {
    padding: 16px 16px 16px 21px;
}

/* Top row: Status badge + Job type + Time */
.ops-card-toprow {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.ops-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.ops-status-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}
.sb-pending     { background: var(--b-50);  color: var(--b-700); }
.sb-pending .ops-status-dot     { background: var(--b-500); }
.sb-actived     { background: var(--g-50);  color: var(--g-700); }
.sb-actived .ops-status-dot     { background: var(--g-500); }
.sb-rescheduled { background: var(--a-50);  color: var(--a-700); }
.sb-rescheduled .ops-status-dot { background: var(--a-500); }
.sb-cancelled   { background: var(--r-50);  color: var(--r-700); }
.sb-cancelled .ops-status-dot   { background: var(--r-500); }
.sb-done        { background: var(--g-50);  color: var(--g-700); }
.sb-done .ops-status-dot        { background: var(--g-400); }

.ops-job-chip {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.jc-instalasi { background: var(--g-100); color: #065F46; }
.jc-service   { background: var(--a-100); color: #78350F; }
.jc-dismantle { background: var(--r-100); color: #7F1D1D; }

/* Customer Name */
.ops-cust-name {
    font-size: 19px;
    font-weight: 900;
    color: var(--n-900);
    letter-spacing: -.5px;
    line-height: 1.2;
    margin-bottom: 6px;
}

/* Info Chips Row */
.ops-info-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.ops-info-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: var(--r-xs);
    font-size: 12px;
    font-weight: 600;
    color: var(--n-600);
    background: var(--n-50);
    border: 1px solid var(--n-200);
    max-width: 100%;
}
.ops-info-chip i { font-size: 11px; color: var(--n-400); flex-shrink: 0; }
.ops-info-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.ops-info-chip.ic-loc  i { color: var(--b-500); }
.ops-info-chip.ic-phone i { color: var(--p-600); }
.ops-info-chip.ic-wifi i { color: #0284C7; }
.ops-info-chip.ic-server i { color: var(--t-600); }

/* Highlighted Address and Paket */
.ops-address-highlight {
    background: var(--n-50);
    border: 1px solid var(--n-200);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    margin-bottom: 10px;
}
.ops-address-perumahan {
    font-size: 14px;
    font-weight: 800;
    color: var(--n-900);
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 3px;
}
.ops-address-perumahan i {
    color: var(--g-600);
    font-size: 13px;
}
.ops-address-location {
    font-size: 12.5px;
    color: var(--n-600);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 7px;
}
.ops-address-location i {
    color: var(--b-500);
    font-size: 12px;
}
.ops-paket-highlight {
    background: var(--b-50);
    border: 1.5px solid var(--b-100);
    color: var(--b-700);
    border-radius: var(--r-sm);
    padding: 10px 12px;
    margin-bottom: 10px;
    font-size: 13px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ops-paket-highlight i {
    color: var(--b-500);
    font-size: 14px;
}

/* Callout boxes */
.ops-callouts { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }

.ops-callout {
    border-radius: var(--r-sm);
    padding: 10px 12px;
    border-left: 3px solid transparent;
}
.ops-callout-aduan {
    background: var(--a-50);
    border-color: var(--a-500);
    border: 1px solid var(--a-100);
    border-left: 3px solid var(--a-500);
}
.ops-callout-noc {
    background: var(--t-50);
    border-color: var(--t-500);
    border: 1px solid var(--t-100);
    border-left: 3px solid var(--t-600);
}
.ops-callout-catatan {
    background: var(--p-50);
    border-color: var(--p-100);
    border: 1px solid var(--p-100);
    border-left: 3px solid var(--p-600);
}
.ops-callout-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 3px;
}
.ops-callout-aduan .ops-callout-label { color: var(--a-700); }
.ops-callout-noc   .ops-callout-label { color: var(--t-700); }
.ops-callout-catatan .ops-callout-label { color: var(--p-600); }
.ops-callout-text {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
    word-break: break-word;
}
.ops-callout-aduan .ops-callout-text { color: #78350F; }
.ops-callout-noc   .ops-callout-text { color: #134E4A; }
.ops-callout-catatan .ops-callout-text { color: #6B21A8; }

/* Card divider */
.ops-divider {
    border: none;
    border-top: 1px solid var(--n-100);
    margin: 12px 0;
}

/* Action area */
.ops-card-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ops-actions-secondary {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 6px;
}

.ops-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    border-radius: var(--r-xs);
    font-size: 12.5px;
    font-weight: 700;
    font-family: var(--fn);
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all var(--ease);
    text-decoration: none !important;
    color: inherit;
    white-space: nowrap;
    width: 100%;
}
.ops-btn i { font-size: 12px; }
.ops-btn:active { transform: scale(.93); opacity: .85; }

.ops-btn-detail  { background: var(--b-50); color: var(--b-700); border-color: var(--b-100); }
.ops-btn-detail:hover  { background: var(--b-100); color: var(--b-700); }

.ops-btn-maps    { background: var(--g-50); color: var(--g-700); border-color: var(--g-100); }
.ops-btn-maps:hover    { background: var(--g-100); color: var(--g-700); }

.ops-btn-wa      { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
.ops-btn-wa:hover      { background: #DCFCE7; color: #15803D; }

/* Primary actions row */
.ops-actions-primary {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 8px;
}

.ops-btn-kendala {
    background: var(--a-50);
    color: var(--a-700);
    border-color: var(--a-200, var(--a-100));
    font-size: 12px;
}
.ops-btn-kendala:hover { background: var(--a-100); color: var(--a-700); }

.ops-btn-mulai {
    background: linear-gradient(135deg, #065F46, var(--g-600));
    color: #fff !important;
    border-color: transparent;
    font-size: 13px;
    box-shadow: 0 4px 14px rgba(5,150,105,.35);
}
.ops-btn-mulai:hover { box-shadow: 0 6px 22px rgba(5,150,105,.45); transform: translateY(-1px); color:#fff; }

.ops-btn-selesai {
    background: linear-gradient(135deg, var(--b-700), var(--b-500));
    color: #fff !important;
    border-color: transparent;
    font-size: 13px;
    box-shadow: 0 4px 14px rgba(37,99,235,.35);
}
.ops-btn-selesai:hover { box-shadow: 0 6px 22px rgba(37,99,235,.45); transform: translateY(-1px); color:#fff; }

/* =============================================
   EMPTY STATE
   ============================================= */
.ops-empty {
    background: var(--card);
    border-radius: var(--r-lg);
    padding: 48px 24px;
    text-align: center;
    box-shadow: var(--sh-sm);
    margin-bottom: 16px;
}
.ops-empty-icon { font-size: 48px; margin-bottom: 12px; opacity: .5; }
.ops-empty-title { font-size: 15px; font-weight: 800; color: var(--n-600); margin-bottom: 4px; }
.ops-empty-sub { font-size: 13px; color: var(--n-400); }

/* =============================================
   FILTER EMPTY
   ============================================= */
.ops-filter-empty {
    background: var(--card);
    border-radius: var(--r-lg);
    padding: 32px 20px;
    text-align: center;
    box-shadow: var(--sh-sm);
    display: none;
}
.ops-filter-empty.visible { display: block; }
.ops-filter-empty-text { font-size: 14px; font-weight: 700; color: var(--n-500); }

/* =============================================
   ISSUES SECTION
   ============================================= */
.ops-issues-section {
    margin-bottom: 28px;
    animation: fadeUp .35s ease .15s both;
}

.ops-issues-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--card);
    border-radius: var(--r-lg);
    padding: 14px 16px;
    cursor: pointer;
    box-shadow: var(--sh-sm);
    transition: box-shadow var(--ease);
    user-select: none;
    border: none;
    width: 100%;
    font-family: var(--fn);
}
.ops-issues-toggle:hover { box-shadow: var(--sh); }

.ops-issues-toggle-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ops-issues-icon-wrap {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: var(--r-50);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    color: var(--r-600);
    flex-shrink: 0;
}

.ops-issues-toggle-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--n-900);
}
.ops-issues-toggle-sub {
    font-size: 11px;
    color: var(--n-500);
    font-weight: 500;
}

.ops-issues-count-badge {
    background: var(--r-500);
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 99px;
}
.ops-issues-count-badge.zero {
    background: var(--g-100);
    color: var(--g-700);
}

.ops-issues-chevron {
    font-size: 13px;
    color: var(--n-400);
    transition: transform var(--ease);
}
.ops-issues-toggle.open .ops-issues-chevron {
    transform: rotate(180deg);
}

.ops-issues-body {
    padding-top: 10px;
    display: none;
}
.ops-issues-body.open { display: block; }

.ops-issue-list { display: flex; flex-direction: column; gap: 8px; }

.ops-issue-card {
    background: var(--card);
    border-radius: var(--r);
    padding: 13px 14px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    box-shadow: var(--sh-sm);
    border-left: 3.5px solid transparent;
    transition: box-shadow var(--ease), transform var(--ease);
}
.ops-issue-card:hover { box-shadow: var(--sh); transform: translateY(-1px); }

.ops-issue-ico {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.ops-issue-body { flex: 1; min-width: 0; }

.ops-issue-type {
    font-size: 13px;
    font-weight: 800;
    color: var(--n-900);
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ops-issue-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 2px 8px;
    border-radius: 99px;
    margin-bottom: 4px;
}
.ops-issue-meta {
    font-size: 11px;
    color: var(--n-400);
    font-weight: 500;
}

.ops-issue-actions {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
}
.ops-issue-act-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid var(--n-200);
    background: var(--n-50);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all var(--ease);
    color: var(--n-500);
    text-decoration: none !important;
}
.ops-issue-act-btn i { font-size: 1rem; }
.ops-issue-act-btn:hover { background: var(--card); box-shadow: var(--sh-xs); transform: translateY(-1px); }
.ops-issue-act-btn.abt-detail:hover { border-color: var(--b-500); color: var(--b-600); }
.ops-issue-act-btn.abt-edit:hover   { border-color: var(--a-500); color: var(--a-600); }
.ops-issue-act-btn.abt-del:hover    { border-color: var(--r-500); color: var(--r-600); }

/* =============================================
   DESKTOP RESPONSIVE
   ============================================= */
@media (min-width: 640px) {
    .ops-content { padding: 0 24px; }
    .ops-stat-pills { grid-template-columns: repeat(4, 1fr); }
    .ops-actions-secondary { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 768px) {
    .ops-content { padding: 0 32px; }
    .ops-topbar { margin: 20px 32px 14px; }
    .ops-topbar { padding: 0 28px; }
    .ops-topbar-name { font-size: 15px; }
    .ops-cust-name { font-size: 21px; }
}

/* =============================================
   ANIMATIONS
   ============================================= */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* =============================================
   LEGACY compat — kept for issue modal JS
   ============================================= */
.td-issue-actions-direct { display: flex; gap: 6px; align-items: center; }
.btn-direct-action {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid var(--n-200); display: flex; align-items: center;
    justify-content: center; transition: all var(--ease);
    background: var(--n-50); cursor: pointer;
}
.btn-direct-action:hover { transform: translateY(-1px); background: var(--card); box-shadow: var(--sh-xs); }
.btn-detail-action:hover { border-color: var(--b-500); }
.btn-edit-action:hover   { border-color: var(--a-500); }
.btn-delete-action:hover { border-color: var(--r-500); }
</style>


<!-- ============================================================
     MAIN WRAPPER
     ============================================================ -->
<div class="ops-wrap">

    <!-- ══════════════════════════════════════════════
         TOP STATUS BAR (STICKY)
         ══════════════════════════════════════════════ -->
    <div class="ops-topbar">
        <div class="ops-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="ops-topbar-info">
            <div class="ops-topbar-name"><?= htmlspecialchars($nama_teknisi) ?></div>
            <div class="ops-topbar-date"><?= $today_label ?></div>
        </div>
        <div class="d-flex align-items-center ml-auto">
            <button type="button" onclick="openPartnerModal()" class="ops-partner-badge" id="opsPartnerBadge" title="Presensi Rekan Kerja">
                <i class="fa fa-users"></i> <span id="partnerBadgeText">Pilih Tim</span>
            </button>
            <button type="button" onclick="openTaskModal()" class="ops-task-badge-btn" id="opsTaskBadge" title="Pembagian Tugas Per Perumahan">
                <i class="fa fa-map-marked-alt"></i> <span id="taskBadgeText">Pilih Tugas</span>
            </button>
        </div>
    </div>

    <div class="ops-content">

        <!-- ══════════════════════════════════════════════
             SUMMARY STRIP — Progress Ring + Status Pills
             ══════════════════════════════════════════════ -->
        <div class="ops-summary">

            <!-- SVG gradient def -->
            <svg width="0" height="0" style="position:absolute">
                <defs>
                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#059669"/>
                        <stop offset="100%" style="stop-color:#34D399"/>
                    </linearGradient>
                </defs>
            </svg>

            <div class="ops-progress-section">
                <!-- Circular Progress Ring -->
                <div class="ops-ring-wrap">
                    <?php
                    $initial_schedules = $has_my_claims ? $my_schedules : $schedules;
                    $initial_total     = count($initial_schedules);
                    $initial_done      = count(array_filter($initial_schedules, fn($s) => $s['status'] === 'Done'));
                    $initial_aktif     = count(array_filter($initial_schedules, fn($s) => in_array($s['status'], ['Pending', 'Actived', 'Rescheduled'])));
                    $progress_pct      = $initial_total > 0 ? round(($initial_done / $initial_total) * 100) : 0;

                    $circumference = 2 * M_PI * 26; // r=26
                    $dashOffset    = $circumference - ($progress_pct / 100) * $circumference;
                    ?>
                    <svg class="ops-ring-svg" viewBox="0 0 72 72">
                        <circle class="ops-ring-track" cx="36" cy="36" r="26"/>
                        <circle class="ops-ring-fill"
                                cx="36" cy="36" r="26"
                                stroke-dasharray="<?= $circumference ?>"
                                stroke-dashoffset="<?= $dashOffset ?>"
                                id="ringFill"/>
                    </svg>
                    <div class="ops-ring-label">
                        <span class="ops-ring-pct"><?= $progress_pct ?>%</span>
                        <span class="ops-ring-sub">Done</span>
                    </div>
                </div>

                <!-- Progress Text + Bar -->
                <div class="ops-progress-info">
                    <div class="ops-progress-title">Progress Hari Ini</div>
                    <div class="ops-progress-sub"><?= $initial_done ?> dari <?= $initial_total ?> jadwal selesai</div>
                    <div class="ops-progress-bar">
                        <div class="ops-progress-bar-fill" style="width:<?= $progress_pct ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Status Pills -->
            <div class="ops-stat-pills">
                <div class="ops-stat-pill pill-total">
                    <div class="ops-pill-num"><?= $initial_total ?></div>
                    <div class="ops-pill-lbl">Total</div>
                </div>
                <div class="ops-stat-pill pill-aktif">
                    <div class="ops-pill-num"><?= $initial_aktif ?></div>
                    <div class="ops-pill-lbl">Aktif</div>
                </div>
                <div class="ops-stat-pill pill-done">
                    <div class="ops-pill-num"><?= $initial_done ?></div>
                    <div class="ops-pill-lbl">Selesai</div>
                </div>
                <div class="ops-stat-pill pill-issue">
                    <div class="ops-pill-num"><?= count($issues_report) ?></div>
                    <div class="ops-pill-lbl">Kendala</div>
                </div>
            </div>

            <!-- Job Type Breakdown Strip -->
            <?php
            $job_counts_summary = ['Instalasi' => 0, 'Service' => 0, 'Dismantle' => 0];
            foreach ($schedules as $s_item) {
                $jt_item = $s_item['job_type'];
                if (isset($job_counts_summary[$jt_item])) {
                    $job_counts_summary[$jt_item]++;
                } else {
                    $job_counts_summary[$jt_item] = 1;
                }
            }
            ?>
            <div class="ops-job-breakdown">
                <div class="ops-job-stat">
                    <span class="ops-job-dot" style="background:#10B981"></span>
                    <span class="ops-job-lbl">Instalasi</span>
                    <span class="ops-job-count"><?= $job_counts_summary['Instalasi'] ?? 0 ?></span>
                </div>
                <div class="ops-job-stat">
                    <span class="ops-job-dot" style="background:#F59E0B"></span>
                    <span class="ops-job-lbl">Service</span>
                    <span class="ops-job-count"><?= $job_counts_summary['Service'] ?? 0 ?></span>
                </div>
                <div class="ops-job-stat">
                    <span class="ops-job-dot" style="background:#EF4444"></span>
                    <span class="ops-job-lbl">Dismantle</span>
                    <span class="ops-job-count"><?= $job_counts_summary['Dismantle'] ?? 0 ?></span>
                </div>
            </div>
        </div>


        <!-- ══════════════════════════════════════════════
             FILTER PILLS
             ══════════════════════════════════════════════ -->
        <?php if ($total > 0): ?>
        <div class="ops-filters-wrap">
            <div class="ops-filters" id="filterPills">
                <button class="ops-filter-pill <?= !$has_my_claims ? 'active' : '' ?> fp-all" data-filter="all">
                    Semua <span class="ops-filter-count"><?= $total ?></span>
                </button>
                <button class="ops-filter-pill <?= $has_my_claims ? 'active' : '' ?> fp-instalasi" data-filter="claim-mine">
                    📌 Tugas Saya <span class="ops-filter-count"><?= $my_total ?></span>
                </button>
                <button class="ops-filter-pill fp-pending" data-filter="claim-unclaimed">
                    ⚪ Belum Diambil <span class="ops-filter-count"><?= $unclaimed_total ?></span>
                </button>
                <?php
                $status_counts = [];
                $job_counts    = [];
                foreach ($schedules as $s) {
                    $status_counts[$s['status']] = ($status_counts[$s['status']] ?? 0) + 1;
                    $job_counts[$s['job_type']]  = ($job_counts[$s['job_type']] ?? 0) + 1;
                }
                $status_map = [
                    'Pending'     => ['label' => 'Pending',     'cls' => 'fp-pending'],
                    'Actived'     => ['label' => 'Aktif',       'cls' => 'fp-actived'],
                    'Rescheduled' => ['label' => 'Reschedule',  'cls' => 'fp-pending'],
                    'Done'        => ['label' => 'Selesai',     'cls' => 'fp-done'],
                    'Cancelled'   => ['label' => 'Dibatalkan',  'cls' => 'fp-done'],
                ];
                $job_map = [
                    'Instalasi' => ['label' => 'Instalasi', 'cls' => 'fp-instalasi', 'icon' => 'fa-plug'],
                    'Service'   => ['label' => 'Service',   'cls' => 'fp-service',   'icon' => 'fa-tools'],
                    'Dismantle' => ['label' => 'Dismantle', 'cls' => 'fp-dismantle', 'icon' => 'fa-unlink'],
                ];
                foreach ($status_map as $st => $info):
                    if (!isset($status_counts[$st])) continue;
                ?>
                <button class="ops-filter-pill <?= $info['cls'] ?>" data-filter="status-<?= $st ?>">
                    <?= $info['label'] ?> <span class="ops-filter-count"><?= $status_counts[$st] ?></span>
                </button>
                <?php endforeach; ?>
                <?php foreach ($job_map as $jt => $info):
                    if (!isset($job_counts[$jt])) continue;
                ?>
                <button class="ops-filter-pill <?= $info['cls'] ?>" data-filter="job-<?= $jt ?>">
                    <i class="fa <?= $info['icon'] ?>"></i> <?= $info['label'] ?> <span class="ops-filter-count"><?= $job_counts[$jt] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════
             MISSION CARDS
             ══════════════════════════════════════════════ -->
        <div class="ops-section-hdr">
            <span class="ops-section-title">
                <i class="fa fa-list-ul"></i> Jadwal Hari Ini
            </span>
            <span class="ops-section-badge" id="visibleCount"><?= $total ?> jadwal</span>
        </div>

        <div class="ops-missions" id="opsMissions">

            <?php if ($total > 0):
                foreach ($schedules as $s):
                    $jt = $s['job_type'];
                    $st = $s['status'];
                    $sid_item = $s['schedule_id'];
                    $c_info   = $taskClaims[$sid_item] ?? null;
                    $is_mine  = false;
                    $claim_tag_cls = 'claim-free';
                    $claim_text    = '⚪ Belum Diambil';

                    if ($c_info) {
                        if ($c_info['claimed_by_tech_id'] === $tech_id || in_array($c_info['claimed_by_tech_id'], $saved_daily_team)) {
                            $is_mine = true;
                            $claim_tag_cls = 'claim-mine';
                            $claim_text = '📌 Tugas Saya';
                        } else {
                            $claim_tag_cls = 'claim-other';
                            $claim_text = '👤 Diambil: ' . explode(' ', $c_info['claimed_by_name'])[0];
                        }
                    }

                    // Accent color per job type
                    $accent_colors = [
                        'Instalasi' => '#10B981',
                        'Service'   => '#F59E0B',
                        'Dismantle' => '#EF4444',
                    ];
                    $accent = $accent_colors[$jt] ?? '#94A3B8';

                    // Job chip class
                    $jchip = [
                        'Instalasi' => 'jc-instalasi',
                        'Service'   => 'jc-service',
                        'Dismantle' => 'jc-dismantle',
                    ][$jt] ?? '';

                    // Status badge class
                    $sbadge = [
                        'Pending'     => 'sb-pending',
                        'Actived'     => 'sb-actived',
                        'Rescheduled' => 'sb-rescheduled',
                        'Cancelled'   => 'sb-cancelled',
                        'Done'        => 'sb-done',
                    ][$st] ?? '';

                    // Maps URL
                    if (!empty($s['sharelock'])) {
                        $maps_url = $s['sharelock'];
                    } else {
                        $maps_url = "https://www.google.com/maps/search/?api=1&query="
                            . urlencode($s['perumahan']) . "+" . urlencode($s['location']);
                    }

                    // WhatsApp
                    $raw_phone_number = !empty($s['phone_contact']) ? $s['phone_contact'] : ($s['phone'] ?? '');
                    $phone_number = !empty($raw_phone_number) ? $raw_phone_number : '-';
                    $phone = preg_replace('/[^0-9]/', '', (string)$raw_phone_number);
                    if (!empty($phone) && substr($phone, 0, 1) === '0') {
                        $phone = '62' . substr($phone, 1);
                    }
                    $message  = "Halo Bapak/Ibu {$s['name']},\n\n";
                    $message .= "Perkenalkan saya {$nama_teknisi} dari tim teknisi JABBAR23.\n";
                    $message .= "Saya ingin melakukan kunjungan untuk pekerjaan *{$jt}* hari ini.\n";
                    $message .= "Alamat: {$s['perumahan']} {$s['location']}\n";
                    $message .= "Mohon konfirmasi apakah Bapak/Ibu tersedia.\n\nTerima kasih";
                    $wa_url   = !empty($phone) ? "https://wa.me/{$phone}?text=" . urlencode($message) : '#';

                    // Action logic
                    $is_pending_resh   = in_array($st, ['Pending', 'Rescheduled']);
                    $is_actived        = ($st === 'Actived');
                    $show_task_actions = ($is_pending_resh || $is_actived) && empty($s['has_issue']);

                    // Card state class
                    $card_state_class = '';
                    if ($st === 'Done')      $card_state_class = 'is-done';
                    if ($st === 'Cancelled') $card_state_class = 'is-cancelled';

                    // Status label ID
                    $st_label = [
                        'Pending'     => 'Pending',
                        'Actived'     => 'Aktif',
                        'Rescheduled' => 'Reschedule',
                        'Cancelled'   => 'Dibatalkan',
                        'Done'        => 'Selesai',
                    ][$st] ?? $st;
            ?>

            <div class="ops-card <?= $card_state_class ?>"
                 data-status="<?= $st ?>"
                 data-job="<?= $jt ?>"
                 data-claim="<?= $is_mine ? 'mine' : ($c_info ? 'other' : 'unclaimed') ?>"
                 data-detail-url="<?= BASE_URL ?>pages/schedule/detail.php?id=<?= $s['schedule_id'] ?>&job_type=<?= $jt ?>">

                <!-- Left accent bar -->
                <div class="ops-card-accent-bar" style="background:<?= $accent ?>"></div>

                <div class="ops-card-inner">

                    <!-- Top Row: Status + Job + Claim Tag -->
                    <div class="ops-card-toprow d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-1">
                            <span class="ops-status-badge <?= $sbadge ?>">
                                <span class="ops-status-dot"></span>
                                <?= htmlspecialchars($st_label) ?>
                            </span>
                            <span class="ops-job-chip <?= $jchip ?>">
                                <?php if ($jt === 'Instalasi'): ?><i class="fa fa-plug"></i>
                                <?php elseif ($jt === 'Service'): ?><i class="fa fa-tools"></i>
                                <?php else: ?><i class="fa fa-unlink"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($jt) ?>
                            </span>
                        </div>

                        <!-- Claim Marker Badge / Action Button -->
                        <?php if ($is_mine): ?>
                            <span class="ops-claim-badge claim-mine">📌 Tugas Saya</span>
                        <?php elseif ($c_info): ?>
                            <span class="ops-claim-badge claim-other">👤 <?= htmlspecialchars($claim_text) ?></span>
                        <?php else: ?>
                            <button type="button" class="btn-claim-action" onclick="event.stopPropagation(); quickClaimTask('<?= $sid_item ?>')" title="Klik untuk mengklaim tugas ini">
                                <i class="fa fa-hand-pointer"></i> Ambil Tugas
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Customer Name -->
                    <div class="ops-cust-name"><?= htmlspecialchars($s['name']) ?></div>

                    <!-- Info Chips for secondary details -->
                    <div class="ops-info-chips">
                        <span class="ops-info-chip ic-phone">
                            <i class="fa fa-phone"></i>
                            <span><?= htmlspecialchars($phone_number) ?></span>
                        </span>

                        <?php if ($jt !== 'Instalasi' && !empty($s['server'])): ?>
                            <span class="ops-info-chip ic-server">
                                <i class="fa fa-server"></i>
                                <span><?= htmlspecialchars($s['server']) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Highlighted Address Box -->
                    <div class="ops-address-highlight">
                        <div class="ops-address-perumahan">
                            <i class="fa fa-home"></i> <?= htmlspecialchars($s['perumahan']) ?>
                        </div>
                        <div class="ops-address-location">
                            <i class="fa fa-map-marker-alt"></i> <?= htmlspecialchars($s['location']) ?>
                        </div>
                    </div>

                    <!-- Highlighted Paket Block for Instalasi -->
                    <?php if ($jt === 'Instalasi' && !empty($s['paket_internet'])): ?>
                    <div class="ops-paket-highlight">
                        <i class="fa fa-wifi"></i> Paket Internet: <?= htmlspecialchars($s['paket_internet']) ?> Mbps
                    </div>
                    <?php endif; ?>

                    <!-- Highlighted Catatan for Instalasi -->
                    <?php if ($jt === 'Instalasi' && !empty($s['type_issue'])): ?>
                    <div class="ops-callouts">
                        <div class="ops-callout ops-callout-catatan">
                            <div class="ops-callout-label">
                                <i class="fa fa-sticky-note"></i> Catatan Pemasangan
                            </div>
                            <div class="ops-callout-text"><?= htmlspecialchars($s['type_issue']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Callout boxes: Aduan + NOC -->
                    <?php if ($jt !== 'Instalasi' && (!empty($s['aduan_pelanggan']) || !empty($s['verifikasi_noc']))): ?>
                    <div class="ops-callouts">
                        <?php if (!empty($s['aduan_pelanggan'])): ?>
                        <div class="ops-callout ops-callout-aduan">
                            <div class="ops-callout-label">
                                <i class="fa fa-exclamation-triangle"></i> Aduan Pelanggan
                            </div>
                            <div class="ops-callout-text"><?= htmlspecialchars($s['aduan_pelanggan']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($s['verifikasi_noc'])): ?>
                        <div class="ops-callout ops-callout-noc">
                            <div class="ops-callout-label">
                                <i class="fa fa-user-shield"></i> Verifikasi NOC
                            </div>
                            <div class="ops-callout-text"><?= htmlspecialchars($s['verifikasi_noc']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <hr class="ops-divider">

                    <!-- Action Area -->
                    <div class="ops-card-actions">

                        <!-- Secondary: Detail / Maps / WA -->
                        <div class="ops-actions-secondary">
                            <a href="<?= BASE_URL ?>pages/schedule/detail.php?id=<?= $s['schedule_id'] ?>&job_type=<?= $jt ?>"
                               class="ops-btn ops-btn-detail">
                                <i class="flaticon-eye"></i> Detail
                            </a>
                            <a href="<?= $maps_url ?>" target="_blank" class="ops-btn ops-btn-maps">
                                <i class="fa fa-map-marker-alt"></i> Maps
                            </a>
                            <a href="<?= $wa_url ?>" target="_blank" class="ops-btn ops-btn-wa">
                                <i class="fab fa-whatsapp"></i> WA
                            </a>
                        </div>

                        <!-- Primary: Kendala / Mulai / Selesai -->
                        <?php if ($show_task_actions): ?>
                        <div class="ops-actions-primary">
                            <a href="<?= BASE_URL ?>pages/schedule/issue_report.php?id=<?= htmlspecialchars($s['schedule_id']) ?>"
                               class="ops-btn ops-btn-kendala">
                                <i class="flaticon2-warning"></i> Kendala
                            </a>

                            <?php if ($is_pending_resh): ?>
                                <button onclick="confirmActiveTask('<?= htmlspecialchars(addslashes($s['schedule_id'])) ?>', 'controllers/schedules/actived.php')"
                                        class="ops-btn ops-btn-mulai">
                                    <i class="fas fa-hourglass-start"></i> Mulai Kerja
                                </button>
                            <?php elseif ($is_actived && !empty($actionDone[$jt])): ?>
                                <a href="<?= BASE_URL ?>pages/<?= htmlspecialchars($actionDone[$jt]) ?>/create.php?id=<?= urlencode($s['schedule_id']) ?>"
                                   class="ops-btn ops-btn-selesai">
                                    <i class="flaticon2-check-mark"></i> Selesai
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div><!-- /ops-card-actions -->

                </div><!-- /ops-card-inner -->
            </div><!-- /ops-card -->

            <?php endforeach;
            else: ?>

            <div class="ops-empty">
                <div class="ops-empty-icon">📅</div>
                <div class="ops-empty-title">Tidak ada jadwal hari ini</div>
                <div class="ops-empty-sub">Jadwal baru akan muncul di sini</div>
            </div>

            <?php endif; ?>

            <!-- Filter empty state (shown via JS) -->
            <div class="ops-filter-empty" id="filterEmpty">
                <div class="ops-filter-empty-text">😶 Tidak ada jadwal yang sesuai filter ini</div>
            </div>

        </div><!-- /ops-missions -->


        <!-- ══════════════════════════════════════════════
             ISSUE REPORTS — Collapsible
             ══════════════════════════════════════════════ -->
        <div class="ops-issues-section">

            <button class="ops-issues-toggle" id="issuesToggle" onclick="toggleIssues()">
                <div class="ops-issues-toggle-left">
                    <div class="ops-issues-icon-wrap">
                        <i class="fa fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <div class="ops-issues-toggle-title">Laporan Kendala Hari Ini</div>
                        <div class="ops-issues-toggle-sub">
                            <?= count($issues_report) > 0
                                ? count($issues_report) . ' laporan ditemukan'
                                : 'Tidak ada laporan kendala' ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="ops-issues-count-badge <?= count($issues_report) === 0 ? 'zero' : '' ?>">
                        <?= count($issues_report) ?>
                    </span>
                    <i class="fa fa-angle-down ops-issues-chevron"></i>
                </div>
            </button>

            <div class="ops-issues-body <?= count($issues_report) > 0 ? 'open' : '' ?>" id="issuesBody">
                <?php if (count($issues_report) > 0):
                    $issue_palette = [
                        'Pending'    => ['icon_bg' => '#FEF3C7', 'icon_color' => '#D97706', 'chip_bg' => '#FEF3C7', 'chip_color' => '#92400E', 'dot' => '#F59E0B', 'ico' => '⏳', 'border' => '#F59E0B'],
                        'On Process' => ['icon_bg' => '#DBEAFE', 'icon_color' => '#2563EB', 'chip_bg' => '#EFF6FF', 'chip_color' => '#1D4ED8', 'dot' => '#3B82F6', 'ico' => '🔧', 'border' => '#3B82F6'],
                        'Resolved'   => ['icon_bg' => '#D1FAE5', 'icon_color' => '#059669', 'chip_bg' => '#ECFDF5', 'chip_color' => '#065F46', 'dot' => '#10B981', 'ico' => '✅', 'border' => '#10B981'],
                    ];
                ?>
                <div class="ops-issue-list">
                    <?php foreach ($issues_report as $i):
                        $ip = $issue_palette[$i['status']] ?? [
                            'icon_bg' => '#F1F5F9', 'icon_color' => '#64748B',
                            'chip_bg' => '#F1F5F9', 'chip_color' => '#64748B',
                            'dot' => '#CBD5E1', 'ico' => '📋', 'border' => '#CBD5E1'
                        ];
                    ?>
                    <div class="ops-issue-card" style="border-left-color:<?= $ip['border'] ?>">
                        <div class="ops-issue-ico" style="background:<?= $ip['icon_bg'] ?>">
                            <?= $ip['ico'] ?>
                        </div>
                        <div class="ops-issue-body">
                            <div class="ops-issue-type"><?= htmlspecialchars($i['issue_type']) ?></div>
                            <span class="ops-issue-status-chip"
                                  style="background:<?= $ip['chip_bg'] ?>;color:<?= $ip['chip_color'] ?>">
                                <span style="width:5px;height:5px;border-radius:50%;background:<?= $ip['dot'] ?>;display:inline-block"></span>
                                <?= htmlspecialchars($i['status']) ?>
                            </span>
                            <div class="ops-issue-meta">
                                #<?= htmlspecialchars($i['issue_id']) ?> · Sched #<?= htmlspecialchars($i['schedule_id']) ?>
                            </div>
                        </div>

                        <!-- Issue Actions (kept compatible with existing JS) -->
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
                                    data-state="<?= $statusIssueClasses[$i['status']] ?? '' ?>"
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
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="ops-empty" style="margin-top:10px;padding:32px 20px;">
                    <div class="ops-empty-icon">✅</div>
                    <div class="ops-empty-title">Tidak ada laporan kendala</div>
                    <div class="ops-empty-sub">Semua berjalan lancar hari ini 🎉</div>
                </div>
                <?php endif; ?>
            </div><!-- /ops-issues-body -->

        </div><!-- /ops-issues-section -->

    </div><!-- /ops-content -->
</div><!-- /ops-wrap -->


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

<script>
(function() {
    'use strict';

    // ─── Filter Pills ────────────────────────────────────────────
    const pills     = document.querySelectorAll('.ops-filter-pill');
    const cards     = document.querySelectorAll('.ops-card');
    const countBadge = document.getElementById('visibleCount');
    const filterEmpty = document.getElementById('filterEmpty');

    function applyFilter(pillEl) {
        pills.forEach(p => p.classList.remove('active'));
        pillEl.classList.add('active');

        const filter = pillEl.dataset.filter;
        let visible = 0;
        let doneCount = 0;
        let aktifCount = 0;

        cards.forEach(card => {
            const status = card.dataset.status;
            const job    = card.dataset.job;
            const claim  = card.dataset.claim;
            let show = false;

            if (filter === 'all') {
                show = true;
            } else if (filter === 'claim-mine') {
                show = (claim === 'mine');
            } else if (filter === 'claim-unclaimed') {
                show = (claim === 'unclaimed');
            } else if (filter.startsWith('status-')) {
                show = (status === filter.replace('status-', ''));
            } else if (filter.startsWith('job-')) {
                show = (job === filter.replace('job-', ''));
            }

            card.style.display = show ? '' : 'none';
            if (show) {
                visible++;
                if (status === 'Done') doneCount++;
                if (['Pending', 'Actived', 'Rescheduled'].includes(status)) aktifCount++;
            }
        });

        if (countBadge) {
            countBadge.textContent = visible + ' jadwal';
        }
        if (filterEmpty) {
            filterEmpty.classList.toggle('visible', visible === 0);
        }

        // Dynamically update progress card & ring for active filter
        var pct = visible > 0 ? Math.round((doneCount / visible) * 100) : 0;
        var ringPctEl = document.querySelector('.ops-ring-pct');
        var ringSubEl = document.querySelector('.ops-progress-sub');
        var ringFill  = document.getElementById('ringFill');
        var barFill   = document.querySelector('.ops-progress-bar-fill');

        if (ringPctEl) ringPctEl.textContent = pct + '%';
        if (ringSubEl) ringSubEl.textContent = doneCount + ' dari ' + visible + ' jadwal selesai';
        if (barFill) barFill.style.width = pct + '%';
        if (ringFill) {
            var circ = 2 * Math.PI * 26;
            var offset = circ - (pct / 100) * circ;
            ringFill.style.strokeDashoffset = offset;
        }

        // Update pills count
        var pillTot = document.querySelector('.pill-total .ops-pill-num');
        var pillAkt = document.querySelector('.pill-aktif .ops-pill-num');
        var pillDon = document.querySelector('.pill-done .ops-pill-num');
        if (pillTot) pillTot.textContent = visible;
        if (pillAkt) pillAkt.textContent = aktifCount;
        if (pillDon) pillDon.textContent = doneCount;
    }

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            applyFilter(this);
        });
    });

    // Auto trigger default active filter pill on DOMReady
    var activePill = document.querySelector('.ops-filter-pill.active');
    if (activePill) {
        applyFilter(activePill);
    }

    // ─── Clickable Card ──────────────────────────────────────────
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Check if the clicked element or any of its ancestors is an interactive tag
            const isInteractive = e.target.closest('a, button, form, input, select, textarea');
            if (!isInteractive && this.dataset.detailUrl) {
                window.location.href = this.dataset.detailUrl;
            }
        });
    });

    // ─── Issues Toggle ───────────────────────────────────────────
    window.toggleIssues = function() {
        const toggle = document.getElementById('issuesToggle');
        const body   = document.getElementById('issuesBody');
        if (!toggle || !body) return;
        toggle.classList.toggle('open');
        body.classList.toggle('open');
    };

    // Auto-open toggle if there are issues
    const issueCount = <?= count($issues_report) ?>;
    const issueToggle = document.getElementById('issuesToggle');
    if (issueCount > 0 && issueToggle) {
        issueToggle.classList.add('open');
    }

    // ─── Animated ring init ──────────────────────────────────────
    // Ring is driven by PHP inline style; animate with JS for entry
    const ringFill = document.getElementById('ringFill');
    if (ringFill) {
        const circumference = parseFloat(ringFill.getAttribute('stroke-dasharray'));
        const targetOffset  = parseFloat(ringFill.getAttribute('stroke-dashoffset'));
        ringFill.style.strokeDashoffset = circumference; // start from 0%
        requestAnimationFrame(() => {
            ringFill.style.transition = 'stroke-dashoffset 1s cubic-bezier(.4,0,.2,1) .3s';
            ringFill.style.strokeDashoffset = targetOffset;
        });
    }

})();
</script>


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
      const body  = (payload.notification && payload.notification.body)  ? payload.notification.body  : '';
      showCustomFcmNotification(title, body, payload);
    });
  })();
</script>

<!-- Modal Pilih Rekan Kerja Hari Ini -->
<div class="modal fade" id="partnerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0F172A, #1E293B); color: #fff; border: none; padding: 18px 20px;">
                <h5 class="modal-title font-weight-bold" style="font-size: 15px; letter-spacing: -.3px; color: #FFFFFF !important;">
                    👥 Presensi Tim Hari Ini
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <p class="text-muted mb-3" style="font-size: 12.5px; line-height: 1.4;">
                    Pilih teknisi yang bertugas bersama Anda hari ini. Pilihan ini akan <strong>otomatis ter-centang (pre-filled)</strong> saat Anda membuat laporan IKR, Service, atau Dismantle.
                </p>
                <div class="partner-list-box" style="max-height: 250px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
                    <?php foreach ($all_technicians as $t): ?>
                        <?php $isCurrent = ($t['tech_id'] === $tech_id); ?>
                        <label class="partner-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; margin: 0; cursor: pointer; transition: all .15s;">
                            <input type="checkbox" class="chk-partner" value="<?= htmlspecialchars($t['tech_id']) ?>" data-name="<?= htmlspecialchars($t['name']) ?>" <?= $isCurrent ? 'checked disabled' : '' ?> style="width: 18px; height: 18px; accent-color: #2563EB;">
                            <span style="font-size: 13px; font-weight: 600; color: #0F172A;"><?= htmlspecialchars($t['name']) ?> <?= $isCurrent ? '<small class="text-muted">(Saya)</small>' : '' ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer" style="border: none; padding: 12px 20px 20px; background: #F8FAFC;">
                <button type="button" class="btn btn-primary btn-block font-weight-bold" onclick="savePartnersAndProceed()" style="border-radius: 10px; padding: 10px; font-size: 14px; background: #2563EB; border: none;">
                    Lanjut Pilih Tugas Hari Ini &rarr;
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Pilih Wilayah & Tugas Hari Ini -->
<div class="modal fade" id="taskModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 480px;">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0F172A, #1E293B); color: #fff; border: none; padding: 18px 20px;">
                <h5 class="modal-title font-weight-bold" style="font-size: 15px; letter-spacing: -.3px; color: #FFFFFF !important;">
                    🗺️ Pembagian Tugas
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 16px 20px; background: #F8FAFC;">
                <p class="text-muted mb-3" style="font-size: 12px; line-height: 1.4;">
                    Pilih tugas/wilayah perumahan yang akan Anda kerjakan hari ini. Pilihan ini sebagai <strong>penanda jalur</strong> agar tidak bentrok dengan rekan lain.
                </p>
                <div class="task-perumahan-list" style="display: flex; flex-direction: column; gap: 14px;">
                    <?php if (empty($perumahan_groups)): ?>
                        <p class="text-muted text-center py-3" style="font-size:13px">Tidak ada jadwal tugas untuk hari ini.</p>
                    <?php else: ?>
                        <?php foreach ($perumahan_groups as $p_name => $p_items): ?>
                            <div class="perumahan-group-card" style="background: #fff; border-radius: 12px; border: 1px solid #E2E8F0; padding: 12px 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2" style="border-bottom: 1px dashed #E2E8F0;">
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 4px; flex: 1; min-width: 0; padding-right: 8px;">
                                        <span style="font-size: 13px; font-weight: 800; color: #0F172A; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="<?= htmlspecialchars($p_name) ?>">
                                            🏢 <?= htmlspecialchars($p_name) ?>
                                        </span>
                                        <span class="badge badge-light ml-1" style="font-size:10px; background:#F1F5F9; color:#475569; padding: 2px 6px; font-weight:700;">
                                            <?= count($p_items) ?> jadwal
                                        </span>
                                    </div>
                                    <label class="mb-0 d-flex align-items-center" style="font-size: 11px; font-weight: 700; color: #2563EB; cursor: pointer; white-space: nowrap; flex-shrink: 0;">
                                        <input type="checkbox" class="chk-group-perumahan" onchange="toggleGroupCheck(this)" style="accent-color:#2563EB; margin-right: 4px;"> Pilih Semua
                                    </label>
                                </div>
                                <div class="perumahan-items-list" style="display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($p_items as $item): ?>
                                        <?php
                                        $sid = $item['schedule_id'];
                                        $c_info = $taskClaims[$sid] ?? null;
                                        $is_checked = ($c_info && ($c_info['claimed_by_tech_id'] === $tech_id || in_array($c_info['claimed_by_tech_id'], $saved_daily_team)));
                                        $claimed_by_other = ($c_info && !$is_checked) ? $c_info['claimed_by_name'] : '';
                                        ?>
                                        <label class="task-claim-item" style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border-radius: 10px; background: <?= $is_checked ? '#EFF6FF' : '#F8FAFC' ?>; border: 1px solid <?= $is_checked ? '#BFDBFE' : '#F1F5F9' ?>; margin: 0; cursor: pointer; transition: all 0.15s ease;">
                                            <input type="checkbox" class="chk-task-claim" value="<?= htmlspecialchars($sid) ?>" <?= $is_checked ? 'checked' : '' ?> style="width: 18px; height: 18px; margin-top: 2px; flex-shrink: 0; accent-color: #2563EB;">
                                            <div style="flex: 1; min-width: 0;">
                                                <div class="d-flex align-items-center mb-1" style="flex-wrap: wrap;">
                                                    <span class="badge badge-<?= $badgeClasses[$item['job_type']] ?? 'secondary' ?>" style="font-size: 10px; font-weight: 700; padding: 3px 8px; margin-right: 8px; border-radius: 6px; text-transform: uppercase; flex-shrink: 0;">
                                                        <?= htmlspecialchars($item['job_type']) ?>
                                                    </span>
                                                    <span style="font-size: 13px; font-weight: 800; color: #0F172A; line-height: 1.2; word-break: break-word;">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 11.5px; color: #64748B; line-height: 1.35; margin-top: 3px; word-break: break-word;">
                                                    📍 <strong style="color:#334155"><?= htmlspecialchars($p_name) ?></strong> <?= htmlspecialchars($item['location']) ?>
                                                </div>
                                                <?php if ($claimed_by_other): ?>
                                                    <div style="font-size: 10.5px; color: #D97706; font-weight: 700; margin-top: 4px; background: #FEF3C7; padding: 2px 6px; border-radius: 4px; display: inline-block;">
                                                        👤 Diambil: <?= htmlspecialchars($claimed_by_other) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer" style="border: none; padding: 12px 20px 20px; background: #fff;">
                <button type="button" class="btn btn-success btn-block font-weight-bold" onclick="saveTaskClaimsModal()" style="border-radius: 10px; padding: 11px; font-size: 14px; background: #10B981; border: none;">
                    🚀 Simpan & Mulai Bertugas
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var ALL_TECHNICIANS = <?= json_encode($all_technicians) ?>;
    var CURRENT_USER_TECH_ID = <?= json_encode($tech_id) ?>;
    var SAVED_DAILY_TEAM = <?= json_encode($saved_daily_team) ?>;

    function getTodayKey() {
        var d = new Date();
        var month = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return 'ops_daily_team_' + [year, month, day].join('-');
    }

    function loadSavedPartners() {
        if (SAVED_DAILY_TEAM && Array.isArray(SAVED_DAILY_TEAM) && SAVED_DAILY_TEAM.length > 0) {
            return SAVED_DAILY_TEAM;
        }
        var key = getTodayKey();
        var raw = localStorage.getItem(key);
        if (raw) {
            try { return JSON.parse(raw); } catch(e) {}
        }
        return null;
    }

    function updatePartnerBadge(partners) {
        var btnText = document.getElementById('partnerBadgeText');
        if (!btnText) return;

        if (partners && partners.length > 0) {
            var names = [];
            partners.forEach(function(id) {
                var found = ALL_TECHNICIANS.find(function(t) { return t.tech_id === id; });
                if (found) {
                    var firstName = found.name.split(' ')[0];
                    names.push(firstName);
                }
            });
            var namesStr = names.join(', ');
            if (namesStr.length > 10) {
                namesStr = namesStr.substring(0, 10) + '…';
            }
            btnText.innerHTML = 'Tim (' + partners.length + '): ' + namesStr;
        } else {
            btnText.innerHTML = 'Pilih Tim';
        }
    }

    function openPartnerModal() {
        var saved = loadSavedPartners();
        var selectedIds = saved ? saved : [CURRENT_USER_TECH_ID];

        document.querySelectorAll('.chk-partner').forEach(function(chk) {
            if (chk.value === CURRENT_USER_TECH_ID) {
                chk.checked = true;
            } else {
                chk.checked = selectedIds.includes(chk.value);
            }
        });

        $('#partnerModal').modal('show');
    }

    function savePartners() {
        var selectedIds = [];
        document.querySelectorAll('.chk-partner:checked').forEach(function(chk) {
            selectedIds.push(chk.value);
        });

        if (!selectedIds.includes(CURRENT_USER_TECH_ID)) {
            selectedIds.push(CURRENT_USER_TECH_ID);
        }

        // Save to DB via AJAX
        fetch('<?= BASE_URL ?>controllers/schedules/save_daily_team.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ partners: selectedIds })
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.status) {
                SAVED_DAILY_TEAM = selectedIds;
                var key = getTodayKey();
                localStorage.setItem(key, JSON.stringify(selectedIds));
                updatePartnerBadge(selectedIds);
                $('#partnerModal').modal('hide');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tim Berhasil Disimpan',
                        text: 'Rekan kerja Anda telah tersinkronisasi untuk hari ini.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            } else {
                alert(res.message || 'Gagal menyimpan data tim.');
            }
        })
        .catch(function(err) {
            console.error(err);
            SAVED_DAILY_TEAM = selectedIds;
            var key = getTodayKey();
            localStorage.setItem(key, JSON.stringify(selectedIds));
            updatePartnerBadge(selectedIds);
            $('#partnerModal').modal('hide');
        });
    }

    function savePartnersAndProceed() {
        var selectedIds = [];
        document.querySelectorAll('.chk-partner:checked').forEach(function(chk) {
            selectedIds.push(chk.value);
        });

        if (!selectedIds.includes(CURRENT_USER_TECH_ID)) {
            selectedIds.push(CURRENT_USER_TECH_ID);
        }

        fetch('<?= BASE_URL ?>controllers/schedules/save_daily_team.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ partners: selectedIds })
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            SAVED_DAILY_TEAM = selectedIds;
            var key = getTodayKey();
            localStorage.setItem(key, JSON.stringify(selectedIds));
            updatePartnerBadge(selectedIds);
            $('#partnerModal').modal('hide');
            openTaskModal();
        })
        .catch(function() {
            SAVED_DAILY_TEAM = selectedIds;
            $('#partnerModal').modal('hide');
            openTaskModal();
        });
    }

    function openTaskModal() {
        $('#taskModal').modal('show');
    }

    function toggleGroupCheck(masterChk) {
        var groupCard = masterChk.closest('.perumahan-group-card');
        if (!groupCard) return;
        groupCard.querySelectorAll('.chk-task-claim').forEach(function(chk) {
            chk.checked = masterChk.checked;
        });
    }

    function saveTaskClaimsModal() {
        var claimed = [];
        var unclaimed = [];

        document.querySelectorAll('.chk-task-claim').forEach(function(chk) {
            if (chk.checked) {
                claimed.push(chk.value);
            } else {
                unclaimed.push(chk.value);
            }
        });

        fetch('<?= BASE_URL ?>controllers/schedules/save_task_claims.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ claimed: claimed, unclaimed: unclaimed })
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.status) {
                $('#taskModal').modal('hide');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tugas Berhasil Disimpan',
                        text: 'Pembagian wilayah tugas telah disinkronkan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            } else {
                alert(res.message || 'Gagal menyimpan klaim tugas.');
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Terjadi error saat menyimpan klaim tugas.');
        });
    }

    function quickClaimTask(scheduleId) {
        fetch('<?= BASE_URL ?>controllers/schedules/save_task_claims.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ claimed: [scheduleId], unclaimed: [] })
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            if (res.status) {
                window.location.reload();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var saved = loadSavedPartners();
        if (!saved || saved.length === 0) {
            setTimeout(function() {
                openPartnerModal();
            }, 600);
        } else {
            updatePartnerBadge(saved);
        }
    });
</script>