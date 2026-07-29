<?php

require_once __DIR__ . "/../includes/config.php";

/**
 * Generates Google OAuth2 Access Token for Firebase Cloud Messaging (FCM) HTTP v1 API
 * using pure PHP OpenSSL functions (zero external composer dependencies).
 */
function getFirebaseAccessToken(): ?string
{
    static $cachedToken = null;
    static $tokenExpiry = 0;

    if ($cachedToken && time() < $tokenExpiry - 60) {
        return $cachedToken;
    }

    $credFile = __DIR__ . "/../includes/firebase_credentials.json";
    if (!file_exists($credFile)) {
        error_log("[FCM] firebase_credentials.json not found in includes/");
        return null;
    }

    $cred = json_decode(file_get_contents($credFile), true);
    if (!$cred || empty($cred['private_key']) || empty($cred['client_email'])) {
        error_log("[FCM] Invalid firebase_credentials.json content");
        return null;
    }

    $now = time();
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'iss'   => $cred['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now
    ]);

    $base64UrlHeader  = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signatureInput   = $base64UrlHeader . "." . $base64UrlPayload;

    $signature = '';
    $success   = openssl_sign($signatureInput, $signature, $cred['private_key'], 'SHA256');
    if (!$success) {
        error_log("[FCM] Failed to sign JWT via OpenSSL");
        return null;
    }

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $signatureInput . "." . $base64UrlSignature;

    // Post JWT to Google OAuth2 token endpoint
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("[FCM] Failed to obtain OAuth2 token. HTTP $httpCode: $response");
        return null;
    }

    $data = json_decode($response, true);
    if (!empty($data['access_token'])) {
        $cachedToken = $data['access_token'];
        $tokenExpiry = time() + ($data['expires_in'] ?? 3600);
        return $cachedToken;
    }

    return null;
}

/**
 * Sends a single FCM HTTP v1 notification message to a device token.
 */
function sendFcmPush(string $fcmToken, string $title, string $body, array $extraData = []): bool
{
    $accessToken = getFirebaseAccessToken();
    if (!$accessToken) return false;

    $credFile = __DIR__ . "/../includes/firebase_credentials.json";
    $cred = json_decode(file_get_contents($credFile), true);
    $projectId = $cred['project_id'] ?? 'jtracks-c83ff';

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    // Ensure all data attributes are strings for FCM payload compatibility
    $stringData = [];
    foreach ($extraData as $k => $v) {
        $stringData[(string)$k] = (string)$v;
    }

    $message = [
        'message' => [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => $stringData,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; UTF-8'
        ],
        CURLOPT_POSTFIELDS => json_encode($message)
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        error_log("[FCM] Push failed HTTP $code: $res");
        return false;
    }

    return true;
}

/**
 * Dispatches ticket notifications to all technicians in a team ($tim_id).
 * Notif 1: Ticket detail.
 * Notif 2: Summary of total active tickets assigned to that team today (if >= 2).
 */
