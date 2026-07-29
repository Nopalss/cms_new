<?php
// push_to_minpro.php (APPS SERVER - PUSHER KE MINPRO)
// Jalanin via CLI     : php push_to_minpro.php
// Cron jam 00:30      : 30 0 * * * php /path/to/sync/push_to_minpro.php >> /var/log/sync_minpro.log 2>&1

require_once __DIR__ . "/../../includes/config.php";
include __DIR__ . "/../token/env.php";

/*
|--------------------------------------------------------------------------
| HANYA BOLEH JALAN VIA CLI
|--------------------------------------------------------------------------
*/

// if (php_sapi_name() !== 'cli') {
//     http_response_code(403);
//     echo json_encode([
//         "status"  => false,
//         "message" => "Hanya boleh dijalankan via CLI."
//     ]);
//     exit;
// }

/*
|--------------------------------------------------------------------------
| CEGAH DOUBLE RUN
|--------------------------------------------------------------------------
*/

$lockFile = sys_get_temp_dir() . '/push_minpo.lock';

if (file_exists($lockFile)) {
    echo "[" . date('Y-m-d H:i:s') . "] Script sudah berjalan! PID: " . file_get_contents($lockFile) . ". Keluar." . PHP_EOL;
    exit;
}

file_put_contents($lockFile, getmypid());

register_shutdown_function(function () use ($lockFile) {
    if (file_exists($lockFile)) unlink($lockFile);
});

/*
|--------------------------------------------------------------------------
| LOAD ENV
|--------------------------------------------------------------------------
*/

$minpro_api_url = rtrim($env['JBR_MINPO_API_URL'], '/');
$minpro_token   = $env['JBR_MINPO_INTEGRATION_TOKEN'];

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$batchSize = 50;
$pageSize  = 500;
$maxRetry  = 3;

$startedAt   = date('Y-m-d H:i:s');
$totalData   = 0;
$totalSuccess = 0;
$totalFailed  = 0;
$batchNumber  = 0;

/*
|--------------------------------------------------------------------------
| LOG HELPER
|--------------------------------------------------------------------------
*/

