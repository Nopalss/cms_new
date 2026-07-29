<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID tidak valid'
    ]);
    exit;
}

try {

    // ambil tim_id dulu
    $stmt = $pdo->prepare("SELECT tim_id FROM tim WHERE id=?");
    $stmt->execute([$id]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        echo json_encode([
            'success' => false,
            'message' => 'Team tidak ditemukan'
        ]);
        exit;
    }

    $tim_id = $team['tim_id'];

    // kosongkan technician
    $pdo->prepare("UPDATE technician SET tim_id=NULL WHERE tim_id=?")
        ->execute([$tim_id]);

    // delete team
    $pdo->prepare("DELETE FROM tim WHERE id=?")->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Team berhasil dihapus'
    ]);
} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