function sendTeamTicketNotification(PDO $pdo, ?string $tim_id, string $schedule_id): bool
{
    if (empty($tim_id) || empty($schedule_id)) return false;

    // 1. Query ticket & customer details (Support Service & Instalasi/IKR)
    $stmt = $pdo->prepare("
        SELECT
            s.schedule_id,
            s.job_type,
            s.date,
            q.netpay_id,
            COALESCE(NULLIF(TRIM(c.name), ''), reg.name) AS nama_pelanggan,
            COALESCE(
                NULLIF(TRIM(CONCAT_WS(' ', c.perumahan, c.location)), ''),
                CONCAT_WS(' ', reg.perumahan, reg.location)
            ) AS alamat,
            COALESCE(reg.paket_internet, c.paket_internet) AS paket_internet,
            rm.server,
            rm.deskripsi_issue AS aduan,
            ri.catatan AS catatan_ikr
        FROM schedules s
        JOIN queue_scheduling q ON q.queue_id = s.queue_id
        LEFT JOIN customers c ON c.netpay_id = q.netpay_id
        LEFT JOIN request_maintenance rm ON rm.queue_id = s.queue_id
        LEFT JOIN request_ikr ri ON ri.queue_id = s.queue_id
        LEFT JOIN register reg ON reg.registrasi_id = ri.registrasi_id
        WHERE s.schedule_id = :schedule_id
    ");
    $stmt->execute([':schedule_id' => $schedule_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) return false;

    // 2. Query FCM tokens of all technicians belonging to this team ($tim_id)
    $stmtTech = $pdo->prepare("
        SELECT tech_id, name, fcm_token
        FROM technician
        WHERE tim_id = :tim_id AND fcm_token IS NOT NULL AND TRIM(fcm_token) != ''
    ");
    $stmtTech->execute([':tim_id' => $tim_id]);
    $techs = $stmtTech->fetchAll(PDO::FETCH_ASSOC);

    if (empty($techs)) {
        error_log("[FCM] No technician FCM tokens found for tim_id: $tim_id");
        return false;
    }

    // 3. Prepare Notif 1: Detail Tiket Baru (Deteksi Jenis Pekerjaan)
    $isInstall = ($ticket['job_type'] === 'Install' || $ticket['job_type'] === 'Pemasangan' || !empty($ticket['catatan_ikr']));
    $jobLabel  = $isInstall ? 'Pemasangan' : ($ticket['job_type'] ?: 'Service');

    $title1 = "📌 Tugas {$jobLabel} Baru: " . ($ticket['nama_pelanggan'] ?: 'Pelanggan');

    if ($isInstall) {
        $paketStr   = !empty($ticket['paket_internet']) ? "Paket: {$ticket['paket_internet']} Mbps" : "Pemasangan Baru";
        $catatanStr = !empty($ticket['catatan_ikr']) ? " | Ket: {$ticket['catatan_ikr']}" : "";
        $body1      = "ID: {$ticket['netpay_id']} | {$paketStr} | Alamat: " . ($ticket['alamat'] ?: '-') . $catatanStr;
    } else {
        $body1      = "ID: {$ticket['netpay_id']} | Server: " . ($ticket['server'] ?: '-') . " | Alamat: " . ($ticket['alamat'] ?: '-');
    }

    $extraData1 = [
        'schedule_id' => $schedule_id,
        'netpay_id'   => $ticket['netpay_id'] ?? '',
        'job_type'    => $ticket['job_type'] ?? '',
        'type'        => 'new_task',
    ];

    // 4. Query total active tickets assigned to this team today
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*)
        FROM schedules
        WHERE (tim_id = :tim_id OR tech_id = :tim_id)
          AND status IN ('Pending', 'Actived')
          AND date = CURRENT_DATE
    ");
    $stmtCount->execute([':tim_id' => $tim_id]);
    $totalActive = (int) $stmtCount->fetchColumn();

    // 5. Send notifications to each technician's device
    foreach ($techs as $tech) {
        $token = trim($tech['fcm_token']);
        if (!$token) continue;

        // Kirim Notif 1 (Detail Tiket Spesifik)
        sendFcmPush($token, $title1, $body1, $extraData1);

        // Jika total tiket aktif tim >= 2, kirim Notif 2 (Summary Total Tugas)
        if ($totalActive >= 2) {
            $title2 = "📋 Total Tugas Tim: {$totalActive} Tiket Aktif";
            $body2  = "Tim Anda memiliki total {$totalActive} tiket pengerjaan yang harus diselesaikan hari ini.";
            $extraData2 = [
                'tim_id'       => $tim_id,
                'total_active' => (string) $totalActive,
                'type'         => 'task_summary',
            ];
            sendFcmPush($token, $title2, $body2, $extraData2);
        }
    }

    return true;
}
