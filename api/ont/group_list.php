<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

header('Content-Type: application/json');

function detectBrandName($brand, $type) {
    if (!empty($brand) && trim($brand) !== '') return trim($brand);
    $t = strtoupper($type);
    if (strpos($t, 'HUAWEI') !== false || strpos($t, 'EG8') !== false || strpos($t, 'HG8') !== false || strpos($t, 'TM8') !== false || strpos($t, 'HW') !== false) {
        return 'Huawei';
    }
    if (strpos($t, 'ZTE') !== false || strpos($t, 'F6') !== false || strpos($t, 'GM220') !== false || strpos($t, 'ZX') !== false) {
        return 'ZTE';
    }
    if (strpos($t, 'FIBER') !== false || strpos($t, 'AN55') !== false || strpos($t, 'HG6') !== false) {
        return 'FiberHome';
    }
    if (strpos($t, 'TP-LINK') !== false || strpos($t, 'TPLINK') !== false) {
        return 'TP-Link';
    }
    return 'Lain-lain';
}

try {
    $search     = sanitize($_GET['q'] ?? $_GET['search'] ?? '');
    $period     = sanitize($_GET['period'] ?? 'all');
    $customFrom = sanitize($_GET['custom_from'] ?? '');
    $customTo   = sanitize($_GET['custom_to'] ?? '');
    $page       = max(1, (int)($_GET['page'] ?? 1));
    $limit      = max(1, min(100, (int)($_GET['limit'] ?? 15)));
    $offset     = ($page - 1) * $limit;

    // Date Range Resolution
    $where = [];
    $params = [];

    if ($period !== 'all') {
        $todayObj = new DateTime('now');
        switch ($period) {
            case 'today':
                $from = $todayObj->format('Y-m-d');
                $to   = $todayObj->format('Y-m-d');
                break;
            case 'week':
                $from = (clone $todayObj)->modify('monday this week')->format('Y-m-d');
                $to   = (clone $todayObj)->modify('sunday this week')->format('Y-m-d');
                break;
            case 'month':
                $from = $todayObj->format('Y-m-01');
                $to   = $todayObj->format('Y-m-t');
                break;
            case 'custom':
                $fObj = DateTime::createFromFormat('Y-m-d', $customFrom);
                $tObj = DateTime::createFromFormat('Y-m-d', $customTo);
                $from = $fObj ? $fObj->format('Y-m-d') : $todayObj->format('Y-m-d');
                $to   = $tObj ? $tObj->format('Y-m-d') : $todayObj->format('Y-m-d');
                break;
            default:
                $from = null;
                $to   = null;
                break;
        }

        if ($from && $to) {
            if ($from > $to) { [$from, $to] = [$to, $from]; }
            $where[] = "DATE(updated_at) BETWEEN :from_date AND :to_date";
            $params[':from_date'] = $from;
            $params[':to_date']   = $to;
        }
    }

    // Search filter
    if ($search !== '') {
        $where[] = "(type_ont LIKE :search OR brand LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // 1. KPI Counts
    $kpiSql = "
        SELECT 
            COUNT(1) AS total,
            SUM(CASE WHEN status = 'IN_STOCK' THEN 1 ELSE 0 END) AS in_stock,
            SUM(CASE WHEN status = 'IN_USE' THEN 1 ELSE 0 END) AS in_use,
            SUM(CASE WHEN status = 'DAMAGED' THEN 1 ELSE 0 END) AS damaged,
            SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) AS lost,
            SUM(CASE WHEN status = 'REPAIR' THEN 1 ELSE 0 END) AS repair
        FROM ont_inventory
        $whereSql
    ";
    $kpiStmt = $pdo->prepare($kpiSql);
    $kpiStmt->execute($params);
    $kpiData = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total' => 0, 'in_stock' => 0, 'in_use' => 0, 'damaged' => 0, 'lost' => 0, 'repair' => 0
    ];

    // 2. Count Total Distinct Groups
    $countGroupSql = "
        SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(type_ont),''), 'Tipe Umum')) 
        FROM ont_inventory
        $whereSql
    ";
    $countGroupStmt = $pdo->prepare($countGroupSql);
    $countGroupStmt->execute($params);
    $totalGroups = (int)$countGroupStmt->fetchColumn();

    // 3. Fetch Paginated Groups
    $stmtGroup = $pdo->prepare("
        SELECT 
            COALESCE(NULLIF(TRIM(type_ont),''), 'Tipe Umum') AS type_ont,
            brand,
            COUNT(1) AS total_count,
            SUM(CASE WHEN status = 'IN_STOCK' THEN 1 ELSE 0 END) AS in_stock_count,
            SUM(CASE WHEN status = 'IN_USE' THEN 1 ELSE 0 END) AS in_use_count,
            SUM(CASE WHEN status = 'DAMAGED' THEN 1 ELSE 0 END) AS damaged_count,
            SUM(CASE WHEN status = 'REPAIR' THEN 1 ELSE 0 END) AS repair_count,
            SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) AS lost_count
        FROM ont_inventory
        $whereSql
        GROUP BY type_ont, brand
        ORDER BY total_count DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $stmtGroup->bindValue($k, $v);
    }
    $stmtGroup->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtGroup->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtGroup->execute();
    $rawGroups = $stmtGroup->fetchAll(PDO::FETCH_ASSOC);

    // Process & Enrich Brand Detection
    $groups = [];
    foreach ($rawGroups as $g) {
        $typeVal  = trim($g['type_ont']);
        $brandVal = detectBrandName($g['brand'], $typeVal);

        $groups[] = [
            'type_ont'       => $typeVal,
            'brand'          => $brandVal,
            'raw_brand'      => $g['brand'] ?: '',
            'total_count'    => (int)$g['total_count'],
            'in_stock_count' => (int)$g['in_stock_count'],
            'in_use_count'   => (int)$g['in_use_count'],
            'damaged_count'  => (int)$g['damaged_count'],
            'repair_count'   => (int)$g['repair_count'],
            'lost_count'     => (int)$g['lost_count']
        ];
    }

    echo json_encode([
        'status' => true,
        'kpi'    => [
            'total'     => (int)$kpiData['total'],
            'in_stock'  => (int)$kpiData['in_stock'],
            'in_use'    => (int)$kpiData['in_use'],
            'damaged'   => (int)$kpiData['damaged'],
            'lost'      => (int)$kpiData['lost'],
            'repair'    => (int)$kpiData['repair'],
        ],
        'groups' => $groups,
        'pagination' => [
            'total_items' => $totalGroups,
            'total_pages' => (int)ceil($totalGroups / $limit),
            'current_page'=> $page,
            'limit'       => $limit
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengambil ringkasan tipe ONT: ' . $e->getMessage()
    ]);
}
