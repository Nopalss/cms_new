<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/sanitize.php';

try {
    $search       = sanitize($_GET['q'] ?? $_GET['search'] ?? '');
    $statusFilter = sanitize($_GET['status'] ?? '');
    $brandFilter  = sanitize($_GET['brand'] ?? '');
    $typeFilter   = sanitize($_GET['type_ont'] ?? $_GET['type'] ?? '');

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(
            o.serial_number LIKE :search OR
            o.ont_id LIKE :search OR
            o.mac_address LIKE :search OR
            o.current_netpay_id LIKE :search OR
            c.name LIKE :search
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

    $sql = "
        SELECT 
            o.ont_id,
            o.serial_number,
            o.brand,
            o.type_ont,
            o.mac_address,
            o.status,
            o.current_netpay_id,
            c.name AS customer_name,
            o.condition_note,
            o.created_at,
            o.updated_at
        FROM ont_inventory o
        LEFT JOIN customers c ON c.netpay_id = o.current_netpay_id
        $whereSql
        ORDER BY o.ont_key DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Stok_ONT_jTracks_" . date("Ymd_His") . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel
    fputs($output, "\xEF\xBB\xBF");

    // CSV Header
    fputcsv($output, [
        'ONT ID', 'Serial Number (SN)', 'Brand', 'Type ONT', 'MAC Address', 'Status', 
        'Netpay ID', 'Nama Customer', 'Condition Note', 'Created At', 'Updated At'
    ]);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['ont_id'],
            $row['serial_number'],
            $row['brand'] ?: '-',
            $row['type_ont'] ?: '-',
            $row['mac_address'] ?: '-',
            $row['status'],
            $row['current_netpay_id'] ?: '-',
            $row['customer_name'] ?: '-',
            $row['condition_note'] ?: '-',
            $row['created_at'],
            $row['updated_at']
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    echo "Export Error: " . $e->getMessage();
}
