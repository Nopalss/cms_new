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
    $stmt = $pdo->query("SELECT admin_id, name FROM admin WHERE jabatan IN ('NOC', 'SuperAdmin', 'Admin') OR jabatan IS NULL ORDER BY name ASC");
    echo json_encode([
        'status' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
