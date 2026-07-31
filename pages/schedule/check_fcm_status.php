<?php
require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../helper/fcm_helper.php";

header('Content-Type: text/html; charset=utf-8');

$credFile = __DIR__ . "/../../includes/firebase_credentials.json";
$credExists = file_exists($credFile);
$credValid = false;
$projectId = '-';

if ($credExists) {
    $cred = json_decode(file_get_contents($credFile), true);
    if ($cred && !empty($cred['private_key']) && !empty($cred['client_email'])) {
        $credValid = true;
        $projectId = $cred['project_id'] ?? 'jtracks-c83ff';
    }
}

$tokenError = null;
$oauthToken = null;
if ($credValid) {
    try {
        $oauthToken = getFirebaseAccessToken($tokenError);
    } catch (Exception $e) {
        $tokenError = $e->getMessage();
    }
}

// Query all technicians and their fcm_token status
$stmt = $pdo->query("SELECT tech_id, name, tim_id, fcm_token FROM technician ORDER BY name ASC");
$techs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Test Send
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_tech_id'])) {
    $targetTechId = $_POST['test_tech_id'];
    $stmtT = $pdo->prepare("SELECT tech_id, name, fcm_token FROM technician WHERE tech_id = :id");
    $stmtT->execute([':id' => $targetTechId]);
    $target = $stmtT->fetch(PDO::FETCH_ASSOC);
    
    if ($target && !empty($target['fcm_token'])) {
        $pushErr = null;
        $res = sendFcmPush($target['fcm_token'], "🔔 TES NOTIFIKASI FCM", "Halo {$target['name']}, notifikasi push FCM dari CMS Jabbar berhasil terhubung!", [
            'type' => 'test_push'
        ], $pushErr);
        $testResult = $res ? "SUCCESS: Notifikasi tes berhasil terkirim ke {$target['name']}!" : "FAILED: " . ($pushErr ?: 'Pengiriman FCM gagal');
    } else {
        $testResult = "FAILED: Teknisi {$target['name']} belum memiliki FCM Token di database.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Diagnosa FCM Notifikasi</title>
<style>
body { font-family: sans-serif; padding: 20px; background: #f8fafc; color: #1e293b; max-width: 900px; margin: 0 auto; }
.card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
.badge-success { background: #dcfce7; color: #166534; }
.badge-danger { background: #fee2e2; color: #991b1b; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; border: 1px solid #e2e8f0; text-align: left; font-size: 13px; }
th { background: #f1f5f9; }
button { background: #2563eb; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
</style>
</head>
<body>
<h2>🔍 Diagnosa FCM Push Notification</h2>

<?php if ($testResult): ?>
<div class="card" style="background: #eff6ff; border-left: 4px solid #2563eb;">
    <strong>Hasil Tes:</strong> <?= htmlspecialchars($testResult) ?>
</div>
<?php endif; ?>

<div class="card">
    <h3>1. Status Konfigurasi Firebase</h3>
    <p><strong>File firebase_credentials.json:</strong> 
        <?= $credExists ? '<span class="badge badge-success">ADA</span>' : '<span class="badge badge-danger">TIDAK ADA</span>' ?>
    </p>
    <p><strong>Status Kunci/Credentials:</strong> 
        <?= $credValid ? '<span class="badge badge-success">VALID</span> (Project ID: '.htmlspecialchars($projectId).')' : '<span class="badge badge-danger">INVALID / KOSONG</span>' ?>
    </p>
    <p><strong>Token OAuth2 Google:</strong> 
        <?= $oauthToken ? '<span class="badge badge-success">BERHASIL DITERIMA</span>' : '<span class="badge badge-danger">GAGAL (' . htmlspecialchars($tokenError ?: 'Cek log server') . ')</span>' ?>
    </p>
</div>

<div class="card">
    <h3>2. Status FCM Token Teknisi di Database</h3>
    <table>
        <thead>
            <tr>
                <th>ID Teknisi</th>
                <th>Nama Teknisi</th>
                <th>ID Tim</th>
                <th>Status FCM Token</th>
                <th>Aksi Tes</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($techs as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['tech_id']) ?></td>
                <td><?= htmlspecialchars($t['name']) ?></td>
                <td><?= htmlspecialchars($t['tim_id'] ?: '-') ?></td>
                <td>
                    <?php if (!empty($t['fcm_token'])): ?>
                        <span class="badge badge-success">TERSEDIA (<?= strlen($t['fcm_token']) ?> karakter)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">BELUM ADA TOKEN (KOSONG)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($t['fcm_token'])): ?>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="test_tech_id" value="<?= htmlspecialchars($t['tech_id']) ?>">
                            <button type="submit">🚀 Kirim Tes Push</button>
                        </form>
                    <?php else: ?>
                        <em style="color:#94a3b8; font-size:12px;">Minta teknisi login & izinkan notif di HP/Browser</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
