<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $search = $_POST['query']['generalSearch'] ?? $_GET['query']['generalSearch'] ?? '';

    // Calculate dates for current monthly cycle (26th of last month to 25th of current month)
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

    $start_date = $start->format('Y-m-d');
    $end_date   = $end->format('Y-m-d');

    // 1. Fetch all teams
    $sqlTeams = "SELECT * FROM tim WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sqlTeams .= " AND (tim_id LIKE :search OR nama LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $stmtTeams = $pdo->prepare($sqlTeams);
    $stmtTeams->execute($params);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch C1 & C3 (Activity and duration) grouped by tim_id
    $sqlActivity = "
        SELECT t.tim_id,
               COUNT(1) as jumlah_pekerjaan,
               AVG(TIMESTAMPDIFF(SECOND, x.start_time, x.end_time)) as durasi_rata2
        FROM (
            SELECT srp.tech_id, s.start_time, s.end_time, s.date
            FROM service_reports sr
            JOIN service_report_pic srp ON sr.srv_id = srp.srv_id
            JOIN schedules s ON sr.schedule_id = s.schedule_id
            WHERE s.status = 'Done'

            UNION ALL

            SELECT drp.tech_id, s.start_time, s.end_time, s.date
            FROM dismantle_reports dr
            JOIN dismantle_report_pic drp ON dr.dismantle_id = drp.dismantle_id
            JOIN schedules s ON dr.schedule_id = s.schedule_id
            WHERE s.status = 'Done'

            UNION ALL

            SELECT irp.tech_id, s.start_time, s.end_time, s.date
            FROM ikr_report i
            JOIN ikr_report_pic irp ON i.ikr_id = irp.ikr_id
            JOIN schedules s ON i.schedule_id = s.schedule_id
            WHERE s.status = 'Done'
        ) x
        JOIN technician t ON x.tech_id = t.tech_id
        WHERE t.tim_id IS NOT NULL AND x.date BETWEEN :start AND :end
        GROUP BY t.tim_id
    ";
    $stmtActivity = $pdo->prepare($sqlActivity);
    $stmtActivity->execute([':start' => $start_date, ':end' => $end_date]);
    $activityMap = [];
    foreach ($stmtActivity->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $activityMap[$row['tim_id']] = [
            'jumlah_pekerjaan' => (int)$row['jumlah_pekerjaan'],
            'durasi_rata2'     => $row['durasi_rata2'] !== null ? (float)$row['durasi_rata2'] : null,
        ];
    }

    // 3. Fetch C2 (Ratings) grouped by tim_id
    $sqlRating = "
        SELECT t.tim_id,
               AVG(tr.rating) as rating_rata2,
               COUNT(tr.rating) as jumlah_rating
        FROM detail_ratings dr
        JOIN technician_ratings tr ON dr.rating_id = tr.rating_id
        JOIN schedules s ON tr.schedule_id = s.schedule_id
        JOIN technician t ON dr.tech_id = t.tech_id
        WHERE tr.rating IS NOT NULL
          AND tr.status = 'Rated'
          AND s.status = 'Done'
          AND t.tim_id IS NOT NULL
          AND s.date BETWEEN :start AND :end
        GROUP BY t.tim_id
    ";
    $stmtRating = $pdo->prepare($sqlRating);
    $stmtRating->execute([':start' => $start_date, ':end' => $end_date]);
    $ratingMap = [];
    foreach ($stmtRating->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ratingMap[$row['tim_id']] = [
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
    foreach ($teams as $team) {
        $timId = $team['tim_id'];
        $act = $activityMap[$timId] ?? ['jumlah_pekerjaan' => 0, 'durasi_rata2' => null];
        $rat = $ratingMap[$timId] ?? ['rating_rata2' => null, 'jumlah_rating' => 0];

        $sawMatrix[$timId] = [
            'tim_id'     => $timId,
            'c1'         => $act['jumlah_pekerjaan'],
            'c2'         => $rat['rating_rata2'],
            'c3'         => $act['durasi_rata2'],
        ];
    }

    // 5. Handle missing values
    $maxDurasiTercatat = 0;
    foreach ($sawMatrix as $row) {
        if ($row['c3'] !== null && $row['c3'] > $maxDurasiTercatat) {
            $maxDurasiTercatat = $row['c3'];
        }
    }
    if ($maxDurasiTercatat <= 0) $maxDurasiTercatat = 3600; // fallback 1 jam dalam detik

    foreach ($sawMatrix as $timId => $row) {
        if ($sawMatrix[$timId]['c2'] === null) $sawMatrix[$timId]['c2'] = 0;
        // Penalti 2x durasi terlama untuk tim tanpa data waktu
        if ($sawMatrix[$timId]['c3'] === null) $sawMatrix[$timId]['c3'] = $maxDurasiTercatat * 2;
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
    foreach ($sawMatrix as $timId => $row) {
        // Tim tanpa pekerjaan selesai langsung dapat skor 0
        if ($row['c1'] === 0) {
            $sawMatrix[$timId]['skor'] = 0;
            continue;
        }
        $r1 = $maxC1 > 0 ? $row['c1'] / $maxC1 : 0;
        $r2 = $maxC2 > 0 ? $row['c2'] / $maxC2 : 0;
        $r3 = $row['c3'] > 0 ? $minC3 / $row['c3'] : 1;

        $score = ($W1 * $r1) + ($W2 * $r2) + ($W3 * $r3);
        $sawMatrix[$timId]['skor'] = round($score, 6);
    }

    // 7. Map scores back to teams and sort
    foreach ($teams as &$team) {
        $timId = $team['tim_id'];
        $team['skor'] = $sawMatrix[$timId]['skor'] ?? 0.0;
        $team['jumlah_pekerjaan'] = $sawMatrix[$timId]['c1'] ?? 0;
    }
    unset($team);

    // Sort: tim aktif (skor > 0) dulu lalu skor DESC, yang tidak aktif di bawah
    usort($teams, function($a, $b) {
        // Tim tanpa pekerjaan selalu di bawah
        if ($a['skor'] == 0 && $b['skor'] > 0) return 1;
        if ($b['skor'] == 0 && $a['skor'] > 0) return -1;
        // Jika skor sama, sort by nama tim
        if (abs($a['skor'] - $b['skor']) < 0.000001) {
            return strcmp($a['nama'], $b['nama']);
        }
        return $b['skor'] <=> $a['skor'];
    });

    // Assign rank positions
    $rank = 1;
    foreach ($teams as &$team) {
        $team['ranking'] = $rank++;
    }
    unset($team);

    echo json_encode([
        "data" => $teams
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
