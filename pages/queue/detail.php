<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
$id = isset($_POST['id']) ? $_POST['id'] : null;
$type_queue = isset($_POST['type_queue']) ? $_POST['type_queue'] : null;
if ($id && $type_queue) {
    $_SESSION['menu'] = 'queue';
    $requestTables = [
        "Install"    => ["table" => "request_ikr", "id" => "rikr_id"],
        "Service" => ["table" => "request_maintenance", "id" => "rm_id"],
        "Dismantle"  => ["table" => "request_dismantle", "id" => "rd_id"],
    ];
    function formatDate($datetime, $type = 'date')
    {
        $dt = new DateTime($datetime);
        switch ($type) {
            case 'date':
                return $dt->format('Y-m-d');
            case 'full':
                return $dt->format('d F Y');
            case 'time':
                return $dt->format('H:i');
        }
    }

    try {
        if (isset($requestTables[$type_queue])) {
            $table = $requestTables[$type_queue]['table'];
            $idCol = $requestTables[$type_queue]['id'];

            $sql = "SELECT q.*, r.*, c.* 
            FROM queue_scheduling q
            JOIN $table r ON q.request_id = r.$idCol
            JOIN customers c ON r.netpay_key = c.netpay_key
            WHERE q.queue_key = :queue_key";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':queue_key', $id, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$row) {
            $_SESSION['alert'] = [
                'icon' => 'warning',
                'title' => 'Data Tidak Ditemukan',
                'text' => 'Queue dengan ID tersebut tidak tersedia.',
                'button' => "Kembali",
                'style' => "warning"
            ];
            redirect("pages/queue/");
        }
        $tanggalSchedule   = isset($row['jadwal_pemasangan']) ? formatDate($row['jadwal_pemasangan'], 'date') : '';
        $tanggalPemasangan = isset($row['jadwal_pemasangan']) ? formatDate($row['jadwal_pemasangan'], 'full') : '';
        $jamPemasangan     = isset($row['jadwal_pemasangan']) ? formatDate($row['jadwal_pemasangan'], 'time') : '';
        $cr                = formatDate($row['created_at'], 'full');
        $statusClasses = [
            'Pending' => "info",
            'Accepted' => "success",
            'Rejected' => "danger",
        ];
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    redirect("pages/schedule/");
}
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>


<div class="content  d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Subheader-->
    <div class="subheader py-2 py-lg-6  subheader-solid " id="kt_subheader">
        <div class=" container-fluid  d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
            <!--begin::Info-->
            <div class="d-flex align-items-center flex-wrap mr-1">
                <!--begin::Page Heading-->
                <div class="d-flex align-items-baseline flex-wrap mr-5">
                    <!--begin::Page Title-->
                    <a href="<?= BASE_URL ?>pages/queue/" class="h4 text-dark font-weight-bold my-1 mr-5">
                        Queue </a>

                    <!--end::Page Title-->

                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                        <li class="breadcrumb-item">
                            <a href="" class="text-muted">
                                Detail Queue </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="" class="text-muted">
                                <?= $row['queue_id'] ?> </a>
                        </li>
                    </ul>
                    <!-- end::Breadcrumb -->
                </div>
                <!--end::Page Heading-->
            </div>
            <?php if ($row['status'] == 'Pending'): ?>
                <form action="<?= BASE_URL ?>pages/schedule/create.php" method="post">
                    <span>
                        <input type="hidden" name="type_queue" value="<?= $row['type_queue'] ?>">
                        <button type="submit" name="id" class="btn border-0  btn-light-primary align-self-end" value="<?= $row['queue_key'] ?>">
                            <span class="navi-icon"><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
                            <span class="navi-text">Schedule Now</span>
                        </button>
                    </span>
                </form>
            <?php endif; ?>
            <!--end::Info-->
        </div>
    </div>
    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class="container">
            <!--begin::Row-->
            <div class="row align-items-stretch">
                <div class="col-md-6 mb-5">
                    <div class="card card-stretch">
                        <div class="card-header">
                            <h3>Queue Info</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Queue ID</th>
                                        <td><?= $row['queue_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type Queue</th>
                                        <td><?= $row['type_queue'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Request ID</th>
                                        <td><?= $row['request_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><span class="badge badge-pill badge-<?= $statusClasses[$row['status']] ?>"><?= $row['status'] ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td><?= $cr ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Data Request IKR -->
                    <div class="card card-custom mb-5" data-card="true">
                        <?php if ($row['type_queue'] == "Install"): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="card-label">Data Request IKR</h3>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary mr-1" data-card-tool="toggle" data-toggle="tooltip" data-placement="top">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>

                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>RIKR ID</th>
                                            <td><?= $row['rikr_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Netpay ID</th>
                                            <td><?= $row['netpay_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Jadwal Pemasangan</th>
                                            <td><?= $tanggalPemasangan ?></td>
                                        </tr>
                                        <tr>
                                            <th>Jam</th>
                                            <td><?= $jamPemasangan ?> </td>
                                        </tr>
                                        <tr>
                                            <th>Request By</th>
                                            <td><?= $row['request_by'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Catatan</th>
                                            <td class="text-wrap"><?= $row['catatan'] ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php elseif ($row['type_queue'] == "Maintenance"): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="card-label">Data Request Maintenance</h3>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary mr-1" data-card-tool="toggle" data-toggle="tooltip" data-placement="top">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>

                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>RM ID</th>
                                            <td><?= $row['rm_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Netpay ID</th>
                                            <td><?= $row['netpay_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Type Issue</th>
                                            <td><?= $row['type_issue'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Request By</th>
                                            <td><?= $row['request_by'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Deskripsi Issue</th>
                                            <td><?= $row['deskripsi_issue'] ?> </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php elseif ($row['type_queue'] == "Dismantle"): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="card-label">Data Request Dismantle</h3>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary mr-1" data-card-tool="toggle" data-toggle="tooltip" data-placement="top">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>

                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>RD ID</th>
                                            <td><?= $row['rd_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Netpay ID</th>
                                            <td><?= $row['netpay_id'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Type Dismantle</th>
                                            <td><?= $row['type_dismantle'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Request By</th>
                                            <td><?= $row['request_by'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Deskripsi Dismantle</th>
                                            <td><?= $row['deskripsi_dismantle'] ?> </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- card customer -->
                    <div class="card card-stretch">
                        <div class="card-header">
                            <h3>Data Customer</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Netpay ID</th>
                                        <td><?= $row['netpay_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td><?= $row['name'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?= $row['phone'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Paket</th>
                                        <td><?= $row['paket_internet'] ?> Mbps</td>
                                    </tr>
                                    <tr>
                                        <th>Is Active?</th>
                                        <td><?= $row['is_active'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td class="text-wrap"><?= $row['location'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::Entry-->
</div>

<?php
require __DIR__ . '/../../includes/footer.php';

?>