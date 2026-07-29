<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

header('Content-Type: application/json');

try {
    $search     = sanitize($_GET['q'] ?? $_GET['search'] ?? '');
    $statusFilter = sanitize($_GET['status'] ?? '');
    $netpayId   = sanitize($_GET['netpay_id'] ?? '');
    $limit      = max(1, min(50, (int)($_GET['limit'] ?? 20)));

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(o.serial_number LIKE :q OR o.ont_id LIKE :q OR o.type_ont LIKE :q OR o.brand LIKE :q OR o.mac_address LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }

    if ($statusFilter !== '') {
        $where[] = "o.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($netpayId !== '') {
        $where[] = "o.current_netpay_id = :netpay_id";
        $params[':netpay_id'] = $netpayId;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT 
            o.ont_key,
            o.ont_id,
            o.serial_number,
            o.type_ont,
            o.brand,
            o.mac_address,
            o.status,
            o.current_netpay_id,
            c.name AS customer_name
        FROM ont_inventory o
        LEFT JOIN customers c ON c.netpay_id = o.current_netpay_id
        $whereSql
        ORDER BY o.ont_key DESC
        LIMIT :limit
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'count'  => count($results),
        'data'   => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Search error: ' . $e->getMessage()
    ]);
}
