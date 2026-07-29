<?php
// pull_from_netpay.php (APPS SERVER - PULLER)
// Jalanin via CLI    : php pull_from_netpay.php
// Cron jam 12 malam  : 0 0 * * * php /path/to/sync/pull_from_netpay.php >> /var/log/sync_netpay.log 2>&1

require_once __DIR__ . "/../../includes/config.php";
include __DIR__ . "/../token/env.php";

/*
|--------------------------------------------------------------------------
| HANYA BOLEH JALAN VIA CLI
|--------------------------------------------------------------------------
*/

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode([
        "status"  => false,
        "message" => "Hanya boleh dijalankan via CLI."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CEGAH DOUBLE RUN (kalau cron overlap)
|--------------------------------------------------------------------------
*/

$lockFile = '/tmp/sync_netpay.lock';

if (file_exists($lockFile)) {
    echo "[" . date('Y-m-d H:i:s') . "] Script sudah berjalan! PID: " . file_get_contents($lockFile) . ". Keluar." . PHP_EOL;
    exit;
}

file_put_contents($lockFile, getmypid());

register_shutdown_function(function () use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

/*
|--------------------------------------------------------------------------
| LOAD ENV
|--------------------------------------------------------------------------
*/

$netpay_api_url = rtrim($env['NETPAY_API_URL'], '/');
$netpay_token   = $env['JBR_CMS_INTEGRATION_TOKEN'];

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$limit   = 500; // Per request ke Netpay (max 1000 sesuai get_customers.php)
$page    = 1;
$hasNext = true;

$totalData   = 0;
$totalInsert = 0;
$totalUpdate = 0;
$totalFailed = 0;
$startedAt   = date('Y-m-d H:i:s');

/*
|--------------------------------------------------------------------------
| LOG HELPER
|--------------------------------------------------------------------------
*/

function writeLog(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}" . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| START
|--------------------------------------------------------------------------
*/

writeLog("===== SYNC NETPAY → APPS DIMULAI =====");

/*
|--------------------------------------------------------------------------
| PULL & UPSERT LOOP
|--------------------------------------------------------------------------
*/

while ($hasNext) {

    writeLog("Fetching page {$page} dari Netpay...");

    /*
    |----------------------------------------------------------------------
    | FETCH DARI NETPAY API
    |----------------------------------------------------------------------
    */

    $url     = "{$netpay_api_url}/get_customers.php?page={$page}&limit={$limit}";

    $context = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'header'        =>
            "Authorization: Bearer {$netpay_token}\r\n" .
                "Content-Type: application/json\r\n",
            'timeout'       => 30,
            'ignore_errors' => true,
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    $httpCode = 0;
    if (isset($http_response_header[0])) {
        preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $m);
        $httpCode = (int)($m[1] ?? 0);
    }

    if ($httpCode !== 200 || $response === false) {
        writeLog("GAGAL fetch page {$page}. HTTP Code: {$httpCode}. Stop.");
        $totalFailed++;
        break;
    }

    $result = json_decode($response, true);

    if (!isset($result['status']) || !$result['status']) {
        writeLog("Response tidak valid di page {$page}. Stop.");
        $totalFailed++;
        break;
    }

    $customers  = $result['data'];
    $pagination = $result['pagination'];
    $hasNext    = (bool)$pagination['has_next'];
    $totalData += count($customers);

    writeLog("Page {$page}/{$pagination['total_page']} → " . count($customers) . " customer diterima.");

    if (count($customers) === 0) {
        $page++;
        continue;
    }

    /*
    |----------------------------------------------------------------------
    | CEK EXISTING (UNTUK HITUNG INSERT VS UPDATE)
    |----------------------------------------------------------------------
    */

    $netpayIds = array_column($customers, 'netpay_id');
    $inClause  = implode(',', array_fill(0, count($netpayIds), '?'));

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE netpay_id IN ({$inClause})");
    $countStmt->execute($netpayIds);

    $existingCount = (int)$countStmt->fetchColumn();
    $willInsert    = count($customers) - $existingCount;
    $willUpdate    = $existingCount;

    /*
    |----------------------------------------------------------------------
    | BULK UPSERT KE APPS DB
    |----------------------------------------------------------------------
    */

    try {

        $placeholders = [];
        $values       = [];

        foreach ($customers as $customer) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            array_push(
                $values,
                $customer['netpay_id'],
                $customer['name'],
                $customer['perumahan'],
                $customer['location'],
                $customer['phone'],
                $customer['paket_internet'],
                $customer['is_active'],
                $customer['harga'],
                $customer['ip']
            );
        }

        $sql = "
            INSERT INTO customers
                (netpay_id, name, perumahan, location, phone, paket_internet, is_active, harga, ip)
            VALUES " . implode(", ", $placeholders) . "
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
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $totalInsert += $willInsert;
        $totalUpdate += $willUpdate;

        writeLog("Page {$page} → Insert: {$willInsert}, Update: {$willUpdate} ✓");
    } catch (Exception $e) {

        writeLog("Page {$page} → Upsert GAGAL: " . $e->getMessage());
        $totalFailed += count($customers);
    }

    $page++;
    usleep(100000); // 0.1 detik jeda antar page
}

/*
|--------------------------------------------------------------------------
| SIMPAN KE SYNC LOG
|--------------------------------------------------------------------------
*/

$finishedAt = date('Y-m-d H:i:s');

$status = 'SUCCESS';
if ($totalFailed > 0 && ($totalInsert + $totalUpdate) === 0) {
    $status = 'FAILED';
} elseif ($totalFailed > 0) {
    $status = 'PARTIAL';
}

try {

    $logStmt = $pdo->prepare("
        INSERT INTO sync_log
            (started_at, finished_at, total_data, total_insert, total_update, total_failed, status)
        VALUES
            (:started_at, :finished_at, :total_data, :total_insert, :total_update, :total_failed, :status)
    ");

    $logStmt->execute([
        ':started_at'   => $startedAt,
        ':finished_at'  => $finishedAt,
        ':total_data'   => $totalData,
        ':total_insert' => $totalInsert,
        ':total_update' => $totalUpdate,
        ':total_failed' => $totalFailed,
        ':status'       => $status,
    ]);
} catch (Exception $e) {
    writeLog("Gagal simpan sync log: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

writeLog("===== SYNC SELESAI =====");
writeLog("Status       : {$status}");
writeLog("Total Data   : {$totalData}");
writeLog("Total Insert : {$totalInsert}");
writeLog("Total Update : {$totalUpdate}");
writeLog("Total Failed : {$totalFailed}");
writeLog("Started At   : {$startedAt}");
writeLog("Finished At  : {$finishedAt}");
