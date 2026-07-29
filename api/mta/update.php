<?php
require_once __DIR__ . "/../../includes/config.php";
include __DIR__ . "/../token/env.php";

/**
 * Endpoint : POST /api/v1/webhook/customer-update
 *
 * Digunakan ketika customer melakukan perubahan profil di Minpo.
 *
 * Customer WAJIB sudah ada di Apps.
 * Endpoint ini hanya melakukan UPDATE.
 */

header('Content-Type: application/json');

/**
 * --------------------------------------------------------------------------
 * Helper
 * --------------------------------------------------------------------------
 */

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

/**
 * --------------------------------------------------------------------------
 * Config
 * --------------------------------------------------------------------------
 */

$minpro_api_url = rtrim($env['JBR_MINPO_API_URL'], '/');
$minpro_token   = $env['JBR_MINPO_INTEGRATION_TOKEN'];

/**
 * --------------------------------------------------------------------------
 * Authentication
 * --------------------------------------------------------------------------
 */

$token = getBearerToken();

if ($token === '' || !hash_equals($minpro_token, $token)) {
    respond(401, [
        'status'  => 'error',
        'message' => 'Token tidak valid'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'status'  => 'error',
        'message' => 'Method tidak diizinkan'
    ]);
}

/**
 * --------------------------------------------------------------------------
 * Payload
 * --------------------------------------------------------------------------
 */

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    respond(400, [
        'status'  => 'error',
        'message' => 'JSON tidak valid'
    ]);
}

if (empty($data['customer_id'])) {
    respond(422, [
        'status'  => 'error',
        'message' => 'customer_id wajib diisi'
    ]);
}

$netpayId = (string) $data['customer_id'];

try {

    $pdo->beginTransaction();

    /**
     * ----------------------------------------------------------------------
     * Pastikan customer sudah ada
     * ----------------------------------------------------------------------
     */

    $checkCustomer = $pdo->prepare("
        SELECT customer_key
        FROM customers
        WHERE netpay_id = :netpay_id
        LIMIT 1
    ");

    $checkCustomer->execute([
        ':netpay_id' => $netpayId
    ]);

    if (!$checkCustomer->fetch(PDO::FETCH_ASSOC)) {

        $pdo->rollBack();

        respond(404, [
            'status' => 'error',
            'message' => 'Customer tidak ditemukan'
        ]);
    }

    /**
     * ----------------------------------------------------------------------
     * Dynamic Update Customers
     * ----------------------------------------------------------------------
     */

    $customerMap = [
        'full_name' => 'name',
        'phone'     => 'phone'
    ];

    $customerSet = [];
    $customerBind = [
        ':netpay_id' => $netpayId
    ];

    foreach ($customerMap as $payloadField => $column) {

        if (array_key_exists($payloadField, $data)) {

            $customerSet[] = "{$column} = :{$column}";
            $customerBind[":{$column}"] = $data[$payloadField];
        }
    }

    if (!empty($customerSet)) {

        $customerSet[] = "updated_at = NOW()";

        $sqlCustomer = "
            UPDATE customers
            SET " . implode(", ", $customerSet) . "
            WHERE netpay_id = :netpay_id
        ";

        $stmtCustomer = $pdo->prepare($sqlCustomer);
        $stmtCustomer->execute($customerBind);
    }

    /**
     * ----------------------------------------------------------------------
     * PART 2
     * customer_details akan dimulai dari sini...
     * ----------------------------------------------------------------------
     */

    /**
     * ----------------------------------------------------------------------
     * Dynamic Update Customer Details
     * ----------------------------------------------------------------------
     */

    $detailMap = [

        'email'               => 'email',
        'nik'                 => 'nik',
        'ktp_url'             => 'ktp_url',
        'kk_url'              => 'kk_url',

        'package_external_id' => 'package_external_id',
        'package_name'        => 'package_name',

        'area_external_id'    => 'area_external_id',
        'area_name'           => 'area_name',

        'pop_external_id'     => 'pop_external_id',
        'pop_name'            => 'pop_name',

        'vlan_id'             => 'vlan_id',

        'monthly_bill'        => 'monthly_bill',
        'ikr_fee'             => 'ikr_fee',

        'marital_status'      => 'marital_status',

        'device_brand'        => 'device_brand',
        'modem_sn'            => 'modem_sn',

        'due_day'             => 'due_day',

        'inst_street'         => 'inst_street',
        'inst_rt'             => 'inst_rt',
        'inst_rw'             => 'inst_rw',
        'inst_village'        => 'inst_village',
        'inst_district'       => 'inst_district',
        'inst_city'           => 'inst_city',
        'inst_province'       => 'inst_province',
        'inst_zip'            => 'inst_zip',

        'same_with_installation' => 'same_with_installation',

        'ktp_street'          => 'ktp_street',
        'ktp_rt'              => 'ktp_rt',
        'ktp_rw'              => 'ktp_rw',
        'ktp_village'         => 'ktp_village',
        'ktp_district'        => 'ktp_district',
        'ktp_city'            => 'ktp_city',
        'ktp_province'        => 'ktp_province',
        'ktp_zip'             => 'ktp_zip',

        'gender'              => 'gender',
        'birth_place'         => 'birth_place',
        'birth_date'          => 'birth_date',
        'occupation'          => 'occupation',

        'installed_at'        => 'installed_at'

    ];

    $detailSet = [];

    $detailBind = [
        ':netpay_id' => $netpayId
    ];

    foreach ($detailMap as $payloadField => $column) {

        if (!array_key_exists($payloadField, $data)) {
            continue;
        }

        $value = $data[$payloadField];

        switch ($payloadField) {

            case 'installed_at':

                $value = !empty($value)
                    ? date('Y-m-d H:i:s', strtotime($value))
                    : null;

                break;

            case 'same_with_installation':

                $value = !empty($value) ? 1 : 0;

                break;
        }

        $detailSet[] = "{$column} = :{$column}";
        $detailBind[":{$column}"] = $value;
    }

    /**
     * Metadata Sinkronisasi
     */

    $detailSet[] = "last_source = 'minpo'";
    $detailSet[] = "sync_status = 'synced'";
    $detailSet[] = "minpo_updated_at = NOW()";
    $detailSet[] = "updated_at = NOW()";

    $sqlDetail = "
        UPDATE customer_details
        SET " . implode(", ", $detailSet) . "
        WHERE netpay_id = :netpay_id
    ";

    $stmtDetail = $pdo->prepare($sqlDetail);
    $stmtDetail->execute($detailBind);

    /**
     * ----------------------------------------------------------------------
     * Commit
     * ----------------------------------------------------------------------
     */

    $pdo->commit();

    respond(200, [
        'status' => 'success',
        'message' => 'Customer berhasil diperbarui',
        'netpay_id' => $netpayId
    ]);
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[customer-update] ' . $e->getMessage());

    respond(500, [
        'status' => 'error',
        'message' => 'Gagal memperbarui data customer'
    ]);
}
