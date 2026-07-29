<?php
require_once __DIR__ . '/../../includes/config.php';
$sql = "SELECT s.*, c.name, c.location, c.netpay_id 
        FROM dismantle_reports s  
        JOIN customers c ON s.netpay_key = c.netpay_key";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$service = $stmt->fetchAll(PDO::FETCH_ASSOC);
$no = 1;

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Dismantle_Summary_" . date("d F Y") . ".xls");
?>

<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <center>
        <h2 style="font-family: Arial; margin-bottom: 5px; color:#007bff; font-size:22px;">
            📋 Jabbar23 Dismantle Summary
        </h2>
    </center>

    <div style="text-align: right; font-family: Arial; font-size:14px; color:#333; margin-right:30px; margin-bottom:10px;">
        <?= date("d F Y"); ?>
    </div>

    <table border="1" cellspacing="0" cellpadding="5" style="font-family: Arial; font-size: 12px; border-collapse: collapse; width:95%; margin:20px auto;">
        <thead style="background:#007bff; color:white; text-align:center;">
            <tr>
                <th>No.</th>
                <th>Dismantle ID</th>
                <th>Netpay ID</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Alasan</th>
                <th>Action</th>
                <th>Part Removed</th>
                <th>Kondisi Perangkat</th>
                <th>PIC</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($service as $i => $d): ?>
                <tr style="background: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff'; ?>;">
                    <td><?= $no++; ?></td>
                    <td><?= $d['dismantle_id']; ?></td>
                    <td style="mso-number-format:'\@';">'<?= $d['netpay_id']; ?></td> <!-- fix scientific -->
                    <td><?= $d['tanggal']; ?></td>
                    <td><?= $d['jam']; ?></td>
                    <td><?= $d['name']; ?></td>
                    <td><?= $d['location']; ?></td>
                    <td><?= $d['alasan']; ?></td>
                    <td><?= $d['action']; ?></td>
                    <td><?= $d['part_removed']; ?></td>
                    <td><?= $d['kondisi_perangkat']; ?></td>
                    <td><?= $d['pic']; ?></td>
                    <td><?= $d['keterangan']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>