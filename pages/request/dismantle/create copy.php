<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/checkRowExist.php';
require_once __DIR__ . '/../../../helper/sanitize.php';
require_once __DIR__ . '/../../../helper/generateId.php';

$_SESSION['menu'] = 'request dismantle';

$id = isset($_POST['id']) ? sanitize($_POST['id']) : null;
$rd_id = "";

$q = "SELECT * FROM type WHERE type = 'rd'";
$stmt = $pdo->prepare($q);
$stmt->execute();
$type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DEFAULT
$row = [
    "netpay_key" => '',
    "netpay_id" => '',
    "name" => '',
    "phone" => '',
    "phone_contact" => '',
    "paket_internet" => '',
    "is_active" => '',
    "perumahan" => '',
    "location" => '',
    "sharelock" => ''
];

try {
    if ($id) {

        $stmtLocal = $pdo->prepare("SELECT * FROM customers WHERE netpay_id = ?");
        $stmtLocal->execute([$id]);
        $local = $stmtLocal->fetch(PDO::FETCH_ASSOC);

        if ($local) {
            $row = [
                'netpay_id'     => $local['netpay_id'],
                'netpay_key'    => $local['netpay_key'],
                'name'          => $local['name'],
                'phone'         => $local['phone'],
                'phone_contact' => $local['phone_contact'] ?? '',
                'paket_internet' => $local['paket_internet'],
                'is_active'     => $local['is_active'],
                'perumahan'     => $local['perumahan'],
                'location'      => $local['location'],
                'sharelock'     => $local['sharelock']
            ];
        } else {

            $apiBase = "https://netpay.jabbar23.net/1_api/netpaydt.php";
            $token = defined('NETPAY_API_TOKEN') ? NETPAY_API_TOKEN : '';

            $query = http_build_query([
                'path' => 'usernet',
                'netpay_id' => $id
            ]);
            $url = $apiBase . '?' . $query;

            $options = [
                "http" => [
                    "method" => "GET",
                    "header" => "Authorization: Bearer " . $token . "\r\n" .
                        "Accept: application/json\r\n",
                    "timeout" => 10
                ],
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                $_SESSION['alert'] = [
                    'icon' => 'error',
                    'title' => 'Gagal konek ke server Netpay',
                    'text' => 'Terjadi kesalahan.',
                    'button' => 'Kembali',
                    'style' => 'danger'
                ];
                redirect("pages/request/dismantle/create.php");
                exit;
            }

            $data = json_decode($response, true);

            if (empty($data)) {
                $_SESSION['alert'] = [
                    'icon' => 'warning',
                    'title' => 'Data Tidak Ditemukan',
                    'text' => "Netpay ID {$id} tidak ditemukan.",
                    'button' => 'Kembali',
                    'style' => 'warning'
                ];
                redirect("pages/request/dismantle/create.php");
                exit;
            }

            $row = [
                'netpay_id'     => $data['netpay_id'] ?? $id,
                'netpay_key'    => $data['iduser'] ?? '',
                'name'          => $data['nama'] ?? '',
                'phone'         => $data['telepon'] ?? '',
                'phone_contact' => '',
                'paket_internet' => $data['paket'] ?? '',
                'is_active'     => $data['status'] ?? '',
                'perumahan'     => $data['alamat'] ?? '',
                'location'      => $data['jalan'] ?? '',
                'sharelock'     => ''
            ];
        }

        checkRowExist($row, "pages/request/dismantle/create.php");
        $rd_id = generateId("RD");
    }
} catch (Exception $e) {
    redirect("pages/request/dismantle");
    exit;
}

// 🔥 DEFAULT PHONE CONTACT
$phone_contact = $row['phone_contact'] ?: $row['phone'];

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="row">

                <!-- Form -->
                <div class="col-md-6 mb-10">
                    <div class="card card-custom shadow-sm">
                        <div class="card-header pt-5">
                            <div class="card-title">
                                <h3 class="card-label">Create Request Dismantle</h3>
                            </div>
                        </div>

                        <div class="card-body">

                            <form method="post" class="form">
                                <div class="form-group">
                                    <label>Netpay ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="id"
                                            value="<?= htmlspecialchars($row['netpay_id']) ?>" required>
                                        <button type="submit" class="btn btn-light-primary">
                                            <i class="flaticon-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form method="post" class="form" action="<?= BASE_URL ?>controllers/request/dismantle/create.php">

                                <div class="form-group mt-7">
                                    <label>Request Dismantle ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($rd_id) ?>" disabled />
                                        <input type="hidden" name="rd_id" value="<?= htmlspecialchars($rd_id) ?>">
                                        <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($row['netpay_id']) ?>">
                                        <input type="hidden" name="netpay_key" value="<?= htmlspecialchars($row['netpay_key']) ?>">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($row['name']) ?>">
                                        <input type="hidden" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                                        <input type="hidden" name="phone_contact" value="<?= htmlspecialchars($phone_contact) ?>">
                                        <input type="hidden" name="is_active" value="<?= htmlspecialchars($row['is_active']) ?>">
                                        <input type="hidden" name="perumahan" value="<?= htmlspecialchars($row['perumahan']) ?>">
                                        <input type="hidden" name="location" value="<?= htmlspecialchars($row['location']) ?>">
                                        <input type="hidden" name="paket_internet" value="<?= htmlspecialchars($row['paket_internet']) ?>">
                                        <input type="hidden" name="sharelock" value="<?= htmlspecialchars($row['sharelock']) ?>">
                                    </div>
                                </div>

                                <!-- 🔥 SHARELOCK -->
                                <div class="form-group">
                                    <label>Share Location</label>
                                    <input type="text" class="form-control" name="sharelock"
                                        value="<?= htmlspecialchars($row['sharelock']) ?>">
                                </div>

                                <!-- 🔥 PHONE CONTACT -->
                                <div class="form-group">
                                    <label>No Yang Menghubungi</label>
                                    <input type="text" name="phone_contact" class="form-control"
                                        value="<?= htmlspecialchars($phone_contact) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Type Dismantle</label>
                                    <select class="form-control selectpicker" required name="type_dismantle">
                                        <option value="">Select</option>
                                        <?php foreach ($type as $t): ?>
                                            <option value="<?= $t["catatan"] ?>"><?= $t["catatan"] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group mb-1">
                                    <label>Deskripsi Dismantle</label>
                                    <textarea class="form-control" required name="deskripsi_dismantle" rows="3"></textarea>
                                </div>

                        </div>

                        <div class="card-footer text-right">
                            <a href="<?= BASE_URL ?>pages/request/maintenance" class="btn btn-light-danger font-weight-bold">Batal</a>
                            <button type="submit" name="submit" class="btn btn-primary font-weight-bold">Simpan</button>
                        </div>

                        </form>
                    </div>
                </div>

                <!-- DATA CUSTOMER -->
                <div class="col-md-6 mb-10">
                    <div class="card card-custom mb-5">
                        <div class="card-header">
                            <div class="card-title">
                                <h3 class="card-label">Data Customer</h3>
                            </div>
                        </div>

                        <div class="card-body">
                            <table class="table table-striped">
                                <tr>
                                    <th>Netpay ID</th>
                                    <td><?= htmlspecialchars($row['netpay_id']) ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>No HP</th>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                </tr>
                                <tr>
                                    <th>Paket</th>
                                    <td><?= htmlspecialchars($row['paket_internet']) ?></td>
                                </tr>
                                <tr>
                                    <th>Is Active?</th>
                                    <td><?= htmlspecialchars($row['is_active']) ?></td>
                                </tr>
                                <tr>
                                    <th>Perumahan</th>
                                    <td><?= htmlspecialchars($row['perumahan']) ?></td>
                                </tr>
                                <tr>
                                    <th>Alamat</th>
                                    <td><?= htmlspecialchars($row['location']) ?></td>
                                </tr>
                                <tr>
                                    <th>Share Location</th>
                                    <td><?= htmlspecialchars($row['sharelock']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>