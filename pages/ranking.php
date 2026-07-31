<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../helper/redirect.php';
$_SESSION['menu'] = 'ranking';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/aside.php';
require __DIR__ . '/../includes/navbar.php';

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');
?>

<style>
/* ═══════════════════════════════════════════════
   RANKING PAGE — Custom Styles
   ═══════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
    --ikr-primary:   #6366f1;
    --ikr-light:     #eef2ff;
    --ikr-dark:      #4338ca;
    --srv-primary:   #f59e0b;
    --srv-light:     #fffbeb;
    --srv-dark:      #b45309;
    --neutral-50:    #f8fafc;
    --neutral-100:   #f1f5f9;
    --neutral-200:   #e2e8f0;
    --neutral-400:   #94a3b8;
    --neutral-600:   #475569;
    --neutral-800:   #1e293b;
}

.ranking-page * { font-family: 'Inter', sans-serif; box-sizing: border-box; }

/* ── Hero ── */
.ranking-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4c1d95 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    margin-bottom: 1.75rem;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.ranking-hero::before {
    content: ''; position: absolute;
    top: -60px; right: -60px;
    width: 250px; height: 250px;
    background: rgba(255,255,255,.05); border-radius: 50%;
}
.ranking-hero::after {
    content: ''; position: absolute;
    bottom: -80px; left: -30px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,.04); border-radius: 50%;
}
.ranking-hero h1 {
    font-size: 1.75rem; font-weight: 800;
    margin: 0 0 .4rem; letter-spacing: -.5px;
}
.ranking-hero p { font-size: .92rem; color: rgba(255,255,255,.7); margin: 0; }
.hero-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.15); backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,.2); border-radius: 999px;
    padding: .3rem .85rem; font-size: .78rem; font-weight: 600;
    color: #fff; margin-bottom: .85rem;
}

/* ── Filter Bar ── */
.filter-bar {
    background: #fff; border: 1px solid var(--neutral-200);
    border-radius: 16px; padding: 1.1rem 1.5rem;
    margin-bottom: 0; display: flex;
    align-items: center; flex-wrap: wrap; gap: .85rem;
    border-bottom-left-radius: 0; border-bottom-right-radius: 0;
    border-bottom: none;
}
.filter-bar-second {
    background: var(--neutral-50); border: 1px solid var(--neutral-200);
    border-radius: 0 0 16px 16px;
    padding: .75rem 1.5rem 1rem;
    margin-bottom: 1.75rem;
    display: none;
    align-items: center; flex-wrap: wrap; gap: .5rem;
}
.filter-bar-second.show { display: flex; }
.filter-bar-label {
    font-size: .8rem; font-weight: 700;
    color: var(--neutral-600); text-transform: uppercase;
    letter-spacing: .5px; white-space: nowrap;
    display: flex; align-items: center; gap: .35rem;
}

