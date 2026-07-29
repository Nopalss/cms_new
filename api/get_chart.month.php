<?php
require_once __DIR__ . "/../includes/config.php";
header('Content-Type: application/json');

$tahun = date('Y');

function getMonthData($pdo, $table, $tahun)
{
    $sql = "SELECT MONTH(created_at) AS bulan, COUNT(*) AS total
            FROM $table
            WHERE YEAR(created_at) = :tahun
            GROUP BY MONTH(created_at)
            ORDER BY bulan ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['tahun' => $tahun]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$ikr       = getMonthData($pdo, "ikr_report", $tahun);
$service   = getMonthData($pdo, "service_reports", $tahun);
$dismantle = getMonthData($pdo, "dismantle_reports", $tahun);

// Buat label bulan (Jan–Dec)
$labels = [];
for ($m = 1; $m <= 12; $m++) {
    $labels[] = date('M', mktime(0, 0, 0, $m, 1));
}

function fillZero($labels, $data)
{
    $map = [];
    foreach ($data as $row) {
        $map[(int)$row['bulan']] = (int)$row['total'];
    }
    $result = [];
    for ($m = 1; $m <= 12; $m++) {
        $result[] = $map[$m] ?? 0;
    }
    return $result;
}

echo json_encode([
    'labels'     => $labels,
    'ikr'        => fillZero($labels, $ikr),
    'service'    => fillZero($labels, $service),
    'dismantle'  => fillZero($labels, $dismantle),
    'tahun'      => $tahun
]);
