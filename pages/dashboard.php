<?php
require_once '../includes/config.php';
$_SESSION['menu'] = 'dashboard';
$_SESSION['table'] = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/aside.php';
require __DIR__ . '/../includes/navbar.php';

$date = new DateTime();
$tanggal = $date->format('l, d F Y');

$sql = "SELECT * FROM technician";
$stmt = $pdo->query($sql);
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 1. Service Today (job_type = 'Service')
$service_count = $pdo->query("
    SELECT COUNT(*) AS total
    FROM schedules
    WHERE job_type = 'Service'
      AND status NOT IN ('Cancelled')
      AND DATE(`date`) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);

// 2. Install Today (job_type = 'Install' or 'Pemasangan')
$ikr_count = $pdo->query("
    SELECT COUNT(*) AS total
    FROM schedules
    WHERE (job_type = 'Install' OR job_type = 'Pemasangan')
      AND status NOT IN ('Cancelled')
      AND DATE(`date`) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);

// 3. Dismantle Today
$dismantle_count = $pdo->query("
    SELECT COUNT(*) AS total
    FROM schedules
    WHERE job_type = 'Dismantle'
      AND status NOT IN ('Cancelled')
      AND DATE(`date`) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);

// 4. Kendala Lapangan (Status Kendala / Pending Issue)
$kendala_count = $pdo->query("
    SELECT COUNT(*) AS total
    FROM schedules
    WHERE status = 'Kendala'
       OR schedule_id IN (SELECT schedule_id FROM issues_report WHERE status = 'Pending')
")->fetch(PDO::FETCH_ASSOC);

// 5. Rate Penyelesaian Hari Ini (%)
$today_total = (int)$pdo->query("
    SELECT COUNT(*) AS total FROM schedules WHERE DATE(`date`) = CURDATE() AND status NOT IN ('Cancelled')
")->fetch(PDO::FETCH_ASSOC)['total'];

$today_completed = (int)$pdo->query("
    SELECT COUNT(*) AS total FROM schedules WHERE DATE(`date`) = CURDATE() AND status = 'Completed'
")->fetch(PDO::FETCH_ASSOC)['total'];

$completion_rate = ($today_total > 0) ? round(($today_completed / $today_total) * 100) : 100;

// 6. Stok ONT Available
$ont_count = $pdo->query("
    SELECT COUNT(*) AS total FROM ont_inventory WHERE status = 'Available'
")->fetch(PDO::FETCH_ASSOC);
?>

<style>
    /* ── Dashboard reset & base ──────────────────────────────── */
    .db-wrap {
        padding: 1.5rem 0;
    }

    /* ── Page header ─────────────────────────────────────────── */
    .db-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1.5rem;
    }

    .db-page-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .db-date-badge {
        font-size: 0.8rem;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 4px 14px;
        border-radius: 20px;
    }

    /* ── KPI cards ───────────────────────────────────────────── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
        gap: 10px;
        margin-bottom: 1.25rem;
    }

    .kpi-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 10px;
        padding: 0.75rem 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-left: 4px solid;
        text-decoration: none;
        transition: box-shadow .15s ease, transform .15s ease;
    }

    .kpi-card:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, .06);
        transform: translateY(-1px);
    }

    .kpi-card.kpi-service {
        border-left-color: #F59E0B;
    }

    .kpi-card.kpi-install {
        border-left-color: #10B981;
    }

    .kpi-card.kpi-dismantle {
        border-left-color: #EF4444;
    }

    .kpi-card.kpi-kendala {
        border-left-color: #F97316;
    }

    .kpi-card.kpi-ont {
        border-left-color: #0E7C7B;
    }

    .kpi-card.kpi-rate {
        border-left-color: #8B5CF6;
    }

    .kpi-icon-wrap {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .kpi-service .kpi-icon-wrap {
        background: #FFFBEB;
        color: #F59E0B;
    }

    .kpi-install .kpi-icon-wrap {
        background: #ECFDF5;
        color: #10B981;
    }

    .kpi-dismantle .kpi-icon-wrap {
        background: #FEF2F2;
        color: #EF4444;
    }

    .kpi-kendala .kpi-icon-wrap {
        background: #FFF7ED;
        color: #F97316;
    }

    .kpi-ont .kpi-icon-wrap {
        background: #E6F0F0;
        color: #0E7C7B;
    }

    .kpi-rate .kpi-icon-wrap {
        background: #F3E8FF;
        color: #8B5CF6;
    }

    .kpi-text {
        overflow: hidden;
        min-width: 0;
    }

    .kpi-label {
        font-size: 0.76rem;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 2px;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    .kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }

    /* ── Charts row ──────────────────────────────────────────── */
    .charts-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 12px;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 640px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
    }

    .db-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
    }

    .db-card-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: .25rem;
    }

    .db-card-sub {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 400;
    }

    .chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 0.72rem;
        color: #64748b;
        margin-bottom: 10px;
        margin-top: 4px;
    }

    .leg-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .leg-sq {
        width: 10px;
        height: 10px;
        border-radius: 2px;
        display: inline-block;
    }

    /* ── Monitor section ─────────────────────────────────────── */
    .monitor-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        overflow: hidden;
    }

    .monitor-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .monitor-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e293b;
    }

    .monitor-date {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .filter-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        padding: .75rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fafbfc;
    }

    .filter-row input,
    .filter-row select {
        height: 34px;
        padding: 0 10px;
        font-size: 0.8rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #1e293b;
        outline: none;
        transition: border .15s;
    }

    .filter-row input:focus,
    .filter-row select:focus {
        border-color: #3B82F6;
    }

    .filter-row input {
        min-width: 180px;
    }

    .filter-row select {
        min-width: 160px;
        cursor: pointer;
    }

    .filter-label {
        font-size: 0.75rem;
        color: #64748b;
        white-space: nowrap;
    }

    /* ── Tech table ──────────────────────────────────────────── */
    .tech-table-wrap {
        overflow: auto;
        padding: 1rem;

    }

    .tech-table {
        width: 100%;
        border-collapse: collapse;
    }

    .tech-table th {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        padding: 8px 1.25rem;
        text-align: left;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }

    .tech-table td {
        padding: 10px 1.25rem;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }

    .tech-table tr:last-child td {
        border-bottom: none;
    }

    .tech-table tbody tr:hover td {
        background: #fafbfc;
    }

    .tech-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1e293b;
    }

    .tech-name a {
        color: inherit;
        text-decoration: none;
    }

    .tech-name a:hover {
        color: #3B82F6;
    }

    .tech-id-badge {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    .total-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Progress bar */
    .prog-wrap {
        min-width: 200px;
    }

    .prog-chips {
        display: flex;
        gap: 8px;
        margin-bottom: 5px;
        flex-wrap: wrap;
    }

    .prog-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.7rem;
        color: #64748b;
    }

    .chip-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .chip-i {
        background: #10B981;
    }

    .chip-s {
        background: #F59E0B;
    }

    .chip-d {
        background: #EF4444;
    }

    .prog-track {
        height: 8px;
        border-radius: 4px;
        background: #f1f5f9;
        overflow: hidden;
        display: flex;
    }

    .prog-seg {
        height: 100%;
        transition: width .4s ease;
    }

    .seg-install {
        background: #10B981;
    }

    .seg-service {
        background: #F59E0B;
    }

    .seg-dismantle {
        background: #EF4444;
    }

    .prog-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.68rem;
        color: #94a3b8;
        margin-top: 4px;
    }

    .done-val {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
    }

    .done-total {
        font-size: 0.72rem;
        color: #94a3b8;
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="db-wrap">
                <div class="db-page-header">
                    <h1 class="db-page-title">Selamat datang 👋</h1>
                    <span class="db-date-badge"><?= ucfirst($tanggal) ?></span>
                </div>
                <div class="kpi-grid">
                    <a href="<?= BASE_URL ?>pages/ticketing/service/dashboard.php" class="kpi-card kpi-service">
                        <div class="kpi-icon-wrap"><i class="fas fa-tools"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Tiket Service (Hari Ini)</div>
                            <div class="kpi-value" data-target="<?= (int)$service_count['total'] ?>">0</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>pages/ticketing/instalasi/dashboard.php" class="kpi-card kpi-install">
                        <div class="kpi-icon-wrap"><i class="fas fa-wifi"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Tiket Instalasi (Hari Ini)</div>
                            <div class="kpi-value" data-target="<?= (int)$ikr_count['total'] ?>">0</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>pages/dismantle" class="kpi-card kpi-dismantle">
                        <div class="kpi-icon-wrap"><i class="fas fa-trash-restore"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Tiket Dismantle (Hari Ini)</div>
                            <div class="kpi-value" data-target="<?= (int)$dismantle_count['total'] ?>">0</div>
                        </div>
                    </a>
                    <a href="<?= BASE_URL ?>pages/schedule/table_issue_report.php" class="kpi-card kpi-kendala">
                        <div class="kpi-icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Kendala Lapangan</div>
                            <div class="kpi-value" data-target="<?= (int)$kendala_count['total'] ?>">0</div>
                        </div>
                    </a>
                    <!-- <a href="<?= BASE_URL ?>pages/ont" class="kpi-card kpi-ont">
                        <div class="kpi-icon-wrap"><i class="fas fa-box"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Stok ONT Ready</div>
                            <div class="kpi-value" data-target="<?= (int)$ont_count['total'] ?>">0</div>
                        </div>
                    </a> -->
                    <div class="kpi-card kpi-rate">
                        <div class="kpi-icon-wrap"><i class="fas fa-chart-line"></i></div>
                        <div class="kpi-text">
                            <div class="kpi-label">Rate Selesai Hari Ini</div>
                            <div class="kpi-value" data-target="<?= (int)$completion_rate ?>">0%</div>
                        </div>
                    </div>
                </div>
                <div class="charts-row">
                    <div class="db-card">
                        <div class="db-card-title">Laporan Tahun <span class="db-card-sub"><?= date('Y') ?></span></div>
                        <div class="chart-legend">
                            <span class="leg-item"><span class="leg-sq" style="background:#10B981"></span>Instalasi</span>
                            <span class="leg-item"><span class="leg-sq" style="background:#3B82F6;border:1px dashed #3B82F6;background:transparent;border-style:dashed"></span>Service</span>
                            <span class="leg-item"><span class="leg-sq" style="border:1px dotted #EF4444;background:transparent"></span>Dismantle</span>
                        </div>
                        <div id="chart_2"></div>
                    </div>
                    <div class="db-card">
                        <div class="db-card-title">Bulan <span class="db-card-sub"><?= date('F') ?></span></div>
                        <div id="chart_3" style="margin-top:8px"></div>
                    </div>
                </div>
                <div class="monitor-card mb-5">
                    <div class="monitor-header">
                        <div>
                            <div class="monitor-title">Monitoring Pekerjaan Teknisi</div>
                            <div class="monitor-date"><?= ucfirst($tanggal) ?></div>
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="input-icon" style="position:relative">
                            <input type="text" id="kt_datatable_search_query" placeholder="Cari teknisi…" style="padding-left:30px" />
                            <i class="flaticon2-search-1" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);font-size:.8rem;color:#94a3b8"></i>
                        </div>
                        <span class="filter-label">Teknisi:</span>
                        <select id="kt_datatable_search_tech">
                            <option value="">Semua teknisi</option>
                            <?php foreach ($technicians as $t): ?>
                                <option value="<?= $t['tech_id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tech-table-wrap">
                        <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Animate KPI counters on load
    document.querySelectorAll('.kpi-value[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target, 10);
        const dur = 900,
            start = performance.now();

        function step(ts) {
            const p = Math.min((ts - start) / dur, 1);
            el.textContent = Math.round(p * target);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>