/* Period tabs */
.period-tabs { display: flex; gap: .35rem; flex-wrap: wrap; }
.period-tab {
    padding: .42rem 1rem; border: 1.5px solid var(--neutral-200);
    border-radius: 999px; font-size: .83rem; font-weight: 600;
    color: var(--neutral-600); background: var(--neutral-50);
    cursor: pointer; transition: all .18s; white-space: nowrap;
}
.period-tab:hover  { border-color: var(--ikr-primary); color: var(--ikr-primary); }
.period-tab.active { background: var(--ikr-primary); border-color: var(--ikr-primary); color: #fff; }

/* Month pills */
.month-pills { display: flex; gap: .3rem; flex-wrap: wrap; }
.month-pill {
    padding: .32rem .75rem; border: 1.5px solid var(--neutral-200);
    border-radius: 999px; font-size: .78rem; font-weight: 600;
    color: var(--neutral-600); background: #fff;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.month-pill:hover  { border-color: var(--ikr-primary); color: var(--ikr-primary); }
.month-pill.active { background: var(--ikr-primary); border-color: var(--ikr-primary); color: #fff; }

/* Year picker */
.year-controls { display: flex; align-items: center; gap: .4rem; }
.year-controls.hidden { display: none; }
.year-btn {
    width: 28px; height: 28px; background: var(--neutral-100);
    border: 1.5px solid var(--neutral-200); border-radius: 7px;
    cursor: pointer; font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s; color: var(--neutral-600);
}
.year-btn:hover { background: var(--ikr-primary); color: #fff; border-color: var(--ikr-primary); }
.year-display {
    font-size: .9rem; font-weight: 700;
    color: var(--neutral-800); min-width: 46px; text-align: center;
}

/* Custom range */
.custom-range { display: none; align-items: center; gap: .55rem; flex-wrap: wrap; }
.custom-range.show { display: flex; }
.custom-range label { font-size: .8rem; color: var(--neutral-400); margin: 0; }
.custom-range input[type="date"] {
    height: 34px; font-size: .83rem;
    border: 1.5px solid var(--neutral-200); border-radius: 8px;
    padding: 0 .7rem; color: var(--neutral-800); outline: none;
}
.custom-range input[type="date"]:focus { border-color: var(--ikr-primary); }
.btn-apply {
    height: 34px; padding: 0 1rem;
    background: var(--ikr-primary); color: #fff; border: none;
    border-radius: 8px; font-size: .83rem; font-weight: 600;
    cursor: pointer; transition: opacity .15s;
}
.btn-apply:hover { opacity: .85; }

/* ── Grid ── */
.ranking-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 1.5rem; align-items: start;
}
@media (max-width: 900px) { .ranking-grid { grid-template-columns: 1fr; } }

/* ── Card ── */
.ranking-card {
    background: #fff; border-radius: 20px;
    border: 1px solid var(--neutral-200); overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.05); transition: box-shadow .2s;
}
.ranking-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.1); }

.rc-header {
    padding: 1.25rem 1.5rem 1rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .5rem;
}
.rc-title-group { display: flex; align-items: center; gap: .7rem; }
.rc-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rc-icon.ikr-icon { background: var(--ikr-light); }
.rc-icon.srv-icon { background: var(--srv-light); }
.rc-icon svg { width: 22px; height: 22px; }
.rc-title { font-size: 1.05rem; font-weight: 700; color: var(--neutral-800); margin: 0; }
.rc-subtitle { font-size: .78rem; color: var(--neutral-400); margin: 0; }

/* Breadcrumb */
.rc-breadcrumb {
    display: flex; align-items: center; gap: .3rem; flex-wrap: wrap;
    padding: .6rem 1.5rem; background: var(--neutral-50);
    border-bottom: 1px solid var(--neutral-200); min-height: 42px;
}
.rc-crumb {
    font-size: .8rem; font-weight: 600; color: var(--ikr-primary);
    cursor: pointer; text-decoration: underline;
    text-decoration-color: transparent; transition: text-decoration-color .15s;
    white-space: nowrap;
}
.rc-crumb:hover { text-decoration-color: var(--ikr-primary); }
.rc-crumb.active { color: var(--neutral-400); cursor: default; text-decoration: none; }
.rc-crumb.srv-crumb { color: var(--srv-primary); }
.rc-crumb.srv-crumb:hover { text-decoration-color: var(--srv-primary); }
.rc-sep { color: var(--neutral-400); font-size: .75rem; }

/* List */
.rc-body { padding: 1rem 1.5rem 1.5rem; }
.rank-list { list-style: none; margin: 0; padding: 0; }
.rank-item {
    display: flex; align-items: center; gap: .85rem;
    padding: .7rem .4rem; border-bottom: 1px solid var(--neutral-100);
    cursor: pointer; border-radius: 10px;
    transition: background .15s; position: relative;
}
.rank-item:last-child { border-bottom: none; }
.rank-item:hover { background: var(--neutral-50); }
.rank-item.leaf { cursor: default; }
.rank-item.leaf:hover { background: transparent; }

