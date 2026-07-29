<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Method tidak valid']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $schedule_id = isset($input['schedule_id']) ? $input['schedule_id'] : 0;
    $netpay_id   = isset($input['netpay_id'])   ? $input['netpay_id']    : 0;
    $pics = isset($input['pics'])
        ? implode(',', $input['pics'])
        : '';

    if (!$schedule_id || !$netpay_id) {
        throw new Exception('Data tidak lengkap');
    }

    // ── Cek apakah sudah ada token untuk schedule ini ────────────
    // Kalau teknisi pencet tombol 2x, return token yang sama aja
    $check = $pdo->prepare("
        SELECT token FROM technician_ratings
        WHERE schedule_id = :sk
        LIMIT 1
    ");
    $check->execute([':sk' => $schedule_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Sudah ada — update is_sent & sent_at, return token yang sama
        $pdo->prepare("
    UPDATE technician_ratings
    SET
        is_sent = 'Y',
        sent_at = NOW()
    WHERE schedule_id = :sk
")->execute([
            ':sk'   => $schedule_id
        ]);

        echo json_encode(['status' => true, 'token' => $existing['token']]);
        exit;
    }

    // ── Buat token baru ──────────────────────────────────────────
    $token     = bin2hex(random_bytes(24)); // 48-char hex, cukup aman
    $rating_id = 'RTG' . date('YmdHis') . rand(10, 99);

    $stmt = $pdo->prepare("
    INSERT INTO technician_ratings
    (
        rating_id,
        schedule_id,
        netpay_id,
        token,
        is_sent,
        sent_at,
        status
    )
    VALUES
    (
        :rating_id,
        :schedule_id,
        :netpay_id,
        :token,
        'Y',
        NOW(),
        'Pending'
    )
");
    $stmt->execute([
        ':rating_id'    => $rating_id,
        ':schedule_id' => $schedule_id,
        ':netpay_id'   => $netpay_id,
        ':token'        => $token,
    ]);
    // Simpan detail teknisi yang ikut pada rating ini
    $detail = $pdo->prepare("
    INSERT INTO detail_ratings (tech_id, rating_id)
    VALUES (:tech_id, :rating_id)
");

    foreach ($input['pics'] as $tech_id) {
        $detail->execute([
            ':tech_id'   => $tech_id,
            ':rating_id' => $rating_id
        ]);
    }
    echo json_encode(['status' => true, 'token' => $token]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
