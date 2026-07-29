<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$tim  = trim($data['tim'] ?? '');
$tech = $data['tech'] ?? [];

if (!$tim) {
    echo json_encode([
        'success' => false,
        'message' => 'TIM ID kosong'
    ]);
    exit;
}

try {

    // lepas semua member dari tim ini dulu
    $pdo->prepare("UPDATE technician SET tim_id=NULL WHERE tim_id=?")
        ->execute([$tim]);

    // assign ulang
    if (!empty($tech)) {

        $stmt = $pdo->prepare("
            UPDATE technician 
            SET tim_id = :tim 
            WHERE tech_id = :tech
        ");

        foreach ($tech as $t) {
            $stmt->execute([
                ':tim'  => $tim,
                ':tech' => $t
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Teknisi berhasil di-assign'
    ]);
} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
