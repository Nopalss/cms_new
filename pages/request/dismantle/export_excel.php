<?php
require_once __DIR__ . '/../../../includes/config.php';

$sql = "SELECT r.*, c.name FROM request_dismantle r JOIN customers c ON r.netpay_key = c.netpay_key";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$no = 1;

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Request_Dismantle_Summary_" . date("d F Y") . ".xls");
?>

<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <center>
        <h2 style="font-family: Arial; margin-bottom: 5px; color:#007bff; font-size:22px;">
            📋 Jabbar23 Request Dismantle Summary
        </h2>
    </center>

    <div style="text-align: right; font-family: Arial; font-size:14px; color:#333; margin-right:30px; margin-bottom:10px;">
        <?= date("d F Y"); ?>
    </div>

    <table border="1" cellspacing="0" cellpadding="5" style="font-family: Arial; font-size: 12px; border-collapse: collapse; width:95%; margin:20px auto;">
        <thead style="background:#007bff; color:white; text-align:center;">
            <tr>
                <th>No.</th>
                <th>RD ID</th>
                <th>Netpay ID</th>
                <th>Name</th>
                <th>Type Dismantle</th>
                <th>Deskripsi Dismantle</th>
                <th>Request By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($row as $i => $d): ?>
                <tr style="background: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff'; ?>;">
                    <td><?= $no++; ?></td>
                    <td><?= $d['rd_id']; ?></td>
                    <td style="mso-number-format:'\@';">'<?= $d['netpay_id']; ?></td> <!-- fix scientific -->
                    <td><?= $d['name']; ?></td>
                    <td><?= $d['type_dismantle']; ?></td>
                    <td><?= $d['deskripsi_dismantle']; ?></td>
                    <td><?= $d['request_by']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>