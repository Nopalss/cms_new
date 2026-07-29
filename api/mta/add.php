<?php
require_once __DIR__ . "/../../includes/config.php";
include __DIR__ . "/../token/env.php";

/**
 * Endpoint: POST /api/v1/webhook/customer-sync
 * Terima data pelanggan baru dari Go Backend JBR-Minpo.
 *
 * Asumsi yang dipakai di file ini (sesuaikan kalau beda di project kamu):
 * - config.php menyediakan variabel $pdo (instance PDO yang sudah connect ke DB)
 * - token/env.php mendefinisikan konstanta JBR_MINPO_INTEGRATION_TOKEN
 * - customer_id dari Minpo dipakai langsung sebagai netpay_id
 *   (perlu dikonfirmasi ulang nanti pas bahas billing/Netpay)
 * - kolom last_source, sync_status, minpo_updated_at sudah ditambahkan
 *   ke customer_details (ALTER TABLE dari desain sebelumnya)
 */

header('Content-Type: application/json');

function getBearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '');

    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
        return trim($m[1]);
    }
    return '';
}

function respond(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

$minpro_api_url = rtrim($env['JBR_MINPO_API_URL'], '/');
$minpro_token   = $env['JBR_MINPO_INTEGRATION_TOKEN'];

// --- 1. Autentikasi ---
$token = getBearerToken();
if ($token === '' || !hash_equals($minpro_token, $token)) {
    respond(401, ['status' => 'error', 'message' => 'Token tidak valid']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Method tidak diizinkan']);
}

// --- 2. Ambil & validasi payload ---
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respond(400, ['status' => 'error', 'message' => 'JSON tidak valid']);
}

if (empty($data['customer_id'])) {
    respond(422, ['status' => 'error', 'message' => 'customer_id wajib diisi']);
}

$netpayId = (string) $data['customer_id'];

// --- 3. Simpan ke DB dalam satu transaksi ---
try {
    $pdo->beginTransaction();

    $stmtCustomer = $pdo->prepare("
        INSERT INTO customers (netpay_id, name, phone, is_active, created_at)
        VALUES (:netpay_id, :name, :phone, 'ACTIVE', NOW())
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            phone = VALUES(phone),
            updated_at = NOW()
    ");
    $stmtCustomer->execute([
        ':netpay_id' => $netpayId,
        ':name'      => $data['full_name'] ?? null,
        ':phone'     => $data['phone'] ?? null,
    ]);

    $stmtDetail = $pdo->prepare("
        INSERT INTO customer_details (
            netpay_id, email, nik, ktp_url, kk_url,
            package_external_id, package_name, area_external_id, area_name,
            pop_external_id, pop_name, vlan_id, monthly_bill, ikr_fee,
            marital_status, device_brand, modem_sn, installed_at, due_day,
            inst_street, inst_rt, inst_rw, inst_village, inst_district, inst_city, inst_province, inst_zip,
            same_with_installation,
            ktp_street, ktp_rt, ktp_rw, ktp_village, ktp_district, ktp_city, ktp_province, ktp_zip,
            gender, birth_place, birth_date, occupation,
            last_source, sync_status, minpo_updated_at, created_at
        ) VALUES (
            :netpay_id, :email, :nik, :ktp_url, :kk_url,
            :package_external_id, :package_name, :area_external_id, :area_name,
            :pop_external_id, :pop_name, :vlan_id, :monthly_bill, :ikr_fee,
            :marital_status, :device_brand, :modem_sn, :installed_at, :due_day,
            :inst_street, :inst_rt, :inst_rw, :inst_village, :inst_district, :inst_city, :inst_province, :inst_zip,
            :same_with_installation,
            :ktp_street, :ktp_rt, :ktp_rw, :ktp_village, :ktp_district, :ktp_city, :ktp_province, :ktp_zip,
            :gender, :birth_place, :birth_date, :occupation,
            'minpo', 'synced', NOW(), NOW()
        )
        ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            nik = VALUES(nik),
            ktp_url = VALUES(ktp_url),
            kk_url = VALUES(kk_url),
            package_external_id = VALUES(package_external_id),
            package_name = VALUES(package_name),
            area_external_id = VALUES(area_external_id),
            area_name = VALUES(area_name),
            pop_external_id = VALUES(pop_external_id),
            pop_name = VALUES(pop_name),
            vlan_id = VALUES(vlan_id),
            monthly_bill = VALUES(monthly_bill),
            ikr_fee = VALUES(ikr_fee),
            marital_status = VALUES(marital_status),
            device_brand = VALUES(device_brand),
            modem_sn = VALUES(modem_sn),
            installed_at = VALUES(installed_at),
            due_day = VALUES(due_day),
            inst_street = VALUES(inst_street),
            inst_rt = VALUES(inst_rt),
            inst_rw = VALUES(inst_rw),
            inst_village = VALUES(inst_village),
            inst_district = VALUES(inst_district),
            inst_city = VALUES(inst_city),
            inst_province = VALUES(inst_province),
            inst_zip = VALUES(inst_zip),
            same_with_installation = VALUES(same_with_installation),
            ktp_street = VALUES(ktp_street),
            ktp_rt = VALUES(ktp_rt),
            ktp_rw = VALUES(ktp_rw),
            ktp_village = VALUES(ktp_village),
            ktp_district = VALUES(ktp_district),
            ktp_city = VALUES(ktp_city),
            ktp_province = VALUES(ktp_province),
            ktp_zip = VALUES(ktp_zip),
            gender = VALUES(gender),
            birth_place = VALUES(birth_place),
            birth_date = VALUES(birth_date),
            occupation = VALUES(occupation),
            last_source = 'minpo',
            sync_status = 'synced',
            minpo_updated_at = NOW(),
            updated_at = NOW()
    ");
    $stmtDetail->execute([
        ':netpay_id' => $netpayId,
        ':email' => $data['email'] ?? null,
        ':nik' => $data['nik'] ?? null,
        ':ktp_url' => $data['ktp_url'] ?? null,
        ':kk_url' => $data['kk_url'] ?? null,
        ':package_external_id' => $data['package_external_id'] ?? null,
        ':package_name' => $data['package_name'] ?? null,
        ':area_external_id' => $data['area_external_id'] ?? null,
        ':area_name' => $data['area_name'] ?? null,
        ':pop_external_id' => $data['pop_external_id'] ?? null,
        ':pop_name' => $data['pop_name'] ?? null,
        ':vlan_id' => $data['vlan_id'] ?? null,
        ':monthly_bill' => $data['monthly_bill'] ?? null,
        ':ikr_fee' => $data['ikr_fee'] ?? null,
        ':marital_status' => $data['marital_status'] ?? null,
        ':device_brand' => $data['device_brand'] ?? null,
        ':modem_sn' => $data['modem_sn'] ?? null,
        ':installed_at' => !empty($data['installed_at']) ? date('Y-m-d H:i:s', strtotime($data['installed_at'])) : null,
        ':due_day' => $data['due_day'] ?? null,
        ':inst_street' => $data['inst_street'] ?? null,
        ':inst_rt' => $data['inst_rt'] ?? null,
        ':inst_rw' => $data['inst_rw'] ?? null,
        ':inst_village' => $data['inst_village'] ?? null,
        ':inst_district' => $data['inst_district'] ?? null,
        ':inst_city' => $data['inst_city'] ?? null,
        ':inst_province' => $data['inst_province'] ?? null,
        ':inst_zip' => $data['inst_zip'] ?? null,
        ':same_with_installation' => !empty($data['same_with_installation']) ? 1 : 0,
        ':ktp_street' => $data['ktp_street'] ?? null,
        ':ktp_rt' => $data['ktp_rt'] ?? null,
        ':ktp_rw' => $data['ktp_rw'] ?? null,
        ':ktp_village' => $data['ktp_village'] ?? null,
        ':ktp_district' => $data['ktp_district'] ?? null,
        ':ktp_city' => $data['ktp_city'] ?? null,
        ':ktp_province' => $data['ktp_province'] ?? null,
        ':ktp_zip' => $data['ktp_zip'] ?? null,
        ':gender' => $data['gender'] ?? null,
        ':birth_place' => $data['birth_place'] ?? null,
        ':birth_date' => $data['birth_date'] ?? null,
        ':occupation' => $data['occupation'] ?? null,
    ]);

    $pdo->commit();

    respond(200, [
        'status' => 'success',
        'message' => 'Customer tersinkronisasi',
        'netpay_id' => $netpayId,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[customer-sync] ' . $e->getMessage());
    respond(500, ['status' => 'error', 'message' => 'Gagal menyimpan data']);
}
