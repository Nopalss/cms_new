<?php

require_once __DIR__ . "/../../../includes/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Akses tidak valid'
    ]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT DISTINCT UPPER(TRIM(perumahan)) AS perumahan
        FROM customers
        WHERE perumahan IS NOT NULL AND TRIM(perumahan) <> ''
        ORDER BY perumahan ASC
    ");
    $perumahan = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => true,
        'data' => $perumahan
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
