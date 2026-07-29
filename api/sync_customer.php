<?php
// sync_customer.php (RECEIVER - FIXED)

require_once __DIR__ . "/../includes/config.php";
include __DIR__ . "/token/env.php";

header("Content-Type: application/json");

$integrationToken = $env['JBR_CMS_INTEGRATION_TOKEN'];

// --- VALIDASI TOKEN (sama seperti sebelumnya) ---
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $_SERVER['HTTP_AUTHORIZATION']
    ?? $headers['Authorization']
    ?? $headers['authorization']
    ?? '';

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches) || trim($matches[1]) !== $integrationToken) {
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

// --- AMBIL BODY ---
$body = file_get_contents("php://input");
$data = json_decode($body, true);

if (!is_array($data) || count($data) === 0) {
    http_response_code(400);
    echo json_encode(["status" => false, "message" => "Payload tidak valid."]);
    exit;
}

// --- PASTIKAN netpay_id punya UNIQUE constraint ---
// ALTER TABLE customers ADD UNIQUE KEY uq_netpay_id (netpay_id);

$insert = 0;
$update = 0;
$failed = [];

// --- UPSERT SATU PER SATU dengan error handling per baris ---
// Tidak pakai satu big transaction agar 1 baris gagal tidak rollback semua

$upsertStmt = $pdo->prepare("
    INSERT INTO customers
        (netpay_id, name, perumahan, location, phone, paket_internet, is_active, harga, ip)
    VALUES
        (:netpay_id, :name, :perumahan, :location, :phone, :paket_internet, :is_active, :harga, :ip)
    ON DUPLICATE KEY UPDATE
        name           = VALUES(name),
        perumahan      = VALUES(perumahan),
        location       = VALUES(location),
        phone          = VALUES(phone),
        paket_internet = VALUES(paket_internet),
        is_active      = VALUES(is_active),
        harga          = VALUES(harga),
        ip             = VALUES(ip),
        updated_at     = CURRENT_TIMESTAMP
");

foreach ($data as $customer) {
    try {
        $upsertStmt->execute([
            ':netpay_id'      => $customer['netpay_id'],
            ':name'           => $customer['name'],
            ':perumahan'      => $customer['perumahan'],
            ':location'       => $customer['location'],
            ':phone'          => $customer['phone'],
            ':paket_internet' => $customer['paket_internet'],
            ':is_active'      => $customer['is_active'],
            ':harga'          => $customer['harga'],
            ':ip'             => $customer['ip'],
        ]);

        // rowCount() = 1 → INSERT, 2 → UPDATE, 0 → tidak ada perubahan
        $affected = $upsertStmt->rowCount();
        if ($affected === 1) $insert++;
        else $update++;
    } catch (Exception $e) {
        // Baris ini gagal, catat & lanjut ke baris berikutnya
        $failed[] = [
            'netpay_id' => $customer['netpay_id'] ?? null,
            'error'     => $e->getMessage()
        ];
    }
}

$statusCode = count($failed) > 0 ? 207 : 200; // 207 = Partial success
http_response_code($statusCode);

echo json_encode([
    "status"  => count($failed) === 0,
    "message" => "Sinkronisasi selesai.",
    "total"   => count($data),
    "insert"  => $insert,
    "update"  => $update,
    "failed"  => count($failed),
    "failed_detail" => $failed // kirim balik ke sender biar bisa di-log
]);
