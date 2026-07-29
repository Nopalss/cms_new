<?php
require_once __DIR__ . '/../../includes/config.php';
$sql = "SELECT * FROM queue_scheduling";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$no = 1;

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Queue_Summary_" . date("d F Y") . ".xls");
?>

<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <center>
        <h2 style="font-family: Arial; margin-bottom: 5px; color:#007bff; font-size:22px;">
            📋 Jabbar23 Queue Summary
        </h2>
    </center>

    <div style="text-align: right; font-family: Arial; font-size:14px; color:#333; margin-right:30px; margin-bottom:10px;">
        <?= date("d F Y"); ?>
    </div>

    <table border="1" cellspacing="0" cellpadding="5" style="font-family: Arial; font-size: 12px; border-collapse: collapse; width:95%; margin:20px auto;">
        <thead style="background:#007bff; color:white; text-align:center;">
            <tr>
                <th>No.</th>
                <th>queue ID</th>
                <th>Type Queue</th>
                <th>Request ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($row as $i => $d): ?>
                <tr style="background: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff'; ?>;">
                    <td><?= $no++; ?></td>
                    <td style="mso-number-format:'\@';">'<?= $d['queue_id']; ?></td> <!-- fix scientific -->
                    <td><?= $d['type_queue']; ?></td>
                    <td><?= $d['request_id']; ?></td>
                    <td><?= $d['status']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>