function writeLog(string $message): void
{
    echo "[" . date('Y-m-d H:i:s') . "] {$message}" . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| START
|--------------------------------------------------------------------------
*/

writeLog("===== PUSH APPS → MINPRO DIMULAI =====");

/*
|--------------------------------------------------------------------------
| AMBIL TOTAL DATA
|--------------------------------------------------------------------------
*/

$totalResult = $pdo->query("SELECT COUNT(*) as total FROM customers WHERE is_active = 'ACTIVE'");
$totalData   = (int)$totalResult->fetchColumn();
$totalPage   = (int)ceil($totalData / $pageSize);

writeLog("Total customer aktif: {$totalData}, akan diproses {$totalPage} page.");

/*
|--------------------------------------------------------------------------
| PAGINATION LOOP
|--------------------------------------------------------------------------
*/

$offset = 0;

while ($offset < $totalData) {

    /*
    |----------------------------------------------------------------------
    | AMBIL DATA DARI APPS DB (JOIN customers + ikr)
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            c.netpay_id,
            c.name,
            c.phone,
            c.paket_internet,
            c.harga,
            c.is_active,

            i.alamat,
            i.rt,
            i.rw,
            i.desa,
            i.kec,
            i.kab,
            i.sn          as modem_sn,
            i.type_ont    as device_brand,
            i.ikr_id

        FROM customers c
        LEFT JOIN ikr_report i ON i.netpay_id = c.netpay_id
        WHERE c.is_active = 'ACTIVE'
        LIMIT :limit OFFSET :offset
    ");

    $stmt->execute([
        ':limit'  => $pageSize,
        ':offset' => $offset,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) break;

    $offset += $pageSize;

    /*
    |----------------------------------------------------------------------
    | MAP DATA KE FORMAT MINPRO
    | Field yang belum ada di DB → null
    |----------------------------------------------------------------------
    */

    $customers = [];

    foreach ($rows as $row) {
        $customers[] = [
            // ✅ Ada di DB
            "customer_id"          => $row['netpay_id'],
            "full_name"            => $row['name'],
            "phone"                => $row['phone'],
            "monthly_bill"         => (int)$row['harga'],
            "package_name"         => $row['paket_internet'],
            "activation_status"    => strtolower($row['is_active']),
            "status"               => strtolower($row['is_active']),

            // ✅ Ada di DB (dari ikr, bisa null kalau belum ada data ikr)
            "inst_street"          => $row['alamat']       ?? null,
            "inst_rt"              => $row['rt']           ?? null,
            "inst_rw"              => $row['rw']           ?? null,
            "inst_village"         => $row['desa']         ?? null,
            "inst_district"        => $row['kec']          ?? null,
            "inst_city"            => $row['kab']          ?? null,
            "modem_sn"             => $row['modem_sn']     ?? null,
            "device_brand"         => $row['device_brand'] ?? null,

            // ❌ Belum ada di DB → null dulu, nanti disesuaikan
            "email"                => null,
            "nik"                  => null,
            "ktp_url"              => null,
            "kk_url"               => null,
            "package_external_id"  => null,
            "area_external_id"     => null,
            "area_name"            => null,
            "pop_external_id"      => null,
            "pop_name"             => null,
            "vlan_id"              => null,
            "ikr_fee"              => null,
            "marital_status"       => null,
            "installed_at"         => null,
            "due_day"              => null,
            "inst_province"        => null,
            "inst_zip"             => null,
            "same_with_installation" => null,
            "ktp_street"           => null,
            "ktp_rt"               => null,
            "ktp_rw"               => null,
            "ktp_village"          => null,
            "ktp_district"         => null,
            "ktp_city"             => null,
            "ktp_province"         => null,
            "ktp_zip"              => null,
            "gender"               => null,
            "birth_place"          => null,
            "birth_date"           => null,
            "occupation"           => null,
            "va_mandiri"           => null,
            "va_bri"               => null,
        ];
    }

    /*
    |----------------------------------------------------------------------
    | BATCH & KIRIM KE MINPRO
    |----------------------------------------------------------------------
    */

    $batches    = array_chunk($customers, $batchSize);
    $totalBatch = count($batches);

    foreach ($batches as $index => $batch) {

        $batchNumber++;
        $attempt  = 0;
        $success  = false;
        $lastResp = null;
        $httpCode = 0;

        /*
        |------------------------------------------------------------------
        | RETRY LOOP
        |------------------------------------------------------------------
        */

        while ($attempt < $maxRetry && !$success) {

            $attempt++;
            $payload = json_encode($batch);

            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        =>
                    "Content-Type: application/json\r\n" .
                        "Authorization: Bearer {$minpro_token}\r\n" .
                        "Content-Length: " . strlen($payload) . "\r\n",
                    'content'       => $payload,
                    'timeout'       => 120,
                    'ignore_errors' => true,
                ]
            ]);

            $response = @file_get_contents(
                $minpro_api_url . "/customers/bulk",
                false,
                $context
            );

            $httpCode = 0;
            if (isset($http_response_header[0])) {
                preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $m);
                $httpCode = (int)($m[1] ?? 0);
            }

            $lastResp = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                $success = true;
            } else {
                writeLog("Batch {$batchNumber} attempt {$attempt} gagal (HTTP {$httpCode}), retry...");
                usleep(500000 * $attempt); // 0.5s, 1s, 1.5s
            }
        }

        if ($success) {
            $totalSuccess++;
            writeLog("Batch {$batchNumber}/{$totalBatch} → OK (HTTP {$httpCode}, attempt {$attempt})");
        } else {
            $totalFailed++;
            writeLog("Batch {$batchNumber}/{$totalBatch} → GAGAL setelah {$maxRetry}x retry. HTTP: {$httpCode}");
        }

        usleep(300000); // 0.2 detik jeda antar batch
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN KE SYNC LOG
|--------------------------------------------------------------------------
*/

$finishedAt = date('Y-m-d H:i:s');
$status     = $totalFailed === 0 ? 'SUCCESS' : ($totalSuccess > 0 ? 'PARTIAL' : 'FAILED');

try {
    $logStmt = $pdo->prepare("
        INSERT INTO sync_log
            (sync_name, started_at, finished_at, total_data, total_success_batch, total_failed_batch, status)
        VALUES
            (:sync_name, :started_at, :finished_at, :total_data, :total_success, :total_failed, :status)
    ");

    $logStmt->execute([
        ':sync_name'     => 'apps_to_minpro',
        ':started_at'    => $startedAt,
        ':finished_at'   => $finishedAt,
        ':total_data'    => $totalData,
        ':total_success' => $totalSuccess,
        ':total_failed'  => $totalFailed,
        ':status'        => $status,
    ]);
} catch (Exception $e) {
    writeLog("Gagal simpan sync log: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

writeLog("===== PUSH SELESAI =====");
writeLog("Status          : {$status}");
writeLog("Total Customer  : {$totalData}");
writeLog("Total Batch OK  : {$totalSuccess}");
writeLog("Total Batch FAIL: {$totalFailed}");
writeLog("Started At      : {$startedAt}");
writeLog("Finished At     : {$finishedAt}");
