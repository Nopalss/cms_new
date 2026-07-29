<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$id = $_GET['id'] ?? null;

// Mode filter: 'periode' (tutup buku, tgl 26-25) atau 'range' (bebas dari-sampai)
$filter_mode  = $_GET['filter_mode'] ?? 'periode';
$filter_month = $_GET['filter_month'] ?? null;
$filter_year  = $_GET['filter_year'] ?? null;
$filter_start = $_GET['filter_start'] ?? null;
$filter_end   = $_GET['filter_end'] ?? null;

if (!$id) redirect("pages/dashboard.php");

$validRange = false;
if ($filter_mode === 'range' && $filter_start && $filter_end) {
    try {
        $rangeStart = new DateTime($filter_start);
        $rangeEnd   = new DateTime($filter_end);
        $validRange = true;
    } catch (Exception $e) {
        $validRange = false;
    }
}

if ($validRange) {
    // ── Mode: Range tanggal bebas ──
    if ($rangeStart > $rangeEnd) {
        $start = clone $rangeEnd;
        $end   = clone $rangeStart;
    } else {
        $start = $rangeStart;
        $end   = $rangeEnd;
    }
    $filter_mode = 'range';
} else {
    // ── Mode: Periode gajian (tutup buku tanggal 26 - 25) ──
    $filter_mode = 'periode';
    if ($filter_month && $filter_year) {
        $end   = new DateTime("$filter_year-$filter_month-25");
        $start = clone $end;
        $start->modify('-1 month');
        $start->setDate($start->format('Y'), $start->format('m'), 26);
    } else {
        $today = new DateTime();
        if ((int)$today->format('d') >= 26) {
            $start = new DateTime($today->format('Y-m-26'));
        } else {
            $start = new DateTime($today->format('Y-m-26'));
            $start->modify('-1 month');
        }
        $end = clone $start;
        $end->modify('+1 month');
        $end->modify('-1 day');
    }
}

$start_date = $start->format('Y-m-d');
$end_date   = $end->format('Y-m-d');

/* ============================================================
   BOBOT KRITERIA PENILAIAN KINERJA (internal, tidak ditampilkan ke user)
   C1 = Aktivitas (jumlah pekerjaan selesai)  -> BENEFIT
   C2 = Rating kepuasan pelanggan             -> BENEFIT
   C3 = Durasi penyelesaian pekerjaan         -> COST
   ============================================================ */
$W1 = 0.33; // Aktivitas
$W2 = 0.33; // Rating
$W3 = 0.34; // Durasi

function formatDurasi($detik)
{
    if ($detik === null || $detik < 0) return '—';
    if ($detik < 60) return round($detik) . 'dtk';
    $menit = $detik / 60;
    $jam   = floor($menit / 60);
    $sisa  = round($menit - ($jam * 60));
    return ($jam > 0 ? "{$jam}j " : '') . "{$sisa}m";
}

try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.avatar, tm.nama as tim_nama
        FROM technician t
        JOIN users u ON t.username = u.username
        LEFT JOIN tim tm ON t.tim_id = tm.tim_id
        WHERE t.tech_id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$technician) redirect("pages/dashboard.php");

    // NOTE:
    // - service_reports, dismantle_reports, ikr_report have NO `pic` column.
    //   PIC (technician assignment) lives in the junction tables:
    //   service_report_pic, dismantle_report_pic, ikr_report_pic.
    // - The report tables join to `schedules` via `schedule_id` (varchar),
    //   not `schedule_key`.
    // - The report tables join to `customers` via `netpay_id` (varchar),
    //   not `netpay_key`. `schedules` itself has no netpay_id/netpay_key at all.
    // - The instalasi table is actually named `ikr_report`, not `ikr`.
    $sql = "
        SELECT 'Service' as job_type, sr.srv_id as report_key, sr.srv_id as report_id,
               sr.tanggal,
               (SELECT GROUP_CONCAT(srp.tech_id)
                  FROM service_report_pic srp
                 WHERE srp.srv_id = sr.srv_id) as pic,
               s.status, c.name as customer_name,
               c.location, c.perumahan, c.netpay_id, c.is_active
        FROM service_reports sr
        JOIN schedules s ON sr.schedule_id = s.schedule_id
        JOIN customers c ON sr.netpay_id = c.netpay_id
        WHERE EXISTS (
                SELECT 1 FROM service_report_pic srp2
                 WHERE srp2.srv_id = sr.srv_id AND srp2.tech_id = :tech_id
              )
          AND sr.tanggal BETWEEN :start_date AND :end_date

        UNION ALL

        SELECT 'Dismantle' as job_type, dr.dismantle_id as report_key, dr.dismantle_id as report_id,
               dr.tanggal,
               (SELECT GROUP_CONCAT(drp.tech_id)
                  FROM dismantle_report_pic drp
                 WHERE drp.dismantle_id = dr.dismantle_id) as pic,
               s.status, c.name as customer_name,
               c.location, c.perumahan, c.netpay_id, c.is_active
        FROM dismantle_reports dr
        JOIN schedules s ON dr.schedule_id = s.schedule_id
        JOIN customers c ON dr.netpay_id = c.netpay_id
        WHERE EXISTS (
                SELECT 1 FROM dismantle_report_pic drp2
                 WHERE drp2.dismantle_id = dr.dismantle_id AND drp2.tech_id = :tech_id
              )
          AND dr.tanggal BETWEEN :start_date AND :end_date

        UNION ALL

        SELECT 'Instalasi' as job_type, i.ikr_id as report_key, i.ikr_id as report_id,
               s.date as tanggal,
               (SELECT GROUP_CONCAT(irp.tech_id)
                  FROM ikr_report_pic irp
                 WHERE irp.ikr_id = i.ikr_id) as pic,
               s.status, c.name as customer_name,
               c.location, c.perumahan, c.netpay_id, c.is_active
        FROM ikr_report i
        JOIN schedules s ON i.schedule_id = s.schedule_id
        JOIN customers c ON i.netpay_id = c.netpay_id
        WHERE EXISTS (
                SELECT 1 FROM ikr_report_pic irp2
                 WHERE irp2.ikr_id = i.ikr_id AND irp2.tech_id = :tech_id
              )
          AND s.date BETWEEN :start_date AND :end_date

        ORDER BY tanggal DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tech_id' => $id, ':start_date' => $start_date, ':end_date' => $end_date]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total           = count($reports);
    $total_service   = 0;
    $total_dismantle = 0;
    $total_instalasi = 0;
    foreach ($reports as $r) {
        if ($r['job_type'] == 'Service')   $total_service++;
        if ($r['job_type'] == 'Dismantle') $total_dismantle++;
        if ($r['job_type'] == 'Instalasi') $total_instalasi++;
    }

    /* ============================================================
       EVALUASI KINERJA
       Dihitung untuk SELURUH teknisi (dibutuhkan buat acuan
       perbandingan), lalu diambil hasil milik $id saja
       plus posisi rank-nya dibanding teknisi lain.
       ============================================================ */

    // 1. C1 (jumlah pekerjaan selesai) & C3 (rata-rata durasi, menit)
    //    dari schedules.start_time s/d end_time, per teknisi.
    $sqlActivityAll = "
        SELECT tech_id,
               COUNT(*) as jumlah_pekerjaan,
               AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as durasi_rata2
        FROM (
            SELECT srp.tech_id, s.start_time, s.end_time
            FROM service_reports sr
            JOIN service_report_pic srp ON sr.srv_id = srp.srv_id
            JOIN schedules s ON sr.schedule_id = s.schedule_id
            WHERE s.status = 'Done' AND s.date BETWEEN :start1 AND :end1

            UNION ALL

            SELECT drp.tech_id, s.start_time, s.end_time
            FROM dismantle_reports dr
            JOIN dismantle_report_pic drp ON dr.dismantle_id = drp.dismantle_id
            JOIN schedules s ON dr.schedule_id = s.schedule_id
            WHERE s.status = 'Done' AND s.date BETWEEN :start2 AND :end2

            UNION ALL

            SELECT irp.tech_id, s.start_time, s.end_time
            FROM ikr_report i
            JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
            JOIN schedules s ON i.schedule_id = s.schedule_id
            WHERE s.status = 'Done' AND s.date BETWEEN :start3 AND :end3
        ) x
        GROUP BY tech_id
    ";
    $stmt = $pdo->prepare($sqlActivityAll);
    $stmt->execute([
        ':start1' => $start_date,
        ':end1' => $end_date,
        ':start2' => $start_date,
        ':end2' => $end_date,
        ':start3' => $start_date,
        ':end3' => $end_date,
    ]);
    $activityMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activityMap[$row['tech_id']] = [
            'jumlah_pekerjaan' => (int)$row['jumlah_pekerjaan'],
            'durasi_rata2'     => $row['durasi_rata2'] !== null ? (float)$row['durasi_rata2'] : null,
        ];
    }

    // 2. C2 (rating rata-rata per teknisi)
    //    detail_ratings -> technician_ratings -> schedules (relasi via rating_id & schedule_id)
    $sqlRatingAll = "
        SELECT dr.tech_id,
               AVG(tr.rating) as rating_rata2,
               COUNT(tr.rating) as jumlah_rating
        FROM detail_ratings dr
        JOIN technician_ratings tr ON dr.rating_id = tr.rating_id
        JOIN schedules s ON tr.schedule_id = s.schedule_id
        WHERE tr.rating IS NOT NULL
          AND tr.status = 'Rated'
          AND s.status = 'Done'
          AND s.date BETWEEN :start AND :end
        GROUP BY dr.tech_id
    ";
    $stmt = $pdo->prepare($sqlRatingAll);
    $stmt->execute([':start' => $start_date, ':end' => $end_date]);
    $ratingMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ratingMap[$row['tech_id']] = [
            'rating_rata2'  => (float)$row['rating_rata2'],
            'jumlah_rating' => (int)$row['jumlah_rating'],
        ];
    }

    // 3. Fetch nama teknisi untuk keperluan sorting tiebreaker
    $allTids = array_keys($activityMap);
    $nameMap = [];
    if (!empty($allTids)) {
        $placeholders = implode(',', array_fill(0, count($allTids), '?'));
        $stmtNames = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tech_id IN ($placeholders)");
        $stmtNames->execute($allTids);
        foreach ($stmtNames->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $nameMap[$r['tech_id']] = $r['name'];
        }
    }

    // 4. Susun matriks keputusan untuk semua teknisi yang punya
    //    minimal 1 pekerjaan selesai pada periode ini.
    $sawMatrix = [];
    foreach ($activityMap as $tid => $act) {
        if ($act['jumlah_pekerjaan'] <= 0) continue;

        $sawMatrix[$tid] = [
            'tech_id'    => $tid,
            'name'       => $nameMap[$tid] ?? $tid,
            'c1'         => $act['jumlah_pekerjaan'],
            'c2'         => $ratingMap[$tid]['rating_rata2'] ?? null,
            'c2_count'   => $ratingMap[$tid]['jumlah_rating'] ?? 0,
            'c3'         => $act['durasi_rata2'],
            'c2_missing' => !isset($ratingMap[$tid]),
            'c3_missing' => $act['durasi_rata2'] === null,
        ];
    }

    // 4. Tangani data hilang:
    //    - C2 kosong (belum ada rating) -> nilai 0 (konservatif)
    //    - C3 kosong (waktu belum tercatat) -> 2x durasi terlama sebagai penalti
    $maxDurasiTercatat = 0;
    foreach ($sawMatrix as $row) {
        if ($row['c3'] !== null && $row['c3'] > $maxDurasiTercatat) {
            $maxDurasiTercatat = $row['c3'];
        }
    }
    if ($maxDurasiTercatat <= 0) $maxDurasiTercatat = 3600; // fallback 1 jam dalam detik
    foreach ($sawMatrix as $tid => $row) {
        if ($sawMatrix[$tid]['c2'] === null) $sawMatrix[$tid]['c2'] = 0;
        // Penalti 2x durasi terlama untuk teknisi tanpa data waktu
        if ($sawMatrix[$tid]['c3'] === null) $sawMatrix[$tid]['c3'] = $maxDurasiTercatat * 2;
    }

    // 5. Normalisasi (internal)
    //    Benefit (C1, C2): r = x / max(x)   |   Cost (C3): r = min(x) / x
    $maxC1 = 0;
    $maxC2 = 0;
    $minC3 = null;
    foreach ($sawMatrix as $row) {
        if ($row['c1'] > $maxC1) $maxC1 = $row['c1'];
        if ($row['c2'] > $maxC2) $maxC2 = $row['c2'];
        if ($row['c3'] > 0 && ($minC3 === null || $row['c3'] < $minC3)) $minC3 = $row['c3'];
    }
    if ($minC3 === null || $minC3 <= 0) $minC3 = 1;

    foreach ($sawMatrix as $tid => $row) {
        $r1 = $maxC1 > 0 ? $row['c1'] / $maxC1 : 0;
        $r2 = $maxC2 > 0 ? $row['c2'] / $maxC2 : 0;
        $r3 = $row['c3'] > 0 ? $minC3 / $row['c3'] : 1;

        $sawMatrix[$tid]['r1']   = $r1;
        $sawMatrix[$tid]['r2']   = $r2;
        $sawMatrix[$tid]['r3']   = $r3;
        $sawMatrix[$tid]['skor'] = ($W1 * $r1) + ($W2 * $r2) + ($W3 * $r3);
    }

    // 7. Ranking (skor tertinggi = terbaik) + posisi teknisi ini
    $sawRanking = array_values($sawMatrix);
    usort($sawRanking, function($a, $b) {
        // Tiebreaker: sort by name (SAMA dengan api/teknisi.php)
        if (abs($a['skor'] - $b['skor']) < 0.000001) {
            return strcmp($a['name'], $b['name']);
        }
        return $b['skor'] <=> $a['skor'];
    });

    $totalEvaluasi = count($sawRanking);
    $myRank = null;
    $mySaw  = null;
    foreach ($sawRanking as $i => $row) {
        if ($row['tech_id'] == $id) {
            $myRank = $i + 1;
            $mySaw  = $row;
            break;
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<style>
    :root {
        --primary: #3B82F6;
        --primary-dark: #1D4ED8;
        --navy: #0F172A;
        --slate: #64748B;
        --bg: #F1F5F9;
        --card-radius: 14px;
    }

    #kt_content {
        background: var(--bg);
    }

    .page-heading {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--navy);
        letter-spacing: -.3px;
    }

    .page-sub {
        font-size: .82rem;
        color: var(--slate);
    }

    /* ── Cards ── */
    .rs-card {
        border: none;
        border-radius: var(--card-radius);
        box-shadow: 0 1px 4px rgba(15, 23, 42, .07), 0 4px 16px rgba(15, 23, 42, .05);
        background: #fff;
    }

    .rs-card .card-header {
        background: none;
        border-bottom: 1px solid #E2E8F0;
        padding: 1.1rem 1.5rem;
        border-radius: var(--card-radius) var(--card-radius) 0 0;
        display: flex;
        align-items: center;
    }

    .rs-card .card-header .card-title {
        font-size: .9rem;
        font-weight: 700;
        color: var(--navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .rs-card .card-header .card-title i {
        color: var(--primary);
    }

    .rs-card .card-body {
        padding: 1.5rem;
    }

    /* ── Profile card ── */
    .profile-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #E2E8F0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
    }

    .profile-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--navy);
        margin-bottom: .15rem;
    }

    .profile-id {
        font-size: .8rem;
        color: var(--slate);
        font-weight: 500;
    }

    .role-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: #EFF6FF;
        color: var(--primary-dark);
        border-radius: 99px;
        padding: .2rem .75rem;
        font-size: .72rem;
        font-weight: 700;
        margin-top: .35rem;
    }

    .period-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: .75rem 1.1rem;
        font-size: .8rem;
        color: var(--slate);
        line-height: 1.7;
    }

    .period-box i {
        color: var(--primary);
        margin-right: .3rem;
    }

    /* ── Stat cards ── */
    .stat-card {
        border-radius: var(--card-radius);
        border: none;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .07), 0 4px 16px rgba(15, 23, 42, .05);
        overflow: hidden;
        position: relative;
    }

    .stat-card .stat-accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 4px 0 0 4px;
    }

    .stat-card .card-body {
        padding: 1.1rem 1.25rem 1.1rem 1.5rem;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .25rem;
    }

    .stat-label {
        font-size: .75rem;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    /* ── Filter form ── */
    .rs-label {
        font-size: .75rem;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: .3rem;
    }

    .rs-select {
        height: 42px;
        border-radius: 8px !important;
        border-color: #CBD5E1;
        font-size: .875rem;
        color: var(--navy);
    }

    .rs-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
    }

    .btn-rs-primary {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .55rem 1.25rem;
        font-size: .875rem;
        font-weight: 600;
        transition: background .18s;
        cursor: pointer;
        height: 42px;
    }

    .btn-rs-primary:hover {
        background: var(--primary-dark);
        color: #fff;
    }

    .btn-rs-reset {
        background: #F1F5F9;
        color: var(--slate);
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: .55rem 1.1rem;
        font-size: .875rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        height: 42px;
        transition: background .18s;
    }

    .btn-rs-reset:hover {
        background: #E2E8F0;
        color: var(--navy);
        text-decoration: none;
    }

    .btn-rs-export {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
        border-radius: 8px;
        padding: .45rem 1rem;
        font-size: .8rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        transition: background .18s;
    }

    .btn-rs-export:hover {
        background: #A7F3D0;
        color: #065F46;
        text-decoration: none;
    }

    .btn-rs-back {
        background: #F1F5F9;
        color: var(--slate);
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: .45rem 1rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        transition: background .18s;
    }

    .btn-rs-back:hover {
        background: #E2E8F0;
        color: var(--navy);
        text-decoration: none;
    }

    /* ── Filter mode tabs ── */
    .filter-mode-tabs {
        display: flex;
        gap: .6rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .filter-mode-tab {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .55rem 1.1rem;
        border-radius: 8px;
        border: 1px solid #E2E8F0;
        background: #F8FAFC;
        font-size: .82rem;
        font-weight: 600;
        color: var(--slate);
        cursor: pointer;
        transition: all .15s;
        user-select: none;
    }

    .filter-mode-tab input {
        display: none;
    }

    .filter-mode-tab.active {
        background: #EFF6FF;
        border-color: #BFDBFE;
        color: var(--primary-dark);
    }

    .filter-mode-tab i {
        font-size: .8rem;
    }

    /* ── Table ── */
    .rs-table {
        width: 100%;
        border-collapse: collapse;
    }

    .rs-table thead tr {
        background: #F8FAFC;
        border-bottom: 2px solid #E2E8F0;
    }

    .rs-table thead th {
        font-size: .7rem;
        font-weight: 700;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: .85rem 1rem;
        white-space: nowrap;
    }

    .rs-table thead th:first-child {
        padding-left: 1.5rem;
        border-radius: var(--card-radius) 0 0 0;
    }

    .rs-table thead th:last-child {
        padding-right: 1.5rem;
    }

    .rs-table tbody tr {
        border-bottom: 1px solid #F1F5F9;
        transition: background .12s;
    }

    .rs-table tbody tr:last-child {
        border-bottom: none;
    }

    .rs-table tbody tr:hover {
        background: #F8FAFC;
    }

    .rs-table tbody td {
        padding: .85rem 1rem;
        font-size: .825rem;
        color: var(--navy);
        vertical-align: middle;
    }

    .rs-table tbody td:first-child {
        padding-left: 1.5rem;
    }

    .rs-table tbody td:last-child {
        padding-right: 1.5rem;
    }

    /* ── Type badges ── */
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .7rem;
        border-radius: 99px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .type-service {
        background: #EFF6FF;
        color: #1D4ED8;
    }

    .type-dismantle {
        background: #FEE2E2;
        color: #991B1B;
    }

    .type-instalasi {
        background: #D1FAE5;
        color: #065F46;
    }

    .type-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: .22rem .65rem;
        border-radius: 99px;
        font-size: .72rem;
        font-weight: 700;
        background: #D1FAE5;
        color: #065F46;
    }

    .report-link {
        color: var(--primary);
        font-weight: 700;
        font-size: .82rem;
        text-decoration: none;
    }

    .report-link:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .cell-muted {
        color: var(--slate);
        font-size: .8rem;
    }

    .cell-wrap {
        max-width: 160px;
        white-space: normal;
        line-height: 1.4;
    }

    /* ── Empty state ── */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--slate);
    }

    .empty-state i {
        font-size: 2.5rem;
        color: #CBD5E1;
        display: block;
        margin-bottom: .75rem;
    }

    .empty-state p {
        font-size: .875rem;
        margin: 0;
    }

    /* ── Period range badge in table header ── */
    .period-range {
        font-size: .75rem;
        color: var(--slate);
        font-weight: 500;
        background: #F1F5F9;
        border-radius: 6px;
        padding: .2rem .65rem;
        display: inline-block;
        margin-left: .5rem;
    }

    .count-pill {
        background: #F1F5F9;
        color: var(--slate);
        border-radius: 99px;
        padding: .2rem .75rem;
        font-size: .75rem;
        font-weight: 700;
    }

    /* ── Kartu Evaluasi Kinerja ── */
    .saw-score-wrap {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .saw-score-badge {
        text-align: center;
        background: linear-gradient(145deg, #EFF6FF, #DBEAFE);
        border: 1px solid #BFDBFE;
        border-radius: 16px;
        padding: 1.1rem 1.8rem;
        min-width: 170px;
    }

    .saw-score-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--primary-dark);
        line-height: 1;
    }

    .saw-score-label {
        font-size: .72rem;
        color: var(--slate);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-top: .35rem;
    }

    .skor-pill {
        display: inline-block;
        margin-top: .55rem;
        padding: .18rem .65rem;
        border-radius: 99px;
        font-size: .7rem;
        font-weight: 700;
    }

    .saw-rank-badge {
        text-align: center;
        background: #FEF3C7;
        border: 1px solid #FDE68A;
        border-radius: 16px;
        padding: 1.1rem 1.8rem;
        min-width: 170px;
    }

    .saw-rank-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: #92400E;
        line-height: 1;
    }

    .saw-rank-label {
        font-size: .72rem;
        color: #92400E;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-top: .35rem;
    }

    .crit-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: .75rem;
        margin-top: 1.25rem;
    }

    .crit-card {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: .9rem 1.1rem;
    }

    .crit-card .crit-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .4rem;
    }

    .crit-card .crit-name {
        font-size: .78rem;
        font-weight: 700;
        color: var(--navy);
    }

    .crit-raw {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--navy);
    }

    .crit-sub {
        font-size: .72rem;
        color: var(--slate);
        margin-top: .3rem;
        line-height: 1.5;
    }

    .crit-bar-wrap {
        width: 100%;
        height: 6px;
        background: #E2E8F0;
        border-radius: 99px;
        overflow: hidden;
        margin-top: .55rem;
    }

    .crit-bar-fill {
        height: 100%;
        background: var(--primary);
        border-radius: 99px;
    }

    .missing-flag {
        color: #B45309;
        font-size: .72rem;
        font-weight: 600;
        margin-top: .3rem;
    }

    .saw-empty {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        color: var(--slate);
        font-size: .85rem;
    }

    @media (max-width: 767px) {
        .rs-card .card-body {
            padding: 1.1rem;
        }

        .stat-number {
            font-size: 1.4rem;
        }
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid py-8">
        <div class="container">

            <!-- Page Header -->
            <div class="mb-6 d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <div class="page-heading">Laporan Kerja Teknisi</div>
                    <div class="page-sub mt-1">
                        <a href="<?= BASE_URL ?>pages/technician/" style="color:var(--primary); text-decoration:none; font-weight:600;">Teknisi</a>
                        <span class="mx-1">›</span>
                        <?= htmlspecialchars($technician['name']) ?>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>pages/technician/" class="btn-rs-back mt-2 mt-md-0">
                    <i class="flaticon2-left-arrow-1" style="font-size:.75rem;"></i> Kembali
                </a>
            </div>

            <!-- ── Profile Card ── -->
            <div class="rs-card mb-5">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:1rem;">

                        <div class="d-flex align-items-center" style="gap:1rem;">
                            <img src="<?= BASE_URL ?>assets/media/users/<?= htmlspecialchars($technician['avatar']) ?>"
                                class="profile-avatar" alt="avatar">
                            <div>
                                <div class="profile-name"><?= htmlspecialchars($technician['name']) ?></div>
                                <div class="profile-id"><?= htmlspecialchars($technician['tech_id']) ?></div>
                                <div class="role-pill"><i class="flaticon2-user" style="font-size:.75rem;"></i> Teknisi<?= $technician['tim_nama'] ? ' · ' . htmlspecialchars($technician['tim_nama']) : '' ?></div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center flex-wrap" style="gap:.75rem;">
                            <!-- Total Job highlight -->
                            <div style="text-align:center; background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; padding:.75rem 1.25rem; min-width:90px;">
                                <div style="font-size:1.6rem; font-weight:800; color:var(--primary); line-height:1;"><?= $total ?></div>
                                <div style="font-size:.72rem; color:var(--slate); font-weight:600; text-transform:uppercase; letter-spacing:.4px; margin-top:.2rem;">Total Job</div>
                            </div>
                            <!-- Period -->
                            <div class="period-box">
                                <div><i class="flaticon2-calendar-3"></i> Mulai: <strong style="color:var(--navy);"><?= date('d M Y', strtotime($start_date)) ?></strong></div>
                                <div><i class="flaticon2-calendar-3"></i> Sampai: <strong style="color:var(--navy);"><?= date('d M Y', strtotime($end_date)) ?></strong></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="row mb-5">
                <!-- Total -->
                <div class="col-6 col-md-3 mb-4">
                    <div class="stat-card bg-white">
                        <div class="stat-accent" style="background:#475569;"></div>
                        <div class="card-body">
                            <div class="stat-number" style="color:#475569;"><?= $total ?></div>
                            <div class="stat-label">Total Pekerjaan</div>
                        </div>
                    </div>
                </div>
                <!-- Service -->
                <div class="col-6 col-md-3 mb-4">
                    <div class="stat-card bg-white">
                        <div class="stat-accent" style="background:#3B82F6;"></div>
                        <div class="card-body">
                            <div class="stat-number" style="color:#3B82F6;"><?= $total_service ?></div>
                            <div class="stat-label">Service</div>
                        </div>
                    </div>
                </div>
                <!-- Dismantle -->
                <div class="col-6 col-md-3 mb-4">
                    <div class="stat-card bg-white">
                        <div class="stat-accent" style="background:#EF4444;"></div>
                        <div class="card-body">
                            <div class="stat-number" style="color:#EF4444;"><?= $total_dismantle ?></div>
                            <div class="stat-label">Dismantle</div>
                        </div>
                    </div>
                </div>
                <!-- Instalasi -->
                <div class="col-6 col-md-3 mb-4">
                    <div class="stat-card bg-white">
                        <div class="stat-accent" style="background:#10B981;"></div>
                        <div class="card-body">
                            <div class="stat-number" style="color:#10B981;"><?= $total_instalasi ?></div>
                            <div class="stat-label">Instalasi</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Evaluasi Kinerja ── -->
            <div class="rs-card mb-5">
                <div class="card-header">
                    <div class="card-title">
                        <i class="flaticon2-graphic"></i> Evaluasi Kinerja
                        <span class="period-range"><?= date('d M Y', strtotime($start_date)) ?> — <?= date('d M Y', strtotime($end_date)) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($mySaw):
                        $skorPersen = round($mySaw['skor'] * 100);
                        if ($mySaw['skor'] >= 0.8) {
                            $skorLabel  = 'Sangat Baik';
                            $skorBg     = '#D1FAE5';
                            $skorFg     = '#065F46';
                            $skorBorder = '#A7F3D0';
                        } elseif ($mySaw['skor'] >= 0.65) {
                            $skorLabel  = 'Baik';
                            $skorBg     = '#DBEAFE';
                            $skorFg     = '#1D4ED8';
                            $skorBorder = '#BFDBFE';
                        } elseif ($mySaw['skor'] >= 0.45) {
                            $skorLabel  = 'Cukup';
                            $skorBg     = '#FEF3C7';
                            $skorFg     = '#92400E';
                            $skorBorder = '#FDE68A';
                        } else {
                            $skorLabel  = 'Perlu Ditingkatkan';
                            $skorBg     = '#FEE2E2';
                            $skorFg     = '#991B1B';
                            $skorBorder = '#FECACA';
                        }
                    ?>
                        <div class="saw-score-wrap">
                            <div class="saw-score-badge">
                                <div class="saw-score-value"><?= number_format($skorPersen, 0) ?>%</div>
                                <div class="saw-score-label">Skor Performa</div>
                                <div class="skor-pill" style="background:<?= $skorBg ?>; color:<?= $skorFg ?>; border:1px solid <?= $skorBorder ?>;"><?= $skorLabel ?></div>
                            </div>
                            <div class="saw-rank-badge">
                                <div class="saw-rank-value">#<?= $myRank ?></div>
                                <div class="saw-rank-label">dari <?= $totalEvaluasi ?> teknisi yang dievaluasi</div>
                            </div>
                            <div style="flex:1; min-width:220px; font-size:.8rem; color:var(--slate); line-height:1.6;">
                                Skor performa dihitung dari gabungan <strong>jumlah pekerjaan yang diselesaikan</strong>,
                                <strong>rating kepuasan pelanggan</strong>, dan <strong>kecepatan penyelesaian pekerjaan</strong>,
                                dibandingkan dengan teknisi lain yang punya aktivitas pada periode yang sama.
                            </div>
                        </div>

                        <div class="crit-grid">
                            <!-- Jumlah Pekerjaan -->
                            <div class="crit-card">
                                <div class="crit-head">
                                    <span class="crit-name">Jumlah Pekerjaan Selesai</span>
                                </div>
                                <div class="crit-raw"><?= $mySaw['c1'] ?> job</div>
                                <div class="crit-sub">Setara <?= round($mySaw['r1'] * 100) ?>% dari teknisi dengan pekerjaan terbanyak (<?= $maxC1 ?> job)</div>
                                <div class="crit-bar-wrap">
                                    <div class="crit-bar-fill" style="width:<?= round($mySaw['r1'] * 100) ?>%;"></div>
                                </div>
                            </div>
                            <!-- Rating -->
                            <div class="crit-card">
                                <div class="crit-head">
                                    <span class="crit-name">Rating Kepuasan Pelanggan</span>
                                </div>
                                <div class="crit-raw"><?= number_format($mySaw['c2'], 2) ?> / 5</div>
                                <?php if ($mySaw['c2_missing']): ?>
                                    <div class="missing-flag">Belum ada rating masuk pada periode ini</div>
                                <?php else: ?>
                                    <div class="crit-sub">Setara <?= round($mySaw['r2'] * 100) ?>% dari rating tertinggi (<?= number_format($maxC2, 2) ?>/5), dari <?= $mySaw['c2_count'] ?> rating</div>
                                <?php endif; ?>
                                <div class="crit-bar-wrap">
                                    <div class="crit-bar-fill" style="width:<?= round($mySaw['r2'] * 100) ?>%;"></div>
                                </div>
                            </div>
                            <!-- Kecepatan -->
                            <div class="crit-card">
                                <div class="crit-head">
                                    <span class="crit-name">Kecepatan Pengerjaan</span>
                                </div>
                                <div class="crit-raw"><?= $mySaw['c3_missing'] ? '—' : formatDurasi($mySaw['c3']) ?> / job</div>
                                <?php if ($mySaw['c3_missing']): ?>
                                    <div class="missing-flag">Data waktu mulai/selesai tidak lengkap</div>
                                <?php else: ?>
                                    <div class="crit-sub">Setara <?= round($mySaw['r3'] * 100) ?>% dibanding teknisi tercepat (<?= formatDurasi($minC3) ?>)</div>
                                <?php endif; ?>
                                <div class="crit-bar-wrap">
                                    <div class="crit-bar-fill" style="width:<?= round($mySaw['r3'] * 100) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="saw-empty">
                            Belum ada pekerjaan berstatus <strong>Done</strong> untuk teknisi ini pada periode
                            <?= date('d M Y', strtotime($start_date)) ?> — <?= date('d M Y', strtotime($end_date)) ?>,
                            sehingga skor performa belum bisa dihitung.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Filter Card ── -->
            <div class="rs-card mb-5">
                <div class="card-header">
                    <div class="card-title">
                        <i class="flaticon2-search-1"></i> Filter Periode
                    </div>
                    <div class="ml-auto">
                        <a href="export_excel.php?id=<?= $id ?>&start=<?= $start_date ?>&end=<?= $end_date ?>"
                            class="btn-rs-export">
                            <i class="flaticon2-sheet" style="font-size:.85rem;"></i> Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                        <!-- Tab pilihan mode filter -->
                        <div class="filter-mode-tabs">
                            <label class="filter-mode-tab <?= $filter_mode === 'periode' ? 'active' : '' ?>" id="tab-periode">
                                <input type="radio" name="filter_mode" value="periode"
                                    <?= $filter_mode === 'periode' ? 'checked' : '' ?>
                                    onchange="switchFilterMode('periode')">
                                <i class="flaticon2-calendar-3"></i> Per Periode Gajian
                            </label>
                            <label class="filter-mode-tab <?= $filter_mode === 'range' ? 'active' : '' ?>" id="tab-range">
                                <input type="radio" name="filter_mode" value="range"
                                    <?= $filter_mode === 'range' ? 'checked' : '' ?>
                                    onchange="switchFilterMode('range')">
                                <i class="flaticon2-calendar-3"></i> Per Range Tanggal
                            </label>
                        </div>

                        <!-- Field: Per Periode Gajian (tutup buku tgl 26 - 25) -->
                        <div class="row align-items-end" id="periode-fields"
                            style="<?= $filter_mode === 'range' ? 'display:none;' : '' ?>">
                            <div class="col-sm-4 mb-3 mb-sm-0">
                                <label class="rs-label">Bulan</label>
                                <select name="filter_month" class="form-control rs-select">
                                    <option value="">— Semua Bulan —</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= ($filter_month == $m ? 'selected' : '') ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-sm-3 mb-3 mb-sm-0">
                                <label class="rs-label">Tahun</label>
                                <select name="filter_year" class="form-control rs-select">
                                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($filter_year == $y ? 'selected' : '') ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Field: Per Range Tanggal Bebas -->
                        <div class="row align-items-end" id="range-fields"
                            style="<?= $filter_mode === 'periode' ? 'display:none;' : '' ?>">
                            <div class="col-sm-4 mb-3 mb-sm-0">
                                <label class="rs-label">Dari Tanggal</label>
                                <input type="date" name="filter_start" class="form-control rs-select"
                                    value="<?= htmlspecialchars($filter_start ?? $start_date) ?>">
                            </div>
                            <div class="col-sm-4 mb-3 mb-sm-0">
                                <label class="rs-label">Sampai Tanggal</label>
                                <input type="date" name="filter_end" class="form-control rs-select"
                                    value="<?= htmlspecialchars($filter_end ?? $end_date) ?>">
                            </div>
                        </div>

                        <div class="row align-items-end mt-3">
                            <div class="col-sm-auto mb-3 mb-sm-0">
                                <button type="submit" class="btn-rs-primary">
                                    <i class="flaticon2-search-1 mr-1" style="font-size:.8rem;"></i> Filter
                                </button>
                            </div>
                            <div class="col-sm-auto">
                                <a href="?id=<?= htmlspecialchars($id) ?>" class="btn-rs-reset">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Table Card ── -->
            <div class="rs-card mb-8">
                <div class="card-header">
                    <div class="card-title">
                        <i class="flaticon2-list-2"></i> Riwayat Pekerjaan
                        <span class="period-range"><?= date('d M Y', strtotime($start_date)) ?> — <?= date('d M Y', strtotime($end_date)) ?></span>
                    </div>
                    <div class="ml-auto">
                        <span class="count-pill"><?= $total ?> laporan</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="rs-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Report ID</th>
                                    <th>Netpay ID</th>
                                    <th>Customer</th>
                                    <th>Perumahan</th>
                                    <th>Alamat</th>
                                    <th>PIC</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total > 0): ?>
                                    <?php foreach ($reports as $r):
                                        // Resolve PIC names
                                        $picDisplay = '—';
                                        if (!empty($r['pic'])) {
                                            $picIds    = explode(',', $r['pic']);
                                            $in        = implode(',', array_fill(0, count($picIds), '?'));
                                            $q         = $pdo->prepare("SELECT name FROM technician WHERE tech_id IN ($in)");
                                            $q->execute($picIds);
                                            $names     = $q->fetchAll(PDO::FETCH_COLUMN);
                                            $picDisplay = $names ? implode(', ', $names) : '—';
                                        }

                                        $detailPage = $r['job_type'] == 'Service'
                                            ? 'service_report'
                                            : ($r['job_type'] == 'Dismantle' ? 'dismantle' : 'ikr');

                                        $typeCls = $r['job_type'] == 'Service'
                                            ? 'type-service'
                                            : ($r['job_type'] == 'Dismantle' ? 'type-dismantle' : 'type-instalasi');
                                    ?>
                                        <tr>
                                            <td>
                                                <span style="font-weight:600; color:var(--navy); font-size:.82rem;">
                                                    <?= (new DateTime($r['tanggal']))->format('d M Y') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="type-badge <?= $typeCls ?>">
                                                    <span class="type-dot"></span>
                                                    <?= htmlspecialchars($r['job_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>pages/<?= $detailPage ?>/detail.php?id=<?= $r['report_key'] ?>"
                                                    class="report-link">
                                                    <?= htmlspecialchars($r['report_id']) ?>
                                                </a>
                                            </td>
                                            <td class="cell-muted"><?= htmlspecialchars($r['netpay_id']) ?></td>
                                            <td style="font-weight:600; font-size:.82rem;"><?= htmlspecialchars($r['customer_name']) ?></td>
                                            <td class="cell-muted"><?= htmlspecialchars($r['perumahan']) ?></td>
                                            <td class="cell-muted cell-wrap"><?= htmlspecialchars($r['location']) ?></td>
                                            <td class="cell-muted"><?= htmlspecialchars($picDisplay) ?></td>
                                            <td>
                                                <span class="status-badge"><?= htmlspecialchars($r['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9">
                                            <div class="empty-state">
                                                <i class="flaticon2-search-1"></i>
                                                <p>Tidak ada laporan pada periode ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function switchFilterMode(mode) {
        document.getElementById('periode-fields').style.display = mode === 'periode' ? '' : 'none';
        document.getElementById('range-fields').style.display = mode === 'range' ? '' : 'none';
        document.getElementById('tab-periode').classList.toggle('active', mode === 'periode');
        document.getElementById('tab-range').classList.toggle('active', mode === 'range');
    }
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>