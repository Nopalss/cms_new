<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/checkRowExist.php';
$_SESSION['menu'] = 'schedule';

// ambil id/job_type dari POST dulu, fallback ke GET
$id       = isset($_POST['id'])       ? $_POST['id']       : (isset($_GET['id'])       ? $_GET['id']       : null);
$job_type = isset($_POST['job_type']) ? $_POST['job_type'] : (isset($_GET['job_type']) ? $_GET['job_type'] : null);

try {
    if (empty($id)) {
        throw new Exception("ID schedule tidak ditemukan.");
    }

    if ($job_type === "Instalasi") {

        $sql = "SELECT s.*, c.*,
                   EXISTS (
                       SELECT 1 FROM issues_report ir
                       WHERE ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
                   ) AS has_issue
            FROM schedules s
            LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
            LEFT JOIN customers c ON q.netpay_id = c.netpay_id
            WHERE s.schedule_id = :schedule_id
            LIMIT 1";
    } elseif ($job_type === "Service" || $job_type === "Maintenance" || empty($job_type)) {

        $sql = "SELECT s.*,
                       COALESCE(c.name, 'Fasilitas Umum / Jaringan') AS name,
                       COALESCE(c.perumahan, r.perumahan, '-') AS perumahan,
                       COALESCE(c.location, r.location, '-') AS location,
                       COALESCE(c.sharelock, r.sharelock, '') AS sharelock,
                       c.phone, c.phone_contact, c.netpay_id,
                       r.type_issue, r.server, r.deskripsi_issue AS aduan_pelanggan, r.verifikasi_noc,
                   EXISTS (
                       SELECT 1 FROM issues_report ir
                       WHERE ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
                   ) AS has_issue
            FROM schedules s
            LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
            LEFT JOIN request_maintenance r ON s.queue_id = r.queue_id
            LEFT JOIN customers c ON q.netpay_id = c.netpay_id
            WHERE s.schedule_id = :schedule_id
            LIMIT 1";
    } elseif ($job_type === "Dismantle") {

        $sql = "SELECT s.*, c.*, r.type_dismantle as type_issue,
                   EXISTS (
                       SELECT 1 FROM issues_report ir
                       WHERE ir.schedule_id = s.schedule_id AND ir.status = 'Pending'
                   ) AS has_issue
            FROM schedules s
            LEFT JOIN queue_scheduling q ON s.queue_id = q.queue_id
            LEFT JOIN request_dismantle r ON s.queue_id = r.queue_id
            LEFT JOIN customers c ON q.netpay_id = c.netpay_id
            WHERE s.schedule_id = :schedule_id
            LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':schedule_id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    checkRowExist($row, "pages/schedule/");

    // tentukan kolom tanggal
    $dateField = null;
    foreach (['date', 'tanggal', 'jadwal_pemasangan', 'created_at'] as $f) {
        if (!empty($row[$f])) {
            $dateField = $f;
            break;
        }
    }
    if ($dateField) {
        $dt      = new DateTime($row[$dateField]);
        $tanggal = $dt->format('d F Y');
    } else {
        $tanggal = "-";
    }

    $actionDone = [
        "Instalasi" => "ikr",
        "Service"   => "service_report",
        "Dismantle" => "dismantle"
    ];
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'text'   => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style'  => "danger"
    ];
    redirect("pages/schedule/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

// ── Helper variables ─────────────────────────────────────────────
$status   = isset($row['status'])   ? $row['status']   : '-';
$job      = isset($row['job_type']) ? $row['job_type'] : (isset($job_type) ? $job_type : '-');
$jobKey   = !empty($actionDone[$job]) ? $job : (isset($job_type) && !empty($actionDone[$job_type]) ? $job_type : $job);

$job_accent = [
    'Instalasi' => '#10B981',
    'Service'   => '#F59E0B',
    'Dismantle' => '#EF4444',
];
$accent = isset($job_accent[$job]) ? $job_accent[$job] : '#94A3B8';

$job_badge = [
    'Instalasi' => ['bg' => '#D1FAE5', 'color' => '#065F46'],
    'Service'   => ['bg' => '#FEF3C7', 'color' => '#92400E'],
    'Dismantle' => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
];
$jb = isset($job_badge[$job]) ? $job_badge[$job] : ['bg' => '#F1F5F9', 'color' => '#475569'];

$status_style = [
    'Pending'     => ['bg' => '#EFF6FF', 'color' => '#1D4ED8', 'dot' => '#1D4ED8'],
    'Actived'     => ['bg' => '#ECFDF5', 'color' => '#059669', 'dot' => '#059669'],
    'Rescheduled' => ['bg' => '#FFFBEB', 'color' => '#B45309', 'dot' => '#B45309'],
    'Cancelled'   => ['bg' => '#FEF2F2', 'color' => '#DC2626', 'dot' => '#DC2626'],
    'Done'        => ['bg' => '#ECFDF5', 'color' => '#059669', 'dot' => '#059669'],
];
$ss = isset($status_style[$status]) ? $status_style[$status] : ['bg' => '#F1F5F9', 'color' => '#64748B', 'dot' => '#94A3B8'];

// Maps URL
if (!empty($row['sharelock'])) {
    $maps_url = $row['sharelock'];
} else {
    $maps_url = "https://www.google.com/maps/search/?api=1&query="
        . urlencode(isset($row['perumahan']) ? $row['perumahan'] : '')
        . "+" . urlencode(isset($row['location']) ? $row['location'] : '');
}

// WhatsApp
$phone = preg_replace('/[^0-9]/', '', isset($row['phone_contact']) ? $row['phone_contact'] : $row['phone']);
if (substr($phone, 0, 1) === '0') {
    $phone = '62' . substr($phone, 1);
}
$show_actions    = (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin');
$nama_teknisi = isset($_SESSION['name']) && $show_actions ? $_SESSION['name'] : 'Teknisi';
$wa_msg  = "Halo Bapak/Ibu " . (isset($row['name']) ? $row['name'] : '') . ",\n\n";
$wa_msg .= "Perkenalkan saya {$nama_teknisi} dari tim teknisi JABBAR23.\n";
$wa_msg .= "Saya ingin melakukan kunjungan untuk pekerjaan *{$job}* hari ini.\n";
$wa_msg .= "Alamat: " . (isset($row['perumahan']) ? $row['perumahan'] : '') . " " . (isset($row['location']) ? $row['location'] : '') . "\n";
$wa_msg .= "Mohon konfirmasi apakah Bapak/Ibu tersedia.\n\nTerima kasih ";
$wa_url  = "https://wa.me/{$phone}?text=" . urlencode($wa_msg);

// is_active badge
$is_active_val = isset($row['is_active']) ? $row['is_active'] : '';
$is_active_str = ($is_active_val == 1 || strtolower($is_active_val) === 'yes' || strtolower($is_active_val) === 'active')
    ? '<span style="display:inline-flex;align-items:center;gap:4px;background:#ECFDF5;color:#059669;font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.4px"><span style="width:6px;height:6px;border-radius:50%;background:#059669;display:inline-block"></span>Aktif</span>'
    : '<span style="display:inline-flex;align-items:center;gap:4px;background:#FEF2F2;color:#DC2626;font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.4px"><span style="width:6px;height:6px;border-radius:50%;background:#DC2626;display:inline-block"></span>Tidak Aktif</span>';

// show action bar?
$is_pending_resh = in_array($status, ['Pending', 'Rescheduled']);
$is_actived      = ($status === 'Actived');
$show_bar        = $show_actions && ($is_pending_resh || $is_actived) && empty($row['has_issue']);
?>

<!-- ============================================================
     FONT
     ============================================================ -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- ============================================================
     STYLES
     ============================================================ -->
<style>
    :root {
        --font: 'Plus Jakarta Sans', sans-serif;
        --bg: #F0F4FA;
        --card: #FFFFFF;
        --border: #E2E8F0;
        --text1: #0F172A;
        --text2: #475569;
        --text3: #94A3B8;
        --shadow-sm: 0 1px 3px rgba(15, 23, 42, .07), 0 1px 8px rgba(15, 23, 42, .04);
        --shadow: 0 2px 8px rgba(15, 23, 42, .08), 0 4px 20px rgba(15, 23, 42, .05);
        --r: 16px;
        --r-sm: 10px;
    }

    * {
        box-sizing: border-box;
    }

    .dtl-wrap {
        font-family: var(--font) !important;
        background: var(--bg) !important;
        padding: 10px 8px <?= $show_bar ? '100px' : '30px' ?>;
        min-height: 100vh;
    }

    /* ── Back button ─────────────────── */
    .dtl-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text2);
        background: var(--card);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 6px 12px;
        margin-bottom: 10px;
        text-decoration: none !important;
        transition: background .15s, color .15s;
    }

    .dtl-back:hover {
        background: #F8FAFF;
        color: var(--text1);
    }

    .dtl-back i {
        font-size: 11px;
    }

    /* ── Hero card ───────────────────── */
    .dtl-hero {
        background: var(--card);
        border-radius: var(--r);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 10px;
        animation: fadeUp .35s ease both;
    }

    .dtl-hero-stripe {
        height: 4px;
        width: 100%;
        display: block;
    }

    .dtl-hero-body {
        padding: 14px 12px;
    }

    .dtl-hero-toprow {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .dtl-job-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .dtl-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .dtl-status-chip .s-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .dtl-cust-name {
        font-size: 18px;
        font-weight: 900;
        color: var(--text1);
        letter-spacing: -.4px;
        line-height: 1.15;
        margin-bottom: 6px;
    }

    .dtl-hero-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .dtl-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text2);
        background: var(--bg);
        padding: 4px 9px;
        border-radius: 20px;
    }

    /* ── Quick action row (Maps + WA) ── */
    .dtl-quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 10px;
        animation: fadeUp .35s ease .06s both;
    }

    .dtl-qa-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 9px 6px;
        border-radius: var(--r-sm);
        font-size: 11.5px;
        font-weight: 800;
        font-family: var(--font);
        border: none;
        cursor: pointer;
        text-decoration: none !important;
        color: inherit;
        transition: transform .12s, opacity .12s;
        text-align: center;
        white-space: nowrap;
    }

    .dtl-qa-btn:active {
        transform: scale(.95);
        opacity: .85;
    }

    .dtl-qa-btn i {
        font-size: 13px;
    }

    .dtl-qa-maps {
        background: #ECFDF5;
        color: #059669;
        box-shadow: var(--shadow-sm);
    }

    .dtl-qa-wa {
        background: #F0FDF4;
        color: #15803D;
        box-shadow: var(--shadow-sm);
    }

    /* ── Info cards ──────────────────── */
    .dtl-info-card {
        background: var(--card);
        border-radius: var(--r);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 10px;
        animation: fadeUp .35s ease both;
    }

    .dtl-info-card:nth-child(1) {
        animation-delay: .08s;
    }

    .dtl-info-card:nth-child(2) {
        animation-delay: .12s;
    }

    .dtl-info-card:nth-child(3) {
        animation-delay: .16s;
    }

    .dtl-info-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px 9px;
        border-bottom: 1px solid var(--border);
    }

    .dtl-info-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--text1);
    }

    .dtl-info-icon {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
    }

    .dtl-info-body {
        padding: 2px 0;
    }

    .dtl-row {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 9px 12px;
        border-bottom: 1px solid var(--border);
    }

    .dtl-row:last-child {
        border-bottom: none;
    }

    .dtl-row-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .dtl-row-left {
        font-size: 10px;
        font-weight: 700;
        color: var(--text3);
        text-transform: uppercase;
        letter-spacing: .3px;
        min-width: 75px;
        max-width: 85px;
        padding-top: 2px;
        flex-shrink: 0;
    }

    .dtl-row-right {
        font-size: 12px;
        font-weight: 600;
        color: var(--text1);
        flex: 1;
        line-height: 1.35;
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    /* Notes box */
    .dtl-notes-box {
        background: var(--bg);
        border: 1.5px solid var(--border);
        border-radius: var(--r-sm);
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text2);
        line-height: 1.45;
        word-break: break-word;
    }

    .dtl-notes-empty {
        font-size: 11.5px;
        color: var(--text3);
        font-style: italic;
    }

    /* ── Sticky action bar ───────────── */
    .dtl-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--card);
        border-top: 1px solid var(--border);
        padding: 12px 14px;
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
        display: flex;
        gap: 10px;
        z-index: 100;
        box-shadow: 0 -4px 20px rgba(15, 23, 42, .10);
    }

    .dtl-action-secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 13px 14px;
        border-radius: var(--r-sm);
        font-size: 13px;
        font-weight: 700;
        font-family: var(--font);
        background: #FFFBEB;
        color: #B45309;
        border: none;
        cursor: pointer;
        text-decoration: none !important;
        transition: transform .12s, opacity .12s;
        white-space: nowrap;
    }

    .dtl-action-primary {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 14px;
        border-radius: var(--r-sm);
        font-size: 14px;
        font-weight: 800;
        font-family: var(--font);
        border: none;
        cursor: pointer;
        text-decoration: none !important;
        transition: transform .12s, box-shadow .12s;
        color: #fff;
    }

    .dtl-action-primary:active,
    .dtl-action-secondary:active {
        transform: scale(.96);
        opacity: .85;
    }

    .dtl-btn-mulai {
        background: linear-gradient(135deg, #059669, #10B981);
        box-shadow: 0 4px 14px rgba(16, 185, 129, .35);
    }

    .dtl-btn-selesai {
        background: linear-gradient(135deg, #2563EB, #3B82F6);
        box-shadow: 0 4px 14px rgba(37, 99, 235, .35);
    }

    /* Desktop layout */
    @media (min-width: 768px) {
        .dtl-wrap {
            padding: 20px 20px <?= $show_bar ? '100px' : '40px' ?>;
        }

        .dtl-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .dtl-cust-name {
            font-size: 26px;
        }

        .dtl-action-bar {
            max-width: 560px;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            border-radius: var(--r) var(--r) 0 0;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
        }
    }

    @media (min-width: 1024px) {
        .dtl-wrap {
            padding: 24px 28px <?= $show_bar ? '100px' : '40px' ?>;
        }
    }

    /* Animation */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- ============================================================
     HTML
     ============================================================ -->
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container-fluid">
            <div class="dtl-wrap">

                <!-- ── BACK BUTTON ─────────────────────────── -->
                <a href="<?= BASE_URL ?>pages/schedule/" class="dtl-back">
                    <i class="fa fa-arrow-left"></i> Kembali ke Jadwal
                </a>

                <!-- ── HERO CARD ────────────────────────────── -->
                <div class="dtl-hero">
                    <span class="dtl-hero-stripe" style="background:<?= $accent ?>"></span>
                    <div class="dtl-hero-body">

                        <!-- job type + status chips -->
                        <div class="dtl-hero-toprow">
                            <span class="dtl-job-pill"
                                style="background:<?= $jb['bg'] ?>;color:<?= $jb['color'] ?>">
                                <?php if ($job === 'Instalasi'): ?><i class="fa fa-plug"></i>
                                <?php elseif ($job === 'Service'): ?><i class="fa fa-tools"></i>
                                <?php elseif ($job === 'Dismantle'): ?><i class="fa fa-unlink"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($job) ?>
                            </span>
                            <span class="dtl-status-chip"
                                style="background:<?= $ss['bg'] ?>;color:<?= $ss['color'] ?>">
                                <span class="s-dot" style="background:<?= $ss['dot'] ?>"></span>
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>

                        <!-- customer name -->
                        <div class="dtl-cust-name">
                            <?= htmlspecialchars(isset($row['name']) ? $row['name'] : '-') ?>
                        </div>

                        <!-- date + time pills -->
                        <div class="dtl-hero-meta">
                            <span class="dtl-meta-pill">
                                <i class="far fa-calendar-alt"></i>
                                <?= htmlspecialchars($tanggal) ?>
                            </span>
                            <?php if (!empty($row['time'])): ?>
                                <span class="dtl-meta-pill">
                                    <i class="far fa-clock"></i>
                                    <?= date("H:i", strtotime($row['time'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- ── QUICK ACTIONS (Maps + WA) ────────────── -->
                <div class="dtl-quick-actions">
                    <a href="<?= $maps_url ?>" target="_blank" class="dtl-qa-btn dtl-qa-maps">
                        <i class="fa fa-map-marker-alt"></i> Lihat di Maps
                    </a>
                    <a href="<?= $wa_url ?>" target="_blank" class="dtl-qa-btn dtl-qa-wa">
                        <i class="fab fa-whatsapp"></i> Kirim WhatsApp
                    </a>
                </div>

                <!-- ── TWO COLUMN (desktop) ─────────────────── -->
                <div class="dtl-two-col">

                    <!-- ── INFO SCHEDULE ──────────────── -->
                    <div class="dtl-info-card">
                        <div class="dtl-info-header">
                            <span class="dtl-info-icon" style="background:#EFF6FF;color:#2563EB">
                                <i class="far fa-calendar-check"></i>
                            </span>
                            <span class="dtl-info-title">Detail Jadwal</span>
                        </div>
                        <div class="dtl-info-body">

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#F1F5F9;color:#64748B"><i class="fa fa-hashtag"></i></span>
                                <span class="dtl-row-left">Schedule ID</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['schedule_id']) ? $row['schedule_id'] : '-') ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-id-badge"></i></span>
                                <span class="dtl-row-left">Netpay ID</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['netpay_id']) ? $row['netpay_id'] : '-') ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#F3E8FF;color:#9333EA"><i class="fa fa-user-cog"></i></span>
                                <span class="dtl-row-left">Teknisi ID</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['tech_id']) ? $row['tech_id'] : '-') ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#FEF3C7;color:#D97706"><i class="far fa-calendar"></i></span>
                                <span class="dtl-row-left">Tanggal</span>
                                <span class="dtl-row-right"><?= htmlspecialchars($tanggal) ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#FEF3C7;color:#D97706"><i class="far fa-clock"></i></span>
                                <span class="dtl-row-left">Jam</span>
                                <span class="dtl-row-right">
                                    <?= isset($row['time']) ? date("H:i", strtotime($row['time'])) : '-' ?> WIB
                                </span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon"
                                    style="background:<?= $jb['bg'] ?>;color:<?= $jb['color'] ?>">
                                    <?php if ($job === 'Instalasi'): ?><i class="fa fa-plug"></i>
                                    <?php elseif ($job === 'Service'): ?><i class="fa fa-tools"></i>
                                    <?php else: ?><i class="fa fa-unlink"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="dtl-row-left">Job Type</span>
                                <span class="dtl-row-right">
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:<?= $jb['bg'] ?>;color:<?= $jb['color'] ?>;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
                                        <?= htmlspecialchars($job) ?>
                                    </span>
                                </span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon"
                                    style="background:<?= $ss['bg'] ?>;color:<?= $ss['color'] ?>">
                                    <i class="fa fa-circle" style="font-size:8px"></i>
                                </span>
                                <span class="dtl-row-left">Status</span>
                                <span class="dtl-row-right">
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:<?= $ss['bg'] ?>;color:<?= $ss['color'] ?>;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
                                        <span style="width:5px;height:5px;border-radius:50%;background:<?= $ss['dot'] ?>;display:inline-block"></span>
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </span>
                            </div>

                            <?php if (!empty($row['server'])): ?>
                                <div class="dtl-row">
                                    <span class="dtl-row-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-server"></i></span>
                                    <span class="dtl-row-left">Server</span>
                                    <span class="dtl-row-right"><?= htmlspecialchars($row['server']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($row['aduan_pelanggan'])): ?>
                                <div style="background:#FFFBEB; border:1px solid #FDE68A; border-left:4px solid #F59E0B; border-radius:10px; padding:10px 12px; margin: 8px 0;">
                                    <div style="font-size:11px; font-weight:800; color:#B45309; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; display:flex; align-items:center; gap:5px;">
                                        <i class="fa fa-exclamation-triangle"></i> Aduan Pelanggan
                                    </div>
                                    <div style="font-size:13.5px; font-weight:700; color:#78350F; line-height:1.4;"><?= nl2br(htmlspecialchars($row['aduan_pelanggan'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($row['verifikasi_noc'])): ?>
                                <div style="background:#E4F2F1; border:1px solid #B2DFDB; border-left:4px solid #0E7C7B; border-radius:10px; padding:10px 12px; margin: 8px 0;">
                                    <div style="font-size:11px; font-weight:800; color:#0B6362; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; display:flex; align-items:center; gap:5px;">
                                        <i class="fa fa-user-shield"></i> Verifikasi NOC
                                    </div>
                                    <div style="font-size:13.5px; font-weight:700; color:#09403F; line-height:1.4;"><?= nl2br(htmlspecialchars($row['verifikasi_noc'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Catatan -->
                            <div class="dtl-row" style="flex-direction:column;gap:8px">
                                <div style="display:flex;align-items:center;gap:9px">
                                    <span class="dtl-row-icon" style="background:#F0FDF4;color:#10B981"><i class="far fa-sticky-note"></i></span>
                                    <span class="dtl-row-left" style="min-width:auto">Catatan</span>
                                </div>
                                <?php if (!empty($row['catatan'])): ?>
                                    <?php if ($job_type !== "Instalasi" && !empty($row['type_issue'])): ?>
                                        <div class="dtl-notes-box"><?= nl2br(htmlspecialchars($row['type_issue'])) ?></div>
                                    <?php endif; ?>
                                    <div class="dtl-notes-box"><?= nl2br(htmlspecialchars($row['catatan'])) ?></div>
                                <?php else: ?>
                                    <div class="dtl-notes-empty pl-1">Tidak ada catatan</div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>

                    <!-- ── INFO CUSTOMER ───────────────── -->
                    <div class="dtl-info-card">
                        <div class="dtl-info-header">
                            <span class="dtl-info-icon" style="background:#F3E8FF;color:#9333EA">
                                <i class="fa fa-user"></i>
                            </span>
                            <span class="dtl-info-title">Detail Customer</span>
                        </div>
                        <div class="dtl-info-body">

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-id-card"></i></span>
                                <span class="dtl-row-left">Netpay ID</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['netpay_id']) ? $row['netpay_id'] : '-') ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#F3E8FF;color:#9333EA"><i class="fa fa-user"></i></span>
                                <span class="dtl-row-left">Nama</span>
                                <span class="dtl-row-right" style="font-weight:700">
                                    <?= htmlspecialchars(isset($row['name']) ? $row['name'] : '-') ?>
                                </span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#FDF4FF;color:#A855F7"><i class="fa fa-phone"></i></span>
                                <span class="dtl-row-left">No HP / Contact</span>
                                <span class="dtl-row-right">
                                    <?php $display_phone = !empty($row['phone_contact']) ? $row['phone_contact'] : (isset($row['phone']) ? $row['phone'] : ''); ?>
                                    <a href="tel:<?= htmlspecialchars($display_phone) ?>"
                                        style="color:#2563EB;font-weight:700;text-decoration:none">
                                        <?= htmlspecialchars(!empty($display_phone) ? $display_phone : '-') ?>
                                    </a>
                                </span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#EFF6FF;color:#0EA5E9"><i class="fa fa-wifi"></i></span>
                                <span class="dtl-row-left">Paket</span>
                                <span class="dtl-row-right">
                                    <?php if (!empty($row['paket_internet'])): ?>
                                        <span style="font-weight:800;color:#0F172A"><?= htmlspecialchars($row['paket_internet']) ?></span>
                                        <span style="color:#94A3B8;font-size:12px"> Mbps</span>
                                        <?php else: ?>-<?php endif; ?>
                                </span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#ECFDF5;color:#10B981"><i class="fa fa-check-circle"></i></span>
                                <span class="dtl-row-left">Status</span>
                                <span class="dtl-row-right"><?= $is_active_str ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#FEF3C7;color:#D97706"><i class="fa fa-home"></i></span>
                                <span class="dtl-row-left">Perumahan</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['perumahan']) ? $row['perumahan'] : '-') ?></span>
                            </div>

                            <div class="dtl-row">
                                <span class="dtl-row-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-map-marker-alt"></i></span>
                                <span class="dtl-row-left">Alamat</span>
                                <span class="dtl-row-right"><?= htmlspecialchars(isset($row['location']) ? $row['location'] : '-') ?></span>
                            </div>

                        </div>
                    </div>

                </div><!-- end two-col -->

            </div><!-- end dtl-wrap -->
        </div>
    </div>
</div>

<!-- ============================================================
     STICKY ACTION BAR
     ============================================================ -->
<?php if ($show_actions && ($is_pending_resh || $is_actived) && empty($row['has_issue'])): ?>
    <div class="dtl-action-bar">

        <!-- Laporan Kendala (secondary) -->
        <a href="<?= BASE_URL ?>pages/schedule/issue_report.php?id=<?= htmlspecialchars(isset($row['schedule_id']) ? $row['schedule_id'] : '') ?>"
            class="dtl-action-secondary">
            <i class="flaticon2-warning" style="font-size:16px"></i>
            Kendala
        </a>

        <!-- Mulai Kerja or Selesai (primary) -->
        <?php if ($is_pending_resh): ?>
            <button onclick="confirmActiveTask('<?= htmlspecialchars(addslashes($row['schedule_id'])) ?>', 'controllers/schedules/actived.php')"
                class="dtl-action-primary dtl-btn-mulai">
                <i class="fas fa-hourglass-start" style="font-size:16px"></i>
                Mulai Kerja
            </button>

        <?php elseif ($is_actived): ?>
            <?php if (!empty($actionDone[$jobKey])): ?>
                <a href="<?= BASE_URL ?>pages/<?= htmlspecialchars($actionDone[$jobKey]) ?>/create.php?id=<?= urlencode(isset($row['schedule_id']) ? $row['schedule_id'] : '') ?>"
                    class="dtl-action-primary dtl-btn-selesai">
                    <i class="flaticon2-check-mark" style="font-size:16px"></i>
                    Selesai
                </a>
            <?php endif; ?>
        <?php endif; ?>

    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>