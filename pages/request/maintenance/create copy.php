<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/checkRowExist.php';
require_once __DIR__ . '/../../../helper/generateId.php';

$_SESSION['menu'] = 'request maintenance';

$id = isset($_POST['id']) ? trim($_POST['id']) : null;
$rm_id = "";

$q = "SELECT * FROM type WHERE type = 'rm'";
$stmt = $pdo->prepare($q);
$stmt->execute();
$type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default row
$row = [
    "netpay_id" => '',
    "netpay_key" => '',
    "name" => '',
    "phone" => '',
    "paket_internet" => '',
    "is_active" => '',
    "perumahan" => '',
    "location" => '',
    "sharelock" => ''
];

try {
    if ($id) {

        // =============================
        // 🔥 CEK DATABASE LOKAL DULU
        // =============================
        $stmtLocal = $pdo->prepare("SELECT * FROM customers WHERE netpay_id = ?");
        $stmtLocal->execute([$id]);
        $local = $stmtLocal->fetch(PDO::FETCH_ASSOC);

        if ($local) {
            // ✅ DATA DARI DATABASE
            $row = [
                'netpay_id'     => $local['netpay_id'],
                'netpay_key'    => $local['netpay_key'],
                'name'          => $local['name'],
                'phone'         => $local['phone'],
                'paket_internet' => $local['paket_internet'],
                'is_active'     => $local['is_active'],
                'perumahan'     => $local['perumahan'],
                'location'      => $local['location'],
                'sharelock'     => $local['sharelock']
            ];
        } else {

            // =============================
            // 🌐 HIT API KALAU TIDAK ADA
            // =============================
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
                    "header" => "Authorization: Bearer {$token}\r\n" .
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
                $error = error_get_last();
                $_SESSION['alert'] = [
                    'icon' => 'error',
                    'title' => 'Request gagal',
                    'text' => 'Gagal menghubungi server Netpay. Error: ' . ($error['message'] ?? 'Tidak diketahui'),
                    'button' => "Kembali",
                    'style' => "danger"
                ];
                redirect("pages/request/maintenance/create.php");
                exit;
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $_SESSION['alert'] = [
                    'icon' => 'error',
                    'title' => 'Response Error',
                    'text' => 'Data dari Netpay tidak valid JSON.',
                    'button' => "Kembali",
                    'style' => "danger"
                ];
                redirect("pages/request/maintenance/create.php");
                exit;
            }

            if (empty($data)) {
                $_SESSION['alert'] = [
                    'icon' => 'warning',
                    'title' => 'Data tidak ditemukan',
                    'text' => 'Customer dengan Netpay ID ' . htmlspecialchars($id) . ' tidak ditemukan.',
                    'button' => "Kembali",
                    'style' => "warning"
                ];
                redirect("pages/request/maintenance/create.php");
                exit;
            }

            // DATA DARI API
            $row = [
                'netpay_id'     => $data['netpay_id'] ?? $id,
                'netpay_key'    => $data['iduser'] ?? '',
                'name'          => $data['nama'] ?? '',
                'phone'         => $data['telepon'] ?? '',
                'paket_internet' => $data['paket'] ?? '',
                'is_active'     => $data['status'] ?? '',
                'perumahan'     => $data['alamat'] ?? '',
                'location'      => $data['jalan'] ?? '',
                'sharelock'     => '' // ❗ API tidak punya
            ];
        }

        checkRowExist($row, "pages/request/maintenance/create.php");
        $rm_id = generateId('RM');
    }
} catch (Exception $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/request/maintenance");
    exit;
}

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="row">

                <!-- Form create maintenance -->
                <div class="col-md-6 mb-10">
                    <div class="card card-custom shadow-sm">
                        <div class="card-header pt-5">
                            <div class="card-title">
                                <h3 class="card-label">Create Request Service</h3>
                            </div>
                        </div>
                        <div class="card-body">

                            <form method="post" class="form">
                                <div class="form-group">
                                    <label>Netpay ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Cari Netpay ID" name="id" autocomplete="off" required value="<?= htmlspecialchars($row['netpay_id']) ?>">
                                        <button type="submit" class="btn btn-light-primary"><i class="flaticon-search"></i></button>
                                    </div>
                                </div>
                            </form>
                            <form method="post" class="form" action="<?= BASE_URL ?>controllers/request/maintenance/create.php">
                                <div class="form-group mt-7">
                                    <label>Request Service ID</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($rm_id) ?>" disabled />
                                        <input type="hidden" name="rm_id" value="<?= htmlspecialchars($rm_id) ?>">
                                        <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($row['netpay_id']) ?>">
                                        <input type="hidden" name="netpay_key" value="<?= htmlspecialchars($row['netpay_key']) ?>">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($row['name']) ?>">
                                        <input type="hidden" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
                                        <input type="hidden" name="is_active" value="<?= htmlspecialchars($row['is_active']) ?>">
                                        <input type="hidden" name="perumahan" value="<?= htmlspecialchars($row['perumahan']) ?>">
                                        <input type="hidden" name="location" value="<?= htmlspecialchars($row['location']) ?>">
                                        <input type="hidden" name="paket_internet" value="<?= htmlspecialchars($row['paket_internet']) ?>">
                                        <input type="hidden" name="sharelock" value="<?= htmlspecialchars($row['sharelock']) ?>">
                                    </div>
                                </div>

                                <!-- 🔥 SHARELOCK INPUT -->
                                <div class="form-group">
                                    <label>Share Location (Google Maps)</label>
                                    <input type="text" class="form-control" name="sharelock"
                                        placeholder="Paste link share location..."
                                        value="<?= htmlspecialchars($row['sharelock']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>No Yang Menghubungi</label>
                                    <input
                                        type="text"
                                        name="phone_contact"
                                        class="form-control"
                                        autocomplete="off"

                                        value="<?= htmlspecialchars($row['phone']) ?>">
                                </div>
                                <div class="form-group">
                                    <label>Type Issue</label>
                                    <select class="form-control selectpicker" id="type_issue" name="type_issue">
                                        <option value="">Select</option>
                                        <?php foreach ($type as $t): ?>
                                            <option value="<?= $t["catatan"] ?>"><?= $t["catatan"] ?></option>
                                        <?php endforeach; ?>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>

                                <div class="form-group mb-1">
                                    <label>Deskripsi Issue</label>
                                    <textarea class="form-control" name="deskripsi_issue" rows="3"></textarea>
                                </div>

                        </div>

                        <div class="card-footer text-right">
                            <a href="<?= BASE_URL ?>pages/request/maintenance" class="btn btn-light-danger font-weight-bold">Batal</a>
                            <button type="submit" name="submit" class="btn btn-primary font-weight-bold">Simpan</button>
                        </div>

                        </form>
                    </div>
                </div>

                <!-- Data Customer -->
                <div class="col-md-6 mb-10">
                    <div class="card card-custom mb-5">
                        <div class="card-header">
                            <div class="card-title">
                                <h3 class="card-label">Data Customer</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
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
</div>

<?php
require __DIR__ . '/../../../includes/footer.php';
?>