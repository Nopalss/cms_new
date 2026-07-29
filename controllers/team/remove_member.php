<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$tech_id = trim($data['tech_id'] ?? '');

if (!$tech_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Tech ID tidak valid'
    ]);
    exit;
}

try {

    // pastikan teknisi ada
    $check = $pdo->prepare("SELECT tech_id FROM technician WHERE tech_id = ?");
    $check->execute([$tech_id]);

    if (!$check->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Teknisi tidak ditemukan'
        ]);
        exit;
    }

    // keluarkan teknisi dari team
    $stmt = $pdo->prepare("
        UPDATE technician 
        SET tim_id = NULL
        WHERE tech_id = :tech_id
    ");

    $stmt->execute([
        ':tech_id' => $tech_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Teknisi berhasil dikeluarkan dari team'
    ]);
} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
