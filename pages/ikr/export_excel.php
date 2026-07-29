<?php
require_once __DIR__ . '/../../includes/config.php';


$sql = "SELECT i.*, c.netpay_id FROM ikr i JOIN customers c ON c.netpay_key = i.netpay_key";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$ikr = $stmt->fetchAll(PDO::FETCH_ASSOC);
$no = 1;

// Header untuk download Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=IKR_Summary_" . date("d F Y") . ".xls");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        h2 {
            color: #007bff;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            font-size: 12px;
        }

        table th {
            background: #007bff;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            border: 1px solid #000;
        }

        table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }
    </style>

    <center>
        <h2>📋 Jabbar23 IKR Summary</h2>
    </center>
    <div style="text-align:right; margin-right:30px; font-weight:bold;">
        <?= date("d F Y"); ?>
    </div>

    <table>
        <tr>
            <th>No.</th>
            <th>IKR ID</th>
            <th>Netpay ID</th>
            <th>Group IKR</th>
            <th>IKR An</th>
            <th>Alamat</th>
            <th>RT</th>
            <th>RW</th>
            <th>Desa</th>
            <th>Kecamatan</th>
            <th>Kabupaten</th>
            <th>Telepon</th>
            <th>S/N</th>
            <th>Paket</th>
            <th>Type ONT</th>
            <th>Redaman</th>
            <th>ODP No</th>
            <th>ODC No</th>
            <th>JC No</th>
            <th>MAC Sebelum</th>
            <th>MAC Sesudah</th>
            <th>ODP</th>
            <th>ODC</th>
            <th>Enclosure</th>
            <th>Paket No</th>
            <th>Tanggal</th>
            <th>PIC</th>
        </tr>
        <?php foreach ($ikr as $d): ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $d['ikr_id']; ?></td>
                <td style="mso-number-format:'\@';"><?= $d['netpay_id']; ?></td>
                <td><?= $d['group_ikr']; ?></td>
                <td><?= $d['ikr_an']; ?></td>
                <td><?= $d['alamat']; ?></td>
                <td><?= $d['rt']; ?></td>
                <td><?= $d['rw']; ?></td>
                <td><?= $d['desa']; ?></td>
                <td><?= $d['kec']; ?></td>
                <td><?= $d['kab']; ?></td>
                <td style="mso-number-format:'\@';"><?= $d['telp']; ?></td>
                <td><?= $d['sn']; ?></td>
                <td><?= $d['paket']; ?></td>
                <td><?= $d['type_ont']; ?></td>
                <td><?= $d['redaman']; ?></td>
                <td><?= $d['odp_no']; ?></td>
                <td><?= $d['odc_no']; ?></td>
                <td><?= $d['jc_no']; ?></td>
                <td><?= $d['mac_sebelum']; ?></td>
                <td><?= $d['mac_sesudah']; ?></td>
                <td><?= $d['odp']; ?></td>
                <td><?= $d['odc']; ?></td>
                <td><?= $d['enclosure']; ?></td>
                <td><?= $d['paket_no']; ?></td>
                <td><?= $d['created_at']; ?></td>
                <td><?= $d['pic']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>