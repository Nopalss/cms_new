<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

header('Content-Type: application/json');

try {
    // 1. Parameters
    $search       = sanitize($_GET['q'] ?? $_GET['search'] ?? '');
    $statusFilter = sanitize($_GET['status'] ?? '');
    $brandFilter  = sanitize($_GET['brand'] ?? '');
    $typeFilter   = sanitize($_GET['type_ont'] ?? $_GET['type'] ?? '');
    $page         = max(1, (int)($_GET['page'] ?? 1));
    $limit        = max(1, min(100, (int)($_GET['limit'] ?? 15)));
    $offset       = ($page - 1) * $limit;

    // 2. Fetch KPI Counts
    $kpiStmt = $pdo->query("
        SELECT 
            COUNT(1) AS total,
            SUM(CASE WHEN status = 'IN_STOCK' THEN 1 ELSE 0 END) AS in_stock,
            SUM(CASE WHEN status = 'IN_USE' THEN 1 ELSE 0 END) AS in_use,
            SUM(CASE WHEN status = 'DAMAGED' THEN 1 ELSE 0 END) AS damaged,
            SUM(CASE WHEN status = 'LOST' THEN 1 ELSE 0 END) AS lost,
            SUM(CASE WHEN status = 'REPAIR' THEN 1 ELSE 0 END) AS repair
        FROM ont_inventory
    ");
    $kpiData = $kpiStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total' => 0, 'in_stock' => 0, 'in_use' => 0, 'damaged' => 0, 'lost' => 0, 'repair' => 0
    ];

    // 3. Build WHERE clauses
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(
            o.serial_number LIKE :search OR
            o.ont_id LIKE :search OR
            o.mac_address LIKE :search OR
            o.current_netpay_id LIKE :search OR
            c.name LIKE :search OR
            c.perumahan LIKE :search
        )";
        $params[':search'] = '%' . $search . '%';
    }

    if ($statusFilter !== '' && $statusFilter !== 'ALL') {
        $where[] = "o.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($brandFilter !== '' && $brandFilter !== 'ALL') {
        $where[] = "o.brand = :brand";
        $params[':brand'] = $brandFilter;
    }

    if ($typeFilter !== '' && $typeFilter !== 'ALL') {
        $where[] = "o.type_ont = :type_ont";
        $params[':type_ont'] = $typeFilter;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // 4. Count Filtered Items
    $countSql = "
        SELECT COUNT(1) 
        FROM ont_inventory o
        LEFT JOIN customers c ON c.netpay_id = o.current_netpay_id
        $whereSql
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalFiltered = (int)$countStmt->fetchColumn();

    // 5. Fetch Data
    $dataSql = "
        SELECT 
            o.ont_key,
            o.ont_id,
            o.serial_number,
            o.type_ont,
            o.brand,
            o.mac_address,
            o.status,
            o.current_netpay_id,
            o.condition_note,
            o.created_at,
            o.updated_at,
            c.name AS customer_name,
            c.phone AS customer_phone,
            CONCAT_WS(' ', c.perumahan, c.location) AS customer_alamat
        FROM ont_inventory o
        LEFT JOIN customers c ON c.netpay_id = o.current_netpay_id
        $whereSql
        ORDER BY o.ont_key DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Distinct Brands and Types for Filters
    $brands = $pdo->query("SELECT DISTINCT brand FROM ont_inventory WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
    $types  = $pdo->query("SELECT DISTINCT type_ont FROM ont_inventory WHERE type_ont IS NOT NULL AND type_ont != '' ORDER BY type_ont ASC")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => true,
        'kpi' => [
            'total'     => (int)$kpiData['total'],
            'in_stock'  => (int)$kpiData['in_stock'],
            'in_use'    => (int)$kpiData['in_use'],
            'damaged'   => (int)$kpiData['damaged'],
            'lost'      => (int)$kpiData['lost'],
            'repair'    => (int)$kpiData['repair'],
        ],
        'data' => $items,
        'pagination' => [
            'total_items' => $totalFiltered,
            'total_pages' => (int)ceil($totalFiltered / $limit),
            'current_page'=> $page,
            'limit'       => $limit
        ],
        'brands' => $brands,
        'types'  => $types
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengambil data stok ONT: ' . $e->getMessage()
    ]);
}
