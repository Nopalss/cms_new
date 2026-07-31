<?php
if (!ini_get('zlib.output_compression') && !headers_sent()) {
    @ini_set('zlib.output_compression', 'On');
}

session_start();
date_default_timezone_set('Asia/Jakarta');

// Helper asset versioning (Cache Busting)
if (!function_exists('asset_ver')) {
    function asset_ver($relativePath)
    {
        $cleanPath = ltrim($relativePath, '/');
        $fullPath  = __DIR__ . '/../' . $cleanPath;
        if (file_exists($fullPath)) {
            return BASE_URL . $cleanPath . '?v=' . filemtime($fullPath);
        }
        return BASE_URL . $cleanPath;
    }
}

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
 * Get daily shift team for a team or technician (per-technician state)
 */
function getDailyShiftTeam($pdo, $tim_id = '', $tech_id = '') {
    $targetKey = !empty($tech_id) ? $tech_id : $tim_id;
    if (empty($targetKey)) return [];

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_shift_team (
            tech_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            member_tech_ids TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Query direct record for logged-in tech_id
        $stmt = $pdo->prepare("SELECT date, member_tech_ids FROM daily_shift_team WHERE tech_id = :tech_id");
        $stmt->execute([':tech_id' => $targetKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['date'] === date('Y-m-d')) {
            $ids = json_decode($row['member_tech_ids'], true);
            if (is_array($ids) && !empty($ids)) {
                return $ids;
            }
        }
    } catch (Exception $e) {
        // Fallback
    }

    return [];
}

/**
 * Save daily shift team for a technician and sync/clean up for all team partners
 */
function saveDailyShiftTeam($pdo, $tim_id = '', $tech_id = '', array $member_tech_ids = []) {
    $targetKey = !empty($tech_id) ? $tech_id : $tim_id;
    if (empty($targetKey)) return false;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_shift_team (
            tech_id VARCHAR(50) NOT NULL PRIMARY KEY,
            date DATE NOT NULL,
            member_tech_ids TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // 1. Fetch old members saved for targetKey today
        $stmtOld = $pdo->prepare("SELECT member_tech_ids FROM daily_shift_team WHERE tech_id = :tech_id AND date = CURDATE()");
        $stmtOld->execute([':tech_id' => $targetKey]);
        $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $oldMembers = ($oldRow && !empty($oldRow['member_tech_ids'])) ? json_decode($oldRow['member_tech_ids'], true) : [];
        if (!is_array($oldMembers)) $oldMembers = [];

        // 2. Prepare new members array
        $cleanMembers = array_values(array_unique(array_filter($member_tech_ids)));
        if (!in_array($targetKey, $cleanMembers, true)) {
            $cleanMembers[] = $targetKey;
        }
        $json = json_encode($cleanMembers);

        // 3. Find removed members (members in old team but NOT in new team)
        $removedMembers = array_diff($oldMembers, $cleanMembers);

        // 4. Delete/clean shift team and task claims for all removed members
        if (!empty($removedMembers)) {
            $stmtDelRemoved = $pdo->prepare("DELETE FROM daily_shift_team WHERE tech_id = :tech_id AND date = CURDATE()");
            $stmtDelTaskClaims = $pdo->prepare("DELETE FROM schedule_task_claims WHERE claimed_by_tech_id = :tech_id AND date = CURDATE()");
            foreach ($removedMembers as $remId) {
                if (!empty($remId) && $remId !== $targetKey) {
                    $stmtDelRemoved->execute([':tech_id' => $remId]);
                    $stmtDelTaskClaims->execute([':tech_id' => $remId]);
                }
            }
        }

        // 5. Upsert new team for targetKey AND all new partners
        $stmtUpsert = $pdo->prepare("
            INSERT INTO daily_shift_team (tech_id, date, member_tech_ids)
            VALUES (:tech_id, CURDATE(), :json)
            ON DUPLICATE KEY UPDATE
                date = CURDATE(),
                member_tech_ids = VALUES(member_tech_ids),
                updated_at = NOW()
        ");

        foreach ($cleanMembers as $mId) {
            if (empty($mId)) continue;
            $stmtUpsert->execute([
                ':tech_id' => $mId,
                ':json'    => $json
            ]);
        }

        return true;
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

        $teamMembers = getDailyShiftTeam($pdo, $tim_id, $tech_id);
        if (empty($teamMembers)) $teamMembers = [$tech_id];

        if (!empty($claimed_schedule_ids)) {
            $stmtCheck = $pdo->prepare("SELECT claimed_by_tech_id FROM schedule_task_claims WHERE schedule_id = :sid AND date = CURDATE()");
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

                // Check if task is already claimed by someone outside current shift team
                $stmtCheck->execute([':sid' => $sid]);
                $existingClaim = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($existingClaim && !in_array($existingClaim['claimed_by_tech_id'], $teamMembers, true)) {
                    continue; // Skip: task belongs to another team!
                }

                $stmtInsert->execute([
                    ':schedule_id' => $sid,
                    ':tim_id'      => $tim_id,
                    ':tech_id'     => $tech_id,
                    ':tech_name'   => $tech_name,
                ]);
            }
        }

        if (!empty($unclaimed_schedule_ids)) {
            $teamMembers = getDailyShiftTeam($pdo, $tim_id, $tech_id);
            if (empty($teamMembers)) $teamMembers = [$tech_id];

            $inTechs = implode(',', array_fill(0, count($teamMembers), '?'));
            $inSids  = implode(',', array_fill(0, count($unclaimed_schedule_ids), '?'));

            $stmtDel = $pdo->prepare("
                DELETE FROM schedule_task_claims 
                WHERE schedule_id IN ($inSids) 
                  AND claimed_by_tech_id IN ($inTechs)
            ");
            $paramsDel = array_merge(array_values($unclaimed_schedule_ids), array_values($teamMembers));
            $stmtDel->execute($paramsDel);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}
