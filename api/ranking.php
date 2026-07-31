<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
header('Content-Type: application/json');

try {
    $type   = $_GET['type']   ?? 'ikr';   // 'ikr' or 'service'
    $level  = $_GET['level']  ?? 'kab';   // kab | kec | desa | perumahan
    $period = $_GET['period'] ?? 'month'; // today | week | month | year | custom

    $kab    = trim($_GET['kab']    ?? '');
    $kec    = trim($_GET['kec']    ?? '');
    $desa   = trim($_GET['desa']   ?? '');
    $from   = trim($_GET['from']   ?? '');
    $to     = trim($_GET['to']     ?? '');
    $year   = (int)($_GET['year']  ?? date('Y'));
    $month  = (int)($_GET['month'] ?? date('n'));

    // clamp month 1-12
    $month = max(1, min(12, $month));

    // Resolve date range
    $today = new DateTime('now');
    switch ($period) {
        case 'today':
            $dateFrom = $today->format('Y-m-d');
            $dateTo   = $today->format('Y-m-d');
            break;
        case 'week':
            $dateFrom = (clone $today)->modify('monday this week')->format('Y-m-d');
            $dateTo   = (clone $today)->modify('sunday this week')->format('Y-m-d');
            break;
        case 'year':
            $dateFrom = $year . '-01-01';
            $dateTo   = $year . '-12-31';
            break;
        case 'custom':
            $dateFrom = $from ?: $today->format('Y-m-d');
            $dateTo   = $to   ?: $today->format('Y-m-d');
            break;
        case 'month':
        default:
            $dateFrom = sprintf('%04d-%02d-01', $year, $month);
            $dateTo   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
            break;
    }
    if ($dateFrom > $dateTo) { [$dateFrom, $dateTo] = [$dateTo, $dateFrom]; }

    $params = [':from' => $dateFrom, ':to' => $dateTo];

    // ─────────────────────────────────────────────────────────────────────
    // IKR RANKING
    // Use MIN(label) to satisfy ONLY_FULL_GROUP_BY while GROUP BY LOWER()
    // normalizes case differences (BEKASI vs Bekasi counted as one group).
    // ─────────────────────────────────────────────────────────────────────
    if ($type === 'ikr') {

        if ($level === 'kab') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.kab),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT i.ikr_id) AS total
                    FROM ikr_report i
                    WHERE DATE(i.created_at) BETWEEN :from AND :to
                      AND i.kab IS NOT NULL AND TRIM(i.kab) != ''
                    GROUP BY LOWER(TRIM(i.kab))
                    ORDER BY total DESC
                    LIMIT 20";

        } elseif ($level === 'kec') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.kec),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT i.ikr_id) AS total
                    FROM ikr_report i
                    WHERE DATE(i.created_at) BETWEEN :from AND :to
                      AND LOWER(TRIM(i.kab)) = LOWER(TRIM(:kab))
                    GROUP BY LOWER(TRIM(i.kec))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab'] = $kab;

        } elseif ($level === 'desa') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.desa),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT i.ikr_id) AS total
                    FROM ikr_report i
                    WHERE DATE(i.created_at) BETWEEN :from AND :to
                      AND LOWER(TRIM(i.kab)) = LOWER(TRIM(:kab))
                      AND LOWER(TRIM(i.kec)) = LOWER(TRIM(:kec))
                    GROUP BY LOWER(TRIM(i.desa))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab'] = $kab;
            $params[':kec'] = $kec;

        } elseif ($level === 'perumahan') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(c.perumahan),''), 'Tidak Ada Perumahan')) AS label,
                        COUNT(DISTINCT i.ikr_id) AS total
                    FROM ikr_report i
                    LEFT JOIN customers c ON i.netpay_id = c.netpay_id
                    WHERE DATE(i.created_at) BETWEEN :from AND :to
                      AND LOWER(TRIM(i.kab))  = LOWER(TRIM(:kab))
                      AND LOWER(TRIM(i.kec))  = LOWER(TRIM(:kec))
                      AND LOWER(TRIM(i.desa)) = LOWER(TRIM(:desa))
                    GROUP BY LOWER(TRIM(COALESCE(c.perumahan,'')))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab']  = $kab;
            $params[':kec']  = $kec;
            $params[':desa'] = $desa;
        }

    // ─────────────────────────────────────────────────────────────────────
    // SERVICE RANKING
    // LEFT JOIN ikr_report: records without ikr_report still counted.
    // Records with no location match shown as 'Tidak Diketahui'.
    // MIN(label) satisfies ONLY_FULL_GROUP_BY.
    // GROUP BY LOWER() merges case-variant duplicates.
    // ─────────────────────────────────────────────────────────────────────
    } elseif ($type === 'service') {

        if ($level === 'kab') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.kab),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT sr.srv_id) AS total
                    FROM service_reports sr
                    LEFT JOIN ikr_report i ON sr.netpay_id = i.netpay_id
                    WHERE DATE(sr.tanggal) BETWEEN :from AND :to
                      AND i.kab IS NOT NULL AND TRIM(i.kab) != ''
                    GROUP BY LOWER(TRIM(COALESCE(i.kab,'')))
                    ORDER BY total DESC
                    LIMIT 20";

        } elseif ($level === 'kec') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.kec),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT sr.srv_id) AS total
                    FROM service_reports sr
                    LEFT JOIN ikr_report i ON sr.netpay_id = i.netpay_id
                    WHERE DATE(sr.tanggal) BETWEEN :from AND :to
                      AND LOWER(TRIM(COALESCE(i.kab,''))) = LOWER(TRIM(:kab))
                    GROUP BY LOWER(TRIM(COALESCE(i.kec,'')))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab'] = $kab;

        } elseif ($level === 'desa') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(i.desa),''), 'Tidak Diketahui')) AS label,
                        COUNT(DISTINCT sr.srv_id) AS total
                    FROM service_reports sr
                    LEFT JOIN ikr_report i ON sr.netpay_id = i.netpay_id
                    WHERE DATE(sr.tanggal) BETWEEN :from AND :to
                      AND LOWER(TRIM(COALESCE(i.kab,''))) = LOWER(TRIM(:kab))
                      AND LOWER(TRIM(COALESCE(i.kec,''))) = LOWER(TRIM(:kec))
                    GROUP BY LOWER(TRIM(COALESCE(i.desa,'')))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab'] = $kab;
            $params[':kec'] = $kec;

        } elseif ($level === 'perumahan') {
            $sql = "SELECT
                        MIN(COALESCE(NULLIF(TRIM(c.perumahan),''), 'Tidak Ada Perumahan')) AS label,
                        COUNT(DISTINCT sr.srv_id) AS total
                    FROM service_reports sr
                    LEFT JOIN ikr_report i ON sr.netpay_id = i.netpay_id
                    LEFT JOIN customers c ON sr.netpay_id = c.netpay_id
                    WHERE DATE(sr.tanggal) BETWEEN :from AND :to
                      AND LOWER(TRIM(COALESCE(i.kab,'')))  = LOWER(TRIM(:kab))
                      AND LOWER(TRIM(COALESCE(i.kec,'')))  = LOWER(TRIM(:kec))
                      AND LOWER(TRIM(COALESCE(i.desa,''))) = LOWER(TRIM(:desa))
                    GROUP BY LOWER(TRIM(COALESCE(c.perumahan,'')))
                    ORDER BY total DESC
                    LIMIT 20";
            $params[':kab']  = $kab;
            $params[':kec']  = $kec;
            $params[':desa'] = $desa;
        }
    }

    if (empty($sql)) {
        echo json_encode(['error' => true, 'message' => 'Invalid type or level']);
        exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute max for percentage bars
    $maxTotal = 0;
    foreach ($rows as $r) {
        if ((int)$r['total'] > $maxTotal) $maxTotal = (int)$r['total'];
    }

    foreach ($rows as &$r) {
        $r['total'] = (int)$r['total'];
        $r['pct']   = $maxTotal > 0 ? round(($r['total'] / $maxTotal) * 100, 1) : 0;
    }
    unset($r);

    echo json_encode([
        'success'   => true,
        'data'      => $rows,
        'level'     => $level,
        'type'      => $type,
        'date_from' => $dateFrom,
        'date_to'   => $dateTo,
        'meta'      => ['kab' => $kab, 'kec' => $kec, 'desa' => $desa]
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
