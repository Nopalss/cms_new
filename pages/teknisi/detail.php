<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';


$statusIssueClasses = [
    'Pending' => "info",
    'Approved' => "success",
    'Rejected' => "danger",
];

$id = $_GET['id'] ?? null;
if (!$id) {
    header("location:" . BASE_URL);
}
try {
    $sql = "SELECT t.*, u.avatar FROM technician t  JOIN users u ON t.username = u.username WHERE t.tech_id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$technician) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Teknisi tidak ditemukan.',
            'button' => 'Oke',
            'style' => 'warning'
        ];
        redirect("pages/dashboard.php");
    }
    $date = $date ?? date('Y-m-d');
    $sql = "
    SELECT 
        COUNT(*) AS total_tugas,
        SUM(CASE WHEN job_type = 'Instalasi'  THEN 1 ELSE 0 END) AS total_instalasi,
        SUM(CASE WHEN job_type = 'Maintenance' THEN 1 ELSE 0 END) AS total_maintenance,
        SUM(CASE WHEN job_type = 'Dismantle'   THEN 1 ELSE 0 END) AS total_dismantle,
        SUM(CASE WHEN status = 'Done' THEN 1 ELSE 0 END) AS total_done
    FROM schedules
    WHERE
        (tech_id = :tech_id OR tech_id = :tim_id)
        AND date = :date
";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tech_id' => $id,
        ':tim_id'  => $technician['tim_id'],
        ':date'    => $date
    ]);

    $tugas = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT s.*, c.location, c.phone FROM schedules s
        JOIN queue_scheduling q ON s.queue_id = q.queue_id
        JOIN customers c ON q.netpay_id = c.netpay_id
        WHERE date = CURDATE()
        AND (tech_id = :tech_id OR tech_id = :tim_id)
        ORDER BY time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tech_id' => $id,
        ':tim_id'  => $technician['tim_id'],
    ]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ini buat style
    $badgeClasses = [
        'Instalasi'   => 'success',
        'Maintenance' => 'warning',
        'Dismantle'   => 'danger'
    ];
    $statusClasses = [
        'Pending' => "info",
        'Actived' => "primary",
        'Rescheduled' => "warning",
        'Cancelled' => "danger",
        'Done' => "success"
    ];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
$date = new DateTime();

// Format tanggal gaya bahasa Inggris (contoh: Monday, 25 November 2025)
$tanggal = $date->format('l, d F Y');
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>


<div class="content  d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Subheader-->

    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class=" container ">

            <div class="row mt-25">
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header relative">
                            <div style="width:100px; height:100px; background-color:white; padding:5px; position:absolute; top:-10px; left:50%;  transform: translate(-50%, -50%); border-radius:10px">
                                <img src="<?= BASE_URL ?>assets/media/users/<?= $technician['avatar'] ?>" class="shadow-sm"
                                    style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px; object-position: center;"
                                    alt="Foto Teknisi">

                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="pr-0">Id Teknisi</th>
                                    <td style="width:1%">:</td>
                                    <td><?= $technician['tech_id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td>:</td>
                                    <td><?= $technician['name'] ?></td>
                                </tr>
                                <tr>
                                    <th>No HP</th>
                                    <td>:</td>
                                    <td><?= $technician['phone'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="card-title d-flex justify-content-between align-items-center">
                                <h5>Detail Tugas</h5>
                                <p class="text-muted small"><?= $tanggal ?></p>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 mb-2">
                                    <div class="bg-primary d-flex flex-column justify-content-center align-items-center p-2 rounded text-white shadow-sm" style=" height: 90px">
                                        <h3><?= $tugas['total_tugas'] ?></h3>
                                        <p class="small">Tugas</p>
                                    </div>

                                </div>
                                <div class="col-lg-3 mb-2">
                                    <div class="bg-success d-flex flex-column justify-content-center align-items-center p-2 rounded text-white" style="height: 90px;">
                                        <h3><?= $tugas['total_done'] ?></h3>
                                        <p class="small">Selesai</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 mb-2">
                                    <div class="border border-info d-flex flex-column justify-content-center align-items-center p-2 rounded text-info">
                                        <h3><?= $tugas['total_instalasi'] ?></h3>
                                        <p class="small">Instalasi</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 mb-2">
                                    <div class="border border-warning d-flex flex-column justify-content-center align-items-center p-2 rounded text-warning">
                                        <h3><?= $tugas['total_maintenance'] ?></h3>
                                        <p class="small">Maintenance</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 mb-2">
                                    <div class="border border-danger d-flex flex-column justify-content-center align-items-center p-2 rounded text-danger">
                                        <h3><?= $tugas['total_dismantle'] ?></h3>
                                        <p class="small">Dismantle</p>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive-sm mt-5">
                                <table class="table text-sm" style="min-width: 400px;">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="font-size: 11px;">Schedule Id</th>
                                            <th scope="col" style="font-size: 11px;">Job Type</th>
                                            <th scope="col" style="font-size: 11px;">Status</th>
                                            <th scope="col" style="font-size: 11px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($schedules) > 0): ?>
                                            <?php foreach ($schedules as $s): ?>
                                                <tr>
                                                    <th scope="row" style="font-size: 11px;"><?= $s['schedule_id'] ?></th>
                                                    <td style="font-size: 11px;"><?= $s['job_type'] ?></td>
                                                    <td style="font-size: 11px;"><span class="badge badge-pill text-sm badge-<?= $statusClasses[$s['status']] ?>"><?= $s['status'] ?></span></td>
                                                    <td style="font-size: 11px;">
                                                        <div class="dropdown dropdown-inline">
                                                            <a href="javascript:;" class="btn btn-sm btn-light btn-text-primary btn-icon mr-2" data-toggle="dropdown">
                                                                <span class="svg-icon svg-icon-md">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                                            <rect x="0" y="0" width="24" height="24" />
                                                                            <path d="M5,8.6862915 L5,5 L8.6862915,5 L11.5857864,2.10050506 L14.4852814,5 L19,5 L19,9.51471863 L21.4852814,12 L19,14.4852814 L19,19 L14.4852814,19 L11.5857864,21.8994949 L8.6862915,19 L5,19 L5,15.3137085 L1.6862915,12 L5,8.6862915 Z M12,15 C13.6568542,15 15,13.6568542 15,12 C15,10.3431458 13.6568542,9 12,9 C10.3431458,9 9,10.3431458 9,12 C9,13.6568542 10.3431458,15 12,15 Z" fill="#000000" />
                                                                        </g>
                                                                    </svg>
                                                                </span>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                                <ul class="navi flex-column navi-hover py-2">
                                                                    <li class="navi-header font-weight-bolder text-uppercase font-size-xs text-primary pb-2">
                                                                        Choose an action:
                                                                    </li>
                                                                    <li class="navi-item cursor-pointer">
                                                                        <form action="<?= BASE_URL ?>pages/schedule/detail.php" method="post">
                                                                            <input type="hidden" name="job_type" value="<?= $s['job_type'] ?>">
                                                                            <button type="submit" name="id" class="btn  border-0 navi-link btn-detail-rikr" value="<?= $s['schedule_key'] ?>">
                                                                                <span class="navi-icon "><i class="flaticon-eye text-info"></i></span>
                                                                                <span class="navi-text">Detail</span>
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td class="text-center text-muted text-weight-bold" colspan="4">Tidak ada schedule yang terdaftar untuk hari ini</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end::Container -->
    </div>
</div>
<?php
require __DIR__ . '/../../includes/footer.php';
?>