.rank-badge {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 800; flex-shrink: 0;
}
.rank-badge.gold   { background: #fef9c3; color: #92400e; }
.rank-badge.silver { background: #f1f5f9; color: #64748b; }
.rank-badge.bronze { background: #fff7ed; color: #9a3412; }
.rank-badge.other  { background: var(--neutral-100); color: var(--neutral-600); }

.rank-info { flex: 1; min-width: 0; }
.rank-label {
    font-size: .9rem; font-weight: 600; color: var(--neutral-800);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rank-bar-wrap {
    height: 6px; background: var(--neutral-100);
    border-radius: 999px; margin-top: .3rem; overflow: hidden;
}
.rank-bar {
    height: 100%; border-radius: 999px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
}
.rank-bar.ikr-bar { background: linear-gradient(90deg, #6366f1, #818cf8); }
.rank-bar.srv-bar { background: linear-gradient(90deg, #f59e0b, #fcd34d); }

.rank-count { font-size: .88rem; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.rank-count.ikr-count { color: var(--ikr-primary); }
.rank-count.srv-count { color: var(--srv-dark); }

/* Drill chevron — pure CSS, no FA needed */
.rank-drill-icon {
    width: 16px; height: 16px; flex-shrink: 0;
    border-top: 2px solid var(--neutral-400);
    border-right: 2px solid var(--neutral-400);
    transform: rotate(45deg);
    transition: transform .15s, border-color .15s;
    margin-right: 2px;
}
.rank-item:not(.leaf):hover .rank-drill-icon {
    transform: rotate(45deg) translateX(2px);
    border-color: var(--ikr-primary);
}

/* States */
.rc-state { text-align: center; padding: 3rem 1rem; }
.rc-state .state-icon { font-size: 2.5rem; margin-bottom: .75rem; }
.rc-state p { font-size: .9rem; color: var(--neutral-400); margin: 0; }

.rc-loading {
    display: flex; align-items: center; justify-content: center;
    gap: .6rem; padding: 3rem 1rem;
    font-size: .9rem; color: var(--neutral-400);
}
.spinner {
    width: 22px; height: 22px;
    border: 3px solid var(--neutral-200);
    border-top-color: var(--ikr-primary);
    border-radius: 50%;
    animation: spin .7s linear infinite;
}
.spinner.srv-spin { border-top-color: var(--srv-primary); }
@keyframes spin { to { transform: rotate(360deg); } }

/* Summary */
.rc-summary {
    display: flex; align-items: center; justify-content: space-between;
    padding: .65rem 1.5rem; background: var(--neutral-50);
    border-top: 1px solid var(--neutral-200);
    font-size: .8rem; color: var(--neutral-600);
    flex-wrap: wrap; gap: .4rem;
}
.rc-total-badge {
    font-weight: 700; padding: .2rem .65rem;
    border-radius: 999px; font-size: .78rem;
}
.ikr-total { background: var(--ikr-light); color: var(--ikr-primary); }
.srv-total  { background: var(--srv-light); color: var(--srv-dark); }

/* Divider in filter-bar-second */
.filter-divider {
    width: 1px; height: 24px; background: var(--neutral-200); flex-shrink: 0;
}

@media (max-width: 576px) {
    .ranking-hero { padding: 1.5rem; }
    .ranking-hero h1 { font-size: 1.35rem; }
    .filter-bar, .filter-bar-second { padding-left: 1rem; padding-right: 1rem; }
    .rc-header, .rc-body { padding-left: 1rem; padding-right: 1rem; }
    .rc-breadcrumb, .rc-summary { padding-left: 1rem; padding-right: 1rem; }
    .filter-divider { display: none; }
}
</style>

<div class="content d-flex flex-column flex-column-fluid ranking-page" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <!-- Hero -->
            <div class="ranking-hero">
                <div class="hero-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><rect x="3" y="13" width="3" height="7" rx="1.5" fill="currentColor"/><rect x="8" y="9" width="3" height="11" rx="1.5" fill="currentColor"/><rect x="13" y="4" width="3" height="16" rx="1.5" fill="currentColor" opacity=".6"/><rect x="18" y="11" width="3" height="9" rx="1.5" fill="currentColor"/></svg>
                    Analitik Wilayah
                </div>
                <h1>📊 Ranking per Daerah</h1>
                <p>Pantau distribusi IKR dan Service berdasarkan wilayah — klik untuk drill-down ke Kecamatan, Desa, hingga Perumahan.</p>
            </div>

            <!-- Filter Bar Row 1 — Period tabs -->
            <div class="filter-bar" id="filter-bar">
                <span class="filter-bar-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#475569" stroke-width="2"/><path d="M3 9h18" stroke="#475569" stroke-width="2"/><path d="M8 2v4M16 2v4" stroke="#475569" stroke-width="2" stroke-linecap="round"/></svg>
                    Periode
                </span>
                <div class="period-tabs" id="period-tabs">
                    <button class="period-tab" data-period="today">Hari Ini</button>
                    <button class="period-tab" data-period="week">Minggu Ini</button>
                    <button class="period-tab active" data-period="month">Bulanan</button>
                    <button class="period-tab" data-period="year">Tahunan</button>
                    <button class="period-tab" data-period="custom">Custom</button>
                </div>

                <!-- Year nav (shown for month + year mode) -->
                <div class="year-controls" id="year-controls">
                    <button class="year-btn" id="year-prev">&#8249;</button>
                    <span class="year-display" id="year-display"><?= $currentYear ?></span>
                    <button class="year-btn" id="year-next">&#8250;</button>
                </div>

                <!-- Custom range -->
                <div class="custom-range" id="custom-range">
                    <label>Dari</label>
                    <input type="date" id="date-from" value="<?= date('Y-m-01') ?>">
                    <label>Sampai</label>
                    <input type="date" id="date-to" value="<?= date('Y-m-d') ?>">
                    <button class="btn-apply" id="btn-apply-custom">Terapkan</button>
                </div>
            </div>

            <!-- Filter Bar Row 2 — Month pills (shown when period=month) -->
            <div class="filter-bar-second show" id="month-row">
                <span class="filter-bar-label" style="font-size:.75rem">Bulan</span>
                <div class="month-pills" id="month-pills">
                    <?php
                    $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                    foreach ($months as $i => $m):
                        $active = ($i + 1 === $currentMonth) ? ' active' : '';
                    ?>
                    <button class="month-pill<?= $active ?>" data-month="<?= $i + 1 ?>"><?= $m ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ranking Grid -->
            <div class="ranking-grid">

                <!-- IKR CARD -->
                <div class="ranking-card" id="ikr-card">
                    <div class="rc-header">
                        <div class="rc-title-group">
                            <div class="rc-icon ikr-icon">
                                <!-- Wifi/network SVG -->
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 18.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z" fill="#6366f1"/>
                                    <path d="M8.5 15a5 5 0 0 1 7 0" stroke="#6366f1" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M5.5 12A9 9 0 0 1 18.5 12" stroke="#6366f1" stroke-width="1.8" stroke-linecap="round" opacity=".6"/>
                                    <path d="M2.5 9A13 13 0 0 1 21.5 9" stroke="#6366f1" stroke-width="1.8" stroke-linecap="round" opacity=".35"/>
                                </svg>
                            </div>
                            <div>
                                <p class="rc-title">Ranking IKR</p>
                                <p class="rc-subtitle">Jumlah instalasi per wilayah</p>
                            </div>
                        </div>
                    </div>
                    <div class="rc-breadcrumb" id="ikr-breadcrumb">
                        <span class="rc-crumb active">Kabupaten</span>
                    </div>
                    <div class="rc-body">
                        <div id="ikr-content"><div class="rc-loading"><div class="spinner"></div> Memuat data...</div></div>
                    </div>
                    <div class="rc-summary" id="ikr-summary" style="display:none">
                        <span id="ikr-summary-text">— area ditemukan</span>
                        <span class="rc-total-badge ikr-total" id="ikr-total-badge">0 IKR</span>
                    </div>
                </div>

                <!-- SERVICE CARD -->
                <div class="ranking-card" id="srv-card">
                    <div class="rc-header">
                        <div class="rc-title-group">
                            <div class="rc-icon srv-icon">
                                <!-- Wrench/tools SVG -->
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z" stroke="#b45309" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <p class="rc-title">Ranking Service</p>
                                <p class="rc-subtitle">Jumlah servis per wilayah</p>
                            </div>
                        </div>
                    </div>
                    <div class="rc-breadcrumb" id="srv-breadcrumb">
                        <span class="rc-crumb active srv-crumb">Kabupaten</span>
                    </div>
                    <div class="rc-body">
                        <div id="srv-content"><div class="rc-loading"><div class="spinner srv-spin"></div> Memuat data...</div></div>
                    </div>
                    <div class="rc-summary" id="srv-summary" style="display:none">
                        <span id="srv-summary-text">— area ditemukan</span>
                        <span class="rc-total-badge srv-total" id="srv-total-badge">0 Service</span>
                    </div>
                </div>

            </div><!-- /ranking-grid -->

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const BASE = '<?= BASE_URL ?>api/ranking.php';

    // ── State ──────────────────────────────────────────────────
    let currentPeriod = 'month';
    let currentYear   = <?= $currentYear ?>;
    let currentMonth  = <?= $currentMonth ?>;
    let customFrom    = '';
    let customTo      = '';

    const state = {
        ikr:     { stack: [] },
        service: { stack: [] }
    };

    // ── Helpers ────────────────────────────────────────────────
    function buildParams(type) {
        const s     = state[type];
        const level = ['kab','kec','desa','perumahan'][s.stack.length] ?? 'kab';

        const params = new URLSearchParams({
            type, level,
            period: currentPeriod,
            year:   currentYear,
            month:  currentMonth,
            from:   customFrom,
            to:     customTo,
        });
        if (s.stack.length >= 1) params.set('kab',  s.stack[0]?.value ?? '');
        if (s.stack.length >= 2) params.set('kec',  s.stack[1]?.value ?? '');
        if (s.stack.length >= 3) params.set('desa', s.stack[2]?.value ?? '');
        return params;
    }

    function levelLabel(depth) {
        return ['Kabupaten','Kecamatan','Desa','Perumahan'][depth] ?? 'Kabupaten';
    }
    function badgeClass(i) {
        return ['gold','silver','bronze'][i] ?? 'other';
    }
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Render ─────────────────────────────────────────────────
    function renderList(type, rows, isLeaf) {
        const barClass   = type === 'ikr' ? 'ikr-bar' : 'srv-bar';
        const countClass = type === 'ikr' ? 'ikr-count' : 'srv-count';

        if (!rows || rows.length === 0) {
            return `<div class="rc-state">
                        <div class="state-icon">🗺️</div>
                        <p>Tidak ada data untuk periode ini.<br>Coba pilih periode lain atau perluas range tanggal.</p>
                    </div>`;
        }

        const items = rows.map((r, i) => {
            const leafClass  = isLeaf ? ' leaf' : '';
            const drillIcon  = isLeaf ? '' : `<div class="rank-drill-icon"></div>`;
            return `
            <li class="rank-item${leafClass}" data-value="${escHtml(r.label)}" data-total="${r.total}">
                <div class="rank-badge ${badgeClass(i)}">${i + 1}</div>
                <div class="rank-info">
                    <div class="rank-label" title="${escHtml(r.label)}">${escHtml(r.label)}</div>
                    <div class="rank-bar-wrap">
                        <div class="rank-bar ${barClass}" style="width:${r.pct}%"></div>
                    </div>
                </div>
                <span class="rank-count ${countClass}">${r.total.toLocaleString('id-ID')}</span>
                ${drillIcon}
            </li>`;
        }).join('');

        return `<ul class="rank-list">${items}</ul>`;
    }

    function renderBreadcrumb(type) {
        const s    = state[type];
        const wrap = document.getElementById(type === 'ikr' ? 'ikr-breadcrumb' : 'srv-breadcrumb');
        const cls  = type === 'service' ? 'rc-crumb srv-crumb' : 'rc-crumb';

        let html = `<span class="${cls}${s.stack.length === 0 ? ' active' : ''}" data-depth="-1">Kabupaten</span>`;
        s.stack.forEach((item, idx) => {
            html += `<span class="rc-sep">›</span>`;
            const isLast = idx === s.stack.length - 1;
            html += `<span class="${cls}${isLast ? ' active' : ''}" data-depth="${idx}">${escHtml(item.value)}</span>`;
        });
        wrap.innerHTML = html;

        wrap.querySelectorAll('[data-depth]').forEach(el => {
            el.addEventListener('click', () => {
                const depth = parseInt(el.dataset.depth);
                s.stack = depth === -1 ? [] : s.stack.slice(0, depth + 1);
                loadData(type);
            });
        });
    }

    // ── Load ───────────────────────────────────────────────────
    function loadData(type) {
        const s       = state[type];
        const isLeaf  = s.stack.length >= 3;
        const content = document.getElementById(type === 'ikr' ? 'ikr-content' : 'srv-content');
        const summary = document.getElementById(type === 'ikr' ? 'ikr-summary' : 'srv-summary');
        const sumText = document.getElementById(type === 'ikr' ? 'ikr-summary-text' : 'srv-summary-text');
        const sumBadge= document.getElementById(type === 'ikr' ? 'ikr-total-badge' : 'srv-total-badge');

        const spinClass = type === 'ikr' ? '' : ' srv-spin';
        content.innerHTML = `<div class="rc-loading"><div class="spinner${spinClass}"></div> Memuat data...</div>`;
        summary.style.display = 'none';
        renderBreadcrumb(type);

        fetch(`${BASE}?${buildParams(type)}`)
            .then(r => r.json())
            .then(json => {
                if (json.error) {
                    content.innerHTML = `<div class="rc-state"><div class="state-icon">⚠️</div><p>${escHtml(json.message)}</p></div>`;
                    return;
                }
                content.innerHTML = renderList(type, json.data, isLeaf);

                if (!isLeaf) {
                    content.querySelectorAll('.rank-item').forEach(item => {
                        item.addEventListener('click', () => {
                            s.stack.push({ level: levelLabel(s.stack.length), value: item.dataset.value });
                            loadData(type);
                        });
                    });
                }

                const total = (json.data || []).reduce((acc, r) => acc + r.total, 0);
                sumText.textContent  = `${(json.data||[]).length} area ditemukan`;
                sumBadge.textContent = `${total.toLocaleString('id-ID')} ${type === 'ikr' ? 'IKR' : 'Service'}`;
                summary.style.display = 'flex';
            })
            .catch(err => {
                content.innerHTML = `<div class="rc-state"><div class="state-icon">⚠️</div><p>Gagal memuat data.</p></div>`;
                console.error(err);
            });
    }

    function loadBoth() {
        state.ikr.stack = state.service.stack = [];
        loadData('ikr');
        loadData('service');
    }

    // ── UI visibility helper ───────────────────────────────────
    function updateFilterUI() {
        const yearCtrl  = document.getElementById('year-controls');
        const monthRow  = document.getElementById('month-row');
        const customRng = document.getElementById('custom-range');

        const isMonth  = currentPeriod === 'month';
        const isYear   = currentPeriod === 'year';
        const isCustom = currentPeriod === 'custom';

        // Year nav: show for month & year modes
        yearCtrl.classList.toggle('hidden', isCustom);
        // Month row: show only in month mode
        monthRow.classList.toggle('show',   isMonth);
        // Custom range
        customRng.classList.toggle('show',  isCustom);

        // Filter bar border-radius
        const filterBar = document.getElementById('filter-bar');
        if (isMonth) {
            filterBar.style.borderBottomLeftRadius  = '0';
            filterBar.style.borderBottomRightRadius = '0';
            filterBar.style.borderBottom = 'none';
        } else {
            filterBar.style.borderBottomLeftRadius  = '16px';
            filterBar.style.borderBottomRightRadius = '16px';
            filterBar.style.borderBottom = '';
        }
    }

    // ── Period tab clicks ──────────────────────────────────────
    document.querySelectorAll('.period-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPeriod = btn.dataset.period;
            updateFilterUI();
            if (currentPeriod !== 'custom') loadBoth();
        });
    });

    // ── Month pill clicks ──────────────────────────────────────
    document.querySelectorAll('.month-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.month-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentMonth = parseInt(pill.dataset.month);
            loadBoth();
        });
    });

    // ── Year controls ──────────────────────────────────────────
    document.getElementById('year-prev').addEventListener('click', () => {
        currentYear--;
        document.getElementById('year-display').textContent = currentYear;
        loadBoth();
    });
    document.getElementById('year-next').addEventListener('click', () => {
        currentYear++;
        document.getElementById('year-display').textContent = currentYear;
        loadBoth();
    });

    // ── Custom range ───────────────────────────────────────────
    document.getElementById('btn-apply-custom').addEventListener('click', () => {
        customFrom = document.getElementById('date-from').value;
        customTo   = document.getElementById('date-to').value;
        if (customFrom && customTo) loadBoth();
    });

    // ── Initial ────────────────────────────────────────────────
    updateFilterUI();
    loadBoth();

})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
