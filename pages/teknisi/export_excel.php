<?php
require_once __DIR__ . '/../../includes/config.php';

$id = $_GET['id'] ?? null;
$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

if (!$id || !$start || !$end) {
    exit("Invalid Request");
}

try {

    // === GET TECHNICIAN ===
    $stmt = $pdo->prepare("
        SELECT t.*, u.avatar 
        FROM technician t
        JOIN users u ON t.username = u.username
        WHERE t.tech_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$technician) {
        exit("Teknisi tidak ditemukan");
    }

    // === GET REPORT DATA (PAKAI RANGE) ===
    $sql = "
SELECT 
    'Service' as job_type,
    sr.srv_id as report_id,
    sr.tanggal,
    sr.pic,
    s.status,
    c.name as customer_name,
    c.location,
    c.perumahan,
    c.netpay_id,
    c.is_active
FROM service_reports sr
JOIN schedules s ON sr.schedule_key = s.schedule_key
JOIN customers c ON sr.netpay_key = c.netpay_key
WHERE sr.pic LIKE CONCAT('%', :tech_id, '%')
    AND sr.tanggal BETWEEN :start AND :end

UNION ALL

SELECT 
    'Dismantle' as job_type,
    dr.dismantle_id as report_id,
    dr.tanggal,
    dr.pic,
    s.status,
    c.name as customer_name,
    c.location,
    c.perumahan,
    c.netpay_id,
    c.is_active
FROM dismantle_reports dr
JOIN schedules s ON dr.schedule_key = s.schedule_key
JOIN customers c ON dr.netpay_key = c.netpay_key
WHERE dr.pic LIKE CONCAT('%', :tech_id, '%')
    AND dr.tanggal BETWEEN :start AND :end

UNION ALL

SELECT 
    'Instalasi' as job_type,
    i.ikr_id as report_id,
    s.date as tanggal,
    i.pic,
    s.status,
    c.name as customer_name,
    c.location,
    c.perumahan,
    c.netpay_id,
    c.is_active
FROM ikr i
JOIN schedules s ON i.schedule_key = s.schedule_key
JOIN customers c ON s.netpay_key = c.netpay_key
WHERE i.pic LIKE CONCAT('%', :tech_id, '%')
    AND s.date BETWEEN :start AND :end

ORDER BY tanggal DESC
";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tech_id' => $id,
        ':start'   => $start,
        ':end'     => $end
    ]);

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==== HITUNG TOTAL ====
    $total = count($reports);
    $service = 0;
    $dismantle = 0;
    $instalasi = 0;

    foreach ($reports as $r) {
        if ($r['job_type'] == 'Service') $service++;
        if ($r['job_type'] == 'Dismantle') $dismantle++;
        if ($r['job_type'] == 'Instalasi') $instalasi++;
    }
} catch (PDOException $e) {
    exit($e->getMessage());
}

// === FORMAT PERIODE ===
$periode_text = date('d M Y', strtotime($start)) . ' - ' . date('d M Y', strtotime($end));

// === HEADER EXCEL ===
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Teknisi_{$technician['name']}_" . date('Ymd', strtotime($start)) . "_" . date('Ymd', strtotime($end)) . ".xls");

?>

<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            padding: 10px 0;
        }

        .subtitle {
            font-size: 18px;
            color: #555;
        }

        .summary-box {
            background: #f3f6f9;
            padding: 8px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background-color: #1f2937;
            color: white;
            padding: 8px;
            text-align: center;
        }

        td {
            padding: 6px;
            border: 1px solid #ddd;
        }

        .badge-service {
            background-color: #e0f2fe;
        }

        .badge-dismantle {
            background-color: #fee2e2;
        }

        .badge-instalasi {
            background-color: #dcfce7;
        }
    </style>
</head>

<body>

    <div class="title">LAPORAN PEKERJAAN TEKNISI</div>
    <div class="subtitle">
        Nama Teknisi: <strong><?= $technician['name'] ?></strong><br>
        ID Teknisi: <strong><?= $technician['tech_id'] ?></strong><br>
        Periode: <strong><?= $periode_text ?></strong>
    </div>

    <br>

    <table width="50%">
        <tr class="summary-box">
            <td><strong>Total Pekerjaan</strong></td>
            <td><?= $total ?></td>
        </tr>
        <tr class="summary-box">
            <td><strong>Total Service</strong></td>
            <td><?= $service ?></td>
        </tr>
        <tr class="summary-box">
            <td><strong>Total Dismantle</strong></td>
            <td><?= $dismantle ?></td>
        </tr>
        <tr class="summary-box">
            <td><strong>Total Instalasi</strong></td>
            <td><?= $instalasi ?></td>
        </tr>
    </table>

    <br><br>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Report ID</th>
                <th>Netpay ID</th>
                <th>Nama Customer</th>
                <th>Perumahan</th>
                <th>Alamat</th>
                <th>PIC</th>
                <th>Status Schedule</th>
                <th>Status Customer</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($reports as $r): ?>
                <tr class="<?=
                            $r['job_type'] == 'Service' ? 'badge-service' : ($r['job_type'] == 'Dismantle' ? 'badge-dismantle' : 'badge-instalasi')
                            ?>">
                    <td><?= date('d M Y', strtotime($r['tanggal']))  ?></td>
                    <td><?= $r['job_type'] ?></td>
                    <td><?= $r['report_id'] ?></td>
                    <td><?= $r['netpay_id'] ?></td>
                    <td><?= $r['customer_name'] ?></td>
                    <td><?= $r['perumahan'] ?></td>

                    <?php
                    $picNames = [];

                    if (!empty($r['pic'])) {
                        $picIds = explode(',', $r['pic']);
                        $in = implode(',', array_fill(0, count($picIds), '?'));

                        $q = $pdo->prepare("
        SELECT name 
        FROM technician
        WHERE tech_id IN ($in)
    ");

                        $q->execute($picIds);
                        $picNames = $q->fetchAll(PDO::FETCH_COLUMN);
                    }

                    $picDisplay = $picNames ? implode(', ', $picNames) : '-';
                    ?>
                    <td><?= $r['location'] ?></td>
                    <td><?= $picDisplay ?></td>
                    <td><?= $r['status'] ?></td>
                    <td><?= $r['is_active'] ?></td>
                </tr>
            <?php endforeach; ?>

        </tbody>
    </table>

</body>

</html>