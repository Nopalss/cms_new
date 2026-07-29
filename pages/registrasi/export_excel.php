<?php
// 1. Mulai buffering biar aman dari spasi kosong
ob_start();

require_once __DIR__ . '/../../includes/config.php';

$sql = "SELECT * FROM register ORDER BY registrasi_id DESC"; // Bagus diurutkan
$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$no = 1;

// 2. Bersihkan buffer sebelum kirim header (PENTING BIAR GK CORRUPT)
ob_end_clean();

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Register_Summary_" . date("d-m-Y_H-i") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        /* CSS biar tabelnya rapi di Excel */
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background-color: #007bff;
            color: white;
            height: 30px;
            vertical-align: middle;
        }

        td {
            vertical-align: middle;
        }

        /* Class khusus untuk memaksa jadi Teks (biar nol gak hilang) */
        .text-format {
            mso-number-format: '\@';
        }
    </style>
</head>

<body>
    <center>
        <h2 style="font-family: Arial; color:#007bff;">
            📋 Jabbar23 Register Summary
        </h2>
        <small>Export Date: <?= date("d F Y H:i:s"); ?></small>
    </center>
    <br>

    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th width="50">No.</th>
                <th width="150">Registrasi ID</th>
                <th width="200">Name</th>
                <th width="150">Phone</th>
                <th width="200">Location</th>
                <th width="100">Paket</th>
                <th width="100">Status</th>
                <th width="150">Schedule</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($row as $i => $d): ?>
                <tr style="background: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff'; ?>;">
                    <td align="center"><?= $no++; ?></td>

                    <td class="text-format"><?= $d['registrasi_id']; ?></td>

                    <td><?= htmlspecialchars($d['name']); ?></td>

                    <td class="text-format"><?= $d['phone']; ?></td>

                    <td><?= htmlspecialchars($d['location']); ?></td>
                    <td align="center"><?= $d['paket_internet']; ?> Mbps</td>

                    <td align="center" style="color: <?= $d['is_verified'] == 'Yes' ? 'green' : 'red'; ?>; font-weight:bold;">
                        <?= $d['is_verified']; ?>
                    </td>

                    <td><?= $d['date']; ?> - <?= $d['time']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>