<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $search = $_POST['query']['generalSearch'] ?? $_GET['query']['generalSearch'] ?? '';

    // ---------------- Decode filter state ----------------
    $filterStateRaw = $_REQUEST['query']['filterState'] ?? '';
    if ($filterStateRaw) {
        $fs         = json_decode($filterStateRaw, true) ?: [];
        $period     = $fs['period']  ?? 'month';
        $customFrom = $fs['from']    ?? '';
        $customTo   = $fs['to']      ?? '';
    } else {
        $period     = $_REQUEST['query']['period'] ?? 'month';
        $customFrom = $_REQUEST['query']['from']   ?? '';
        $customTo   = $_REQUEST['query']['to']     ?? '';
    }

    $today = new DateTime('now');

    switch ($period) {
        case 'today':
            $start_date = $today->format('Y-m-d');
            $end_date   = $today->format('Y-m-d');
            break;
        case 'week':
            $start_date = (clone $today)->modify('monday this week')->format('Y-m-d');
            $end_date   = (clone $today)->modify('sunday this week')->format('Y-m-d');
            break;
        case 'month':
        default:
            // Calculate dates for current monthly cycle (26th of last month to 25th of current month)
            if ((int)$today->format('d') >= 26) {
                $start = new DateTime($today->format('Y-m-26'));
            } else {
                $start = new DateTime($today->format('Y-m-26'));
                $start->modify('-1 month');
            }
            $end = clone $start;
            $end->modify('+1 month');
            $end->modify('-1 day');
            $start_date = $start->format('Y-m-d');
            $end_date   = $end->format('Y-m-d');
            break;
        case 'custom':
            $fromObj = DateTime::createFromFormat('Y-m-d', $customFrom);
            $toObj   = DateTime::createFromFormat('Y-m-d', $customTo);
            $start_date = $fromObj ? $fromObj->format('Y-m-d') : $today->format('Y-m-d');
            $end_date   = $toObj   ? $toObj->format('Y-m-d')   : $today->format('Y-m-d');
            break;
    }

    if ($start_date > $end_date) { 
        [$start_date, $end_date] = [$end_date, $start_date]; 
    }

    // 1. Fetch all technicians
    $sqlTech = "SELECT * FROM technician WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sqlTech .= " AND (tech_id LIKE :search OR name LIKE :search OR phone LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $stmtTech = $pdo->prepare($sqlTech);
    $stmtTech->execute($params);
    $technicians = $stmtTech->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch C1 & C3 (Activity and duration) via union of PIC tables
    $sqlActivity = "
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
    $stmtActivity = $pdo->prepare($sqlActivity);
    $stmtActivity->execute([
        ':start1' => $start_date, ':end1' => $end_date,
        ':start2' => $start_date, ':end2' => $end_date,
        ':start3' => $start_date, ':end3' => $end_date,
    ]);
    $activityMap = [];
    foreach ($stmtActivity->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activityMap[$row['tech_id']] = [
            'jumlah_pekerjaan' => (int)$row['jumlah_pekerjaan'],
            'durasi_rata2'     => $row['durasi_rata2'] !== null ? (float)$row['durasi_rata2'] : null,
        ];
    }

    // 3. Fetch C2 (Ratings) via detail_ratings
    $sqlRating = "
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
    $stmtRating = $pdo->prepare($sqlRating);
    $stmtRating->execute([':start' => $start_date, ':end' => $end_date]);
    $ratingMap = [];
    foreach ($stmtRating->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ratingMap[$row['tech_id']] = [
            'rating_rata2'  => (float)$row['rating_rata2'],
            'jumlah_rating' => (int)$row['jumlah_rating'],
        ];
    }

    // SAW Weights
    $W1 = 0.33; // Aktivitas
    $W2 = 0.33; // Rating
    $W3 = 0.34; // Durasi

    // 4. Build Decision Matrix
    $sawMatrix = [];
    foreach ($technicians as $tech) {
        $tid = $tech['tech_id'];
        $act = $activityMap[$tid] ?? ['jumlah_pekerjaan' => 0, 'durasi_rata2' => null];
        $rat = $ratingMap[$tid] ?? ['rating_rata2' => null, 'jumlah_rating' => 0];

        $sawMatrix[$tid] = [
            'tech_id'    => $tid,
            'c1'         => $act['jumlah_pekerjaan'],
            'c2'         => $rat['rating_rata2'],
            'c3'         => $act['durasi_rata2'],
        ];
    }

    // 5. Handle missing values for C3 (duration) & C2 (rating)
    $maxDurasiTercatat = 0;
    foreach ($sawMatrix as $row) {
        if ($row['c3'] !== null && $row['c3'] > $maxDurasiTercatat) {
            $maxDurasiTercatat = $row['c3'];
        }
    }
    if ($maxDurasiTercatat <= 0) $maxDurasiTercatat = 3600; // fallback 1 jam dalam detik

    foreach ($sawMatrix as $tid => $row) {
        if ($sawMatrix[$tid]['c2'] === null) $sawMatrix[$tid]['c2'] = 0;
        // Teknisi tanpa durasi mendapat penalti 2x durasi terlama (cost criterion)
        if ($sawMatrix[$tid]['c3'] === null) $sawMatrix[$tid]['c3'] = $maxDurasiTercatat * 2;
    }

    // 6. Normalization
    $maxC1 = 0;
    $maxC2 = 0;
    $minC3 = null;
    foreach ($sawMatrix as $row) {
        if ($row['c1'] > $maxC1) $maxC1 = $row['c1'];
        if ($row['c2'] > $maxC2) $maxC2 = $row['c2'];
        if ($row['c3'] > 0 && ($minC3 === null || $row['c3'] < $minC3)) $minC3 = $row['c3'];
    }
    if ($minC3 === null || $minC3 <= 0) $minC3 = 1;

    // Calculate final scores
    foreach ($sawMatrix as $tid => $row) {
        // Teknisi tanpa pekerjaan langsung skor 0
        if ($row['c1'] === 0) {
            $sawMatrix[$tid]['skor'] = 0;
            continue;
        }
        $r1 = $maxC1 > 0 ? $row['c1'] / $maxC1 : 0;
        $r2 = $maxC2 > 0 ? $row['c2'] / $maxC2 : 0;
        $r3 = $row['c3'] > 0 ? $minC3 / $row['c3'] : 1;

        $score = ($W1 * $r1) + ($W2 * $r2) + ($W3 * $r3);
        $sawMatrix[$tid]['skor'] = round($score, 6);
    }

    // 7. Map scores back to technicians array and sort
    foreach ($technicians as &$tech) {
        $tid = $tech['tech_id'];
        $tech['skor'] = $sawMatrix[$tid]['skor'] ?? 0.0;
        $tech['jumlah_pekerjaan'] = $sawMatrix[$tid]['c1'] ?? 0;
    }
    unset($tech);

    // Sort: teknisi aktif (skor > 0) dulu lalu skor DESC, yang tidak aktif di bawah
    usort($technicians, function($a, $b) {
        // Teknisi tanpa pekerjaan selalu di bawah
        if ($a['skor'] == 0 && $b['skor'] > 0) return 1;
        if ($b['skor'] == 0 && $a['skor'] > 0) return -1;
        // Jika skor sangat dekat, urutkan by nama
        if (abs($a['skor'] - $b['skor']) < 0.000001) {
            return strcmp($a['name'], $b['name']);
        }
        return $b['skor'] <=> $a['skor'];
    });

    // Assign rank positions
    $rank = 1;
    foreach ($technicians as &$tech) {
        $tech['ranking'] = $rank++;
    }
    unset($tech);

    echo json_encode([
        "data" => $technicians
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
