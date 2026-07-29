<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/config.php";


$bulan = date('n'); // default bulan sekarang
$tahun = date('Y');

$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM ikr_report WHERE MONTH(created_at)=$bulan AND YEAR(created_at)=$tahun) AS ikr,
        (SELECT COUNT(*) FROM service_reports WHERE MONTH(created_at)=$bulan AND YEAR(created_at)=$tahun) AS service,
        (SELECT COUNT(*) FROM dismantle_reports WHERE MONTH(created_at)=$bulan AND YEAR(created_at)=$tahun) AS dismantle
");
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'series' => [
        (int)$data['ikr'],
        (int)$data['service'],
        (int)$data['dismantle']
    ],
    'labels' => ['IKR', 'Service', 'Dismantle']
]);
