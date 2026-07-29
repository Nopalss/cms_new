<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Tambahkan fallback untuk PHP < 8
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

// Database config
$db_host = "127.0.0.1"; // gunakan IP supaya pasti konek TCP
$db_user = "root";
// $db_pass = "123qwe";
$db_pass = "";
$db_name = "cms_database";

// Deteksi apakah HTTPS atau HTTP
$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
);
$protocol = $isHttps ? 'https://' : 'http://';

// Ambil host (misalnya localhost, 103.147.82.5, atau domain)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Ambil folder project (misalnya /cms)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$parts = explode('/', trim($scriptName, '/'));
$projectFolder = isset($parts[0]) ? '/' . $parts[0] : '';

// Gabungkan semuanya
$baseUrl = $protocol . $host . $projectFolder . '/';

// Definisikan BASE_URL
define('BASE_URL', $baseUrl);


// API Token & dummy users
define('NETPAY_API_TOKEN', '14c7585632f6bdd11c25f0ed5f40a3b0');

// Database connection
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

/**
 * Helper to check if rating feature is enabled in DB settings
 */
function isRatingEnabled($pdo) {
    static $enabled = null;
    if ($enabled !== null) return $enabled;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'enable_rating'");
        $stmt->execute();
        $val = $stmt->fetchColumn();

        if ($val === false) {
            $pdo->exec("INSERT INTO app_settings (setting_key, setting_value) VALUES ('enable_rating', '1')");
            $enabled = true;
        } else {
            $enabled = ($val == '1' || strtolower($val) === 'true');
        }
    } catch (Exception $e) {
        $enabled = true;
    }

    return $enabled;
}

/**
 * Get daily shift team for a team or technician (Zero Bloat: 1 row per team)
 */
function getDailyShiftTeam($pdo, $tim_id = '', $tech_id = '') {
    $targetKey = !empty($tim_id) ? $tim_id : $tech_id;
    if (empty($targetKey)) return [];

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_shift_team (
            tim_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            member_tech_ids TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $stmt = $pdo->prepare("SELECT date, member_tech_ids FROM daily_shift_team WHERE tim_id = :tim_id");
        $stmt->execute([':tim_id' => $targetKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['date'] === date('Y-m-d')) {
            $ids = json_decode($row['member_tech_ids'], true);
            return is_array($ids) ? $ids : [];
        }
    } catch (Exception $e) {
        // Fallback
    }

    return [];
}

/**
 * Save daily shift team for a team or technician
 */
function saveDailyShiftTeam($pdo, $tim_id = '', $tech_id = '', array $member_tech_ids = []) {
    $targetKey = !empty($tim_id) ? $tim_id : $tech_id;
    if (empty($targetKey)) return false;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_shift_team (
            tim_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            member_tech_ids TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $json = json_encode(array_values(array_unique($member_tech_ids)));
        $stmt = $pdo->prepare("
            INSERT INTO daily_shift_team (tim_id, date, member_tech_ids)
            VALUES (:tim_id, CURDATE(), :json)
            ON DUPLICATE KEY UPDATE
                date = CURDATE(),
                member_tech_ids = VALUES(member_tech_ids),
                updated_at = NOW()
        ");
        return $stmt->execute([
            ':tim_id' => $targetKey,
            ':json'   => $json
        ]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get daily schedule task claims (Zero Waste: 1 row per schedule)
 */
function getTaskClaims($pdo, $tim_id = '', $date = null) {
    if (!$date) $date = date('Y-m-d');
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_task_claims (
            schedule_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            tim_id VARCHAR(50) NOT NULL,
            claimed_by_tech_id VARCHAR(50) NOT NULL,
            claimed_by_name VARCHAR(100) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date_tim (date, tim_id)
        )");

        $sql = "SELECT schedule_id, claimed_by_tech_id, claimed_by_name, tim_id FROM schedule_task_claims WHERE date = :date";
        $params = [':date' => $date];
        if (!empty($tim_id)) {
            $sql .= " AND tim_id = :tim_id";
            $params[':tim_id'] = $tim_id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[$r['schedule_id']] = $r;
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Save daily schedule task claims for a technician
 */
function saveTaskClaims($pdo, $tech_id, $tech_name, $tim_id, array $claimed_schedule_ids = [], array $unclaimed_schedule_ids = []) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schedule_task_claims (
            schedule_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            tim_id VARCHAR(50) NOT NULL,
            claimed_by_tech_id VARCHAR(50) NOT NULL,
            claimed_by_name VARCHAR(100) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date_tim (date, tim_id)
        )");

        if (!empty($claimed_schedule_ids)) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO schedule_task_claims (schedule_id, date, tim_id, claimed_by_tech_id, claimed_by_name)
                VALUES (:schedule_id, CURDATE(), :tim_id, :tech_id, :tech_name)
                ON DUPLICATE KEY UPDATE
                    date = CURDATE(),
                    tim_id = VALUES(tim_id),
                    claimed_by_tech_id = VALUES(claimed_by_tech_id),
                    claimed_by_name = VALUES(claimed_by_name),
                    updated_at = NOW()
            ");

            foreach ($claimed_schedule_ids as $sid) {
                if (empty($sid)) continue;
                $stmtInsert->execute([
                    ':schedule_id' => $sid,
                    ':tim_id'      => $tim_id,
                    ':tech_id'     => $tech_id,
                    ':tech_name'   => $tech_name,
                ]);
            }
        }

        if (!empty($unclaimed_schedule_ids)) {
            $placeholders = implode(',', array_fill(0, count($unclaimed_schedule_ids), '?'));
            $stmtDel = $pdo->prepare("DELETE FROM schedule_task_claims WHERE schedule_id IN ($placeholders)");
            $stmtDel->execute(array_values($unclaimed_schedule_ids));
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
