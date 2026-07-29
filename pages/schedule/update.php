<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/checkRowExist.php';
$id = isset($_POST['id']) ? $_POST['id'] : null;
$issue_id = isset($_POST['issue_id']) ? $_POST['issue_id'] : null;
$job_type = isset($_POST['job_type']) ? $_POST['job_type'] : null;
if ($id && $job_type) {
    $_SESSION['menu'] = 'schedule';
    try {
        $status = ['Pending', 'Rescheduled', 'Cancelled', 'Done'];

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

        if ($job_type == "Instalasi") {

            $sql = "
        SELECT
            s.*,
            s.status as status_schedule,
            q.*,
            r.*,
            reg.date AS tanggal,
            reg.time AS jam,
            c.*,
            r.catatan AS deskripsi

        FROM schedules s

        INNER JOIN queue_scheduling q
            ON s.queue_id = q.queue_id

        INNER JOIN request_ikr r
            ON r.queue_id = q.queue_id

        INNER JOIN register reg
            ON reg.registrasi_id = r.registrasi_id

        INNER JOIN customers c
            ON c.netpay_id = q.netpay_id

        WHERE s.schedule_id = :id
    ";
        } elseif ($job_type == "Service") {

            $sql = "
        SELECT
            s.*,
            s.status as status_schedule,
            q.*,
            r.*,
            c.*,
            r.deskripsi_issue AS deskripsi

        FROM schedules s

        INNER JOIN queue_scheduling q
            ON s.queue_id = q.queue_id

        INNER JOIN request_maintenance r
            ON r.queue_id = q.queue_id

        INNER JOIN customers c
            ON c.netpay_id = q.netpay_id

        WHERE s.schedule_id = :id
    ";
        } elseif ($job_type == "Dismantle") {

            $sql = "
        SELECT
            s.*,
            s.status as status_schedule,
            q.*,
            r.*,
            c.*,
            r.deskripsi_dismantle AS deskripsi

        FROM schedules s

        INNER JOIN queue_scheduling q
            ON s.queue_id = q.queue_id

        INNER JOIN request_dismantle r
            ON r.queue_id = q.queue_id

        INNER JOIN customers c
            ON c.netpay_id = q.netpay_id

        WHERE s.schedule_id = :id
    ";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        checkRowExist($row, "pages/schedule/");
        $tanggalSchedule   = isset($row['date'])               ? formatDate($row['date'],               'date') : '';
        $jamSchedule       = isset($row['time'])                ? formatDate($row['time'],               'time') : '';
        $tanggalPemasangan = !empty($row['tanggal'])
            ? formatDate($row['tanggal'], 'full')
            : '';

        $jamPemasangan = !empty($row['jam'])
            ? substr($row['jam'], 0, 5)
            : '';
        $cr                = formatDate($row['created_at'], 'full');
        if ($job_type == 'Dismantle') {
            $type_issue = $row['type_dismantle'] ?? '';
        } else {
            $type_issue = $row['type_issue'] ?? '';
        }
        $statusClasses     = [
            'Pending'  => "info",
            'Accepted' => "success",
            'Rejected' => "danger",
        ];
        $sql         = "SELECT * FROM technician";
        $stmt        = $pdo->query($sql);
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sql         = "SELECT * FROM tim";
        $stmt        = $pdo->query($sql);
        $tim         = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($issue_id)) {
            $stmt = $pdo->prepare("SELECT * FROM issues_report WHERE issue_id = :issue_id LIMIT 1");
            $stmt->execute([':issue_id' => $issue_id]);
            $issue = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $_SESSION['alert'] = [
            'icon'   => 'error',
            'title'  => 'Oops! Ada yang Salah',
            'text'   => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
            'button' => "Coba Lagi",
            'style'  => "danger"
        ];
        redirect("pages/schedule/");
    }
} else {
    redirect("pages/schedule/");
}
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

// Mapping label & warna per tipe job
$jobTypeLabel    = $typeJob[$row['job_type']] ?? $row['job_type'];
$jobTypeColorMap = ['Instalasi' => 'success', 'Service' => 'warning', 'Dismantle' => 'danger'];
$jobTypeIconMap  = ['Instalasi' => 'wifi',    'Service' => 'tool',    'Dismantle' => 'x-circle'];
$jobTypeColor    = $jobTypeColorMap[$jobTypeLabel] ?? 'secondary';
$jobTypeIcon     = $jobTypeIconMap[$jobTypeLabel]  ?? 'file';

// XSS escape helper
function sch_h($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Inline SVG icon helper (Feather Icons style)
function sch_icon($name, $class = '')
{
    $paths = [
        'calendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'clock'      => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'check'      => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert'      => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'tool'       => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user'       => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'file'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'hash'       => '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
        'phone'      => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'wifi'       => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>',
        'pin'        => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'x-circle'   => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'clipboard'  => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
        'info'       => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'activity'   => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'lock'       => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'edit'       => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'refresh'    => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
    ];

    $inner     = $paths[$name] ?? '';
    $classAttr = $class !== '' ? ' class="' . $class . '"' : '';
    return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}
?>

<style>
    .sch-update svg {
        width: 18px;
        height: 18px;
        vertical-align: -3px;
    }

    .sch-update .icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sch-update .icon-box svg {
        width: 20px;
        height: 20px;
        vertical-align: 0;
    }

    .sch-update .icon-box.icon-sm {
        width: 34px;
        height: 34px;
        border-radius: 9px;
    }

    .sch-update .icon-box.icon-sm svg {
        width: 15px;
        height: 15px;
    }

    .sch-update .icon-box.icon-lg {
        width: 56px;
        height: 56px;
        border-radius: 16px;
    }

    .sch-update .icon-box.icon-lg svg {
        width: 26px;
        height: 26px;
    }

    .sch-update .icon-box.on-dark {
        background: rgba(255, 255, 255, .18);
        color: #fff;
    }

    .sch-update .header-card {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
    }

    .sch-update .field-ro {
        background-color: #f8f9fc !important;
        border-color: #eef1f5 !important;
        color: #5e6278 !important;
        cursor: default;
    }

    .sch-update .field-ro:focus {
        box-shadow: none !important;
    }

    .sch-update .section-head {
        border-bottom: 1px solid #eef1f5;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .sch-update .info-item {
        border-color: #eef1f5 !important;
    }

    .sch-update .info-item .label-col {
        min-width: 120px;
        flex-shrink: 0;
    }

    .sch-update .bg-light-primary {
        background-color: #e8f1ff;
    }

    .sch-update .bg-light-info {
        background-color: #e7f6fb;
    }

    .sch-update .bg-light-success {
        background-color: #e9f9ef;
    }

    .sch-update .bg-light-warning {
        background-color: #fdf3e1;
    }

    .sch-update .bg-light-danger {
        background-color: #fcebeb;
    }

    .sch-update .bg-light-secondary {
        background-color: #f0f1f3;
    }

    .sch-update .badge-light-success {
        background-color: #e9f9ef;
        color: #16a34a;
    }

    .sch-update .badge-light-warning {
        background-color: #fdf3e1;
        color: #b45309;
    }

    .sch-update .badge-light-danger {
        background-color: #fcebeb;
        color: #dc2626;
    }

    .sch-update .badge-light-primary {
        background-color: #e8f1ff;
        color: #2563eb;
    }

    .sch-update .badge-light-secondary {
        background-color: #f0f1f3;
        color: #6b7280;
    }

    /* Issue report alert strip */
    .sch-update .issue-alert-strip {
        border-left: 4px solid #f59e0b;
        background: #fffbeb;
        border-radius: 0 10px 10px 0;
        padding: 12px 16px;
        margin-bottom: 16px;
    }

    @media (max-width:575.98px) {
        .sch-update .header-card h4 {
            font-size: 1.1rem;
        }

        .sch-update .info-item {
            flex-direction: column;
            gap: 4px;
        }

        .sch-update .info-item .label-col {
            min-width: auto;
        }
    }
</style>

<div class="content d-flex flex-column flex-column-fluid sch-update" id="kt_content">


    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <!-- ===== HERO CARD ===== -->
            <div class="card header-card shadow-sm mb-6">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">

                        <div class="d-flex align-items-center mb-2 mb-md-0">
                            <div class="icon-box icon-lg on-dark mr-3">
                                <?= sch_icon('edit') ?>
                            </div>
                            <div>
                                <h4 class="font-weight-bolder mb-1 text-white">Update Jadwal</h4>
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="badge badge-light mr-2"><?= sch_h($row['schedule_id']) ?></span>
                                    <span class="badge badge-light-<?= sch_h($jobTypeColor) ?>">
                                        <?= sch_icon($jobTypeIcon, 'mr-1') ?><?= sch_h($jobTypeLabel) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <a href="<?= BASE_URL ?>pages/schedule/" class="btn btn-light btn-sm font-weight-bold">
                                <?= sch_icon('arrow-left', 'mr-1') ?>Kembali
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ===== MAIN ROW ===== -->
            <div class="row align-items-start">

                <!-- ==============================
                     KIRI: FORM UPDATE SCHEDULE
                     ============================== -->
                <div class="col-12 col-md-7 mb-6">
                    <div class="card shadow-sm border-0">
                        <form action="<?= BASE_URL ?>controllers/schedules/update.php" method="post">

                            <!-- Card header form -->
                            <div class="card-header bg-white d-flex align-items-center py-4">
                                <div class="icon-box bg-light-primary text-primary mr-3">
                                    <?= sch_icon('refresh') ?>
                                </div>
                                <span class="h6 font-weight-bolder text-dark mb-0">Form Update Jadwal</span>
                            </div>

                            <div class="card-body p-4 p-lg-5">

                                <!-- Hidden inputs -->
                                <input type="hidden" name="schedule_id" value="<?= sch_h($row['schedule_id']) ?>">
                                <input type="hidden" name="queue_id" value="<?= sch_h($row['queue_id']) ?>">
                                <input type="hidden" name="netpay_id" value="<?= sch_h($row['netpay_id']) ?>">
                                <?php if (isset($issue)): ?>
                                    <input type="hidden" name="issue_id" value="<?= sch_h($issue['issue_id']) ?>">
                                <?php endif; ?>

                                <!-- ---- SECTION 1: Info Job ---- -->
                                <div class="d-flex align-items-center section-head">
                                    <div class="icon-box icon-sm bg-light-secondary text-secondary mr-2">
                                        <?= sch_icon('info') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-1">Informasi Job</span>
                                    <small class="text-muted ml-2">(otomatis dari request)</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark small">Netpay ID</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border-right-0">
                                                <?= sch_icon('hash') ?>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control field-ro border-left-0"
                                            value="<?= sch_h($row['netpay_id']) ?>" disabled>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark small">Tipe Job</label>
                                            <div class="form-control field-ro d-flex align-items-center">
                                                <span class="badge badge-pill badge-light-<?= sch_h($jobTypeColor) ?> px-3">
                                                    <?= sch_icon($jobTypeIcon, 'mr-1') ?><?= sch_h($jobTypeLabel) ?>
                                                </span>
                                            </div>
                                            <input type="hidden" name="job_type" value="<?= sch_h($jobTypeLabel) ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark small">Type Issue</label>
                                            <input type="text" class="form-control field-ro" readonly
                                                name="type_issue" value="<?= sch_h($type_issue) ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- ---- SECTION 2: Jadwal & Teknisi ---- -->
                                <div class="d-flex align-items-center section-head mt-5">
                                    <div class="icon-box icon-sm bg-light-primary text-primary mr-2">
                                        <?= sch_icon('calendar') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-1">Jadwal &amp; Teknisi</span>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark small">Pilih Teknisi</label>
                                    <select class="form-control selectpicker" id="tech_id" name="tech_id"
                                        data-size="7" data-live-search="true">
                                        <option value="">— Pilih Teknisi / Tim —</option>
                                        <?php foreach ($technicians as $t): ?>
                                            <?php $selected = ($row['tech_id'] == $t['tech_id']) ? 'selected' : ''; ?>
                                            <option value="<?= sch_h($t['tech_id']) ?>" <?= $selected ?>><?= sch_h($t['name']) ?></option>
                                        <?php endforeach; ?>
                                        <?php foreach ($tim as $t): ?>
                                            <?php $selected = ($row['tech_id'] == $t['tim_id']) ? 'selected' : ''; ?>
                                            <option value="<?= sch_h($t['tim_id']) ?>" <?= $selected ?>><?= sch_h($t['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark small">Tanggal</label>
                                            <input type="date" class="form-control" name="date" id="date"
                                                value="<?= sch_h($tanggalSchedule) ?>"
                                                min="<?= date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark small">Jam</label>
                                            <input type="text" name="time" id="time-schedule"
                                                class="form-control" placeholder="HH:mm" maxlength="5"
                                                value="<?= sch_h($jamSchedule) ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- ---- SECTION 3: Status ---- -->
                                <div class="d-flex align-items-center section-head mt-5">
                                    <div class="icon-box icon-sm bg-light-warning text-warning mr-2">
                                        <?= sch_icon('activity') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-1">Status Jadwal</span>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark small">Status</label>
                                    <select class="form-control selectpicker" name="status" data-size="7">
                                        <option value="">— Pilih Status —</option>
                                        <?php foreach ($status as $s): ?>
                                            <?php $selected = ($s == $row['status_schedule']) ? 'selected' : ''; ?>
                                            <option value="<?= sch_h($s) ?>" <?= $selected ?>><?= sch_h($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- ---- SECTION 4: Lokasi ---- -->
                                <div class="d-flex align-items-center section-head mt-5">
                                    <div class="icon-box icon-sm bg-light-danger text-danger mr-2">
                                        <?= sch_icon('pin') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-1">Lokasi</span>
                                    <small class="text-muted ml-2">(otomatis dari data pelanggan)</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark small">Alamat Lengkap</label>
                                    <textarea class="form-control field-ro" readonly name="location" rows="3"><?= sch_h($row['location']) ?></textarea>
                                </div>

                                <!-- ---- SECTION 5: Catatan ---- -->
                                <div class="d-flex align-items-center section-head mt-5">
                                    <div class="icon-box icon-sm bg-light-info text-info mr-2">
                                        <?= sch_icon('clipboard') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-1">Catatan</span>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark small">Catatan Tambahan</label>
                                    <textarea class="form-control" name="catatan" rows="4"
                                        placeholder="Tambahkan catatan untuk teknisi..."><?= sch_h($row['deskripsi']) ?></textarea>
                                </div>

                            </div><!-- /card-body -->

                            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-4">
                                <a href="<?= BASE_URL ?>pages/schedule/" class="btn btn-light-danger font-weight-bold">
                                    <?= sch_icon('arrow-left', 'mr-1') ?>Batal
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary font-weight-bold px-5">
                                    <?= sch_icon('check', 'mr-1') ?>Simpan Perubahan
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
                <!-- /kiri -->

                <!-- ==============================
                     KANAN: KARTU INFO
                     ============================== -->
                <div class="col-12 col-md-5">

                    <!-- Technician Activities (id card-timeline & div#timeline WAJIB dipertahankan) -->
                    <div class="card card-custom shadow-sm border-0 mb-5" id="card-timeline">
                        <div class="card-header align-items-center border-0 py-4">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="font-weight-bolder text-dark">Aktivitas Teknisi</span>
                                <span class="text-muted font-weight-bold font-size-sm mt-1">Pilih teknisi untuk melihat jadwalnya</span>
                            </h3>
                            <div class="card-toolbar">
                                <div class="dropdown dropdown-inline">
                                    <a href="#" class="btn btn-clean btn-hover-light-primary btn-sm btn-icon"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="ki ki-bold-more-hor"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-md dropdown-menu-right">
                                        <ul class="navi navi-hover">
                                            <li class="navi-header font-weight-bold py-4">
                                                <span class="font-size-lg">Keterangan Warna:</span>
                                            </li>
                                            <li class="navi-separator mb-3 opacity-70"></li>
                                            <li class="navi-item">
                                                <a href="#" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-success">Instalasi</span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="navi-item">
                                                <a href="#" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-warning">Service</span>
                                                    </span>
                                                </a>
                                            </li>
                                            <li class="navi-item">
                                                <a href="#" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-danger">Dismantle</span>
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4">
                            <!-- div#timeline WAJIB ada & tetap kosong — JS yang isi kontennya -->
                            <div class="timeline timeline-6 mt-3" id="timeline"></div>
                        </div>
                    </div>

                    <!-- Issue Report Card (hanya muncul jika ada issue) -->
                    <?php if (isset($issue)): ?>
                        <div class="card card-custom shadow-sm border-0 mb-5" data-card="true">
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="icon-box bg-light-warning text-warning mr-3">
                                        <?= sch_icon('alert') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark">Issue Report</span>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Issue ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($issue['issue_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Schedule ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($issue['schedule_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Reported By</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($issue['reported_by']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Issue Type</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($issue['issue_type']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-center px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Status</span>
                                        <span class="badge badge-pill badge-warning"><?= sch_h($issue['status']) ?></span>
                                    </li>
                                    <li class="list-group-item px-5 py-3">
                                        <div class="text-muted small font-weight-bold mb-1">Description</div>
                                        <div class="font-weight-bold text-dark"><?= sch_h($issue['description']) ?></div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Data Request Card (dinamis per tipe) -->
                    <div class="card card-custom shadow-sm border-0 mb-5" data-card="true">

                        <?php if ($row['type_queue'] === 'Install'): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="icon-box bg-light-success text-success mr-3">
                                        <?= sch_icon('wifi') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark">Data Request Instalasi</span>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">RIKR ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['rikr_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Netpay ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['netpay_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Jadwal Pasang</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($tanggalPemasangan) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Jam</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($jamPemasangan) ?></span>
                                    </li>
                                    <!-- <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Request By</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['request_by']) ?></span>
                                    </li> -->
                                    <li class="list-group-item px-5 py-3">
                                        <div class="text-muted small font-weight-bold mb-1">Catatan</div>
                                        <div class="font-weight-bold text-dark"><?= sch_h($row['catatan']) ?></div>
                                    </li>
                                </ul>
                            </div>

                        <?php elseif ($row['type_queue'] === 'Service'): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="icon-box bg-light-warning text-warning mr-3">
                                        <?= sch_icon('tool') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark">Data Request Service</span>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">RM ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['rm_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Netpay ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['netpay_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Type Issue</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['type_issue']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Request By</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['request_by']) ?></span>
                                    </li>
                                    <li class="list-group-item px-5 py-3">
                                        <div class="text-muted small font-weight-bold mb-1">Deskripsi Issue</div>
                                        <div class="font-weight-bold text-dark"><?= sch_h($row['deskripsi_issue']) ?></div>
                                    </li>
                                </ul>
                            </div>

                        <?php elseif ($row['type_queue'] === 'Dismantle'): ?>
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="icon-box bg-light-danger text-danger mr-3">
                                        <?= sch_icon('x-circle') ?>
                                    </div>
                                    <span class="font-weight-bolder text-dark">Data Request Dismantle</span>
                                </div>
                                <div class="card-toolbar">
                                    <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                        <i class="ki ki-arrow-down icon-nm"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">RD ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['rd_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Netpay ID</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['netpay_id']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Type Dismantle</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['type_dismantle']) ?></span>
                                    </li>
                                    <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                        <span class="label-col text-muted small font-weight-bold">Request By</span>
                                        <span class="font-weight-bold text-dark text-right"><?= sch_h($row['request_by']) ?></span>
                                    </li>
                                    <li class="list-group-item px-5 py-3">
                                        <div class="text-muted small font-weight-bold mb-1">Deskripsi Dismantle</div>
                                        <div class="font-weight-bold text-dark"><?= sch_h($row['deskripsi_dismantle']) ?></div>
                                    </li>
                                </ul>
                            </div>

                        <?php endif; ?>
                    </div>

                    <!-- Queue Info -->
                    <div class="card card-custom shadow-sm border-0 mb-5" data-card="true">
                        <div class="card-header">
                            <div class="card-title">
                                <div class="icon-box bg-light-primary text-primary mr-3">
                                    <?= sch_icon('hash') ?>
                                </div>
                                <span class="font-weight-bolder text-dark">Info Antrian</span>
                            </div>
                            <div class="card-toolbar">
                                <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                    <i class="ki ki-arrow-down icon-nm"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Queue ID</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($row['queue_id']) ?></span>
                                </li>
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Type Queue</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($row['type_queue']) ?></span>
                                </li>

                                <li class="list-group-item info-item d-flex justify-content-between align-items-center px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Status</span>
                                    <span class="badge badge-pill badge-<?= sch_h($statusClasses[$row['status']] ?? 'secondary') ?>">
                                        <?= sch_h($row['status']) ?>
                                    </span>
                                </li>
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Dibuat</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($cr) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Data Customer -->
                    <div class="card card-custom shadow-sm border-0 mb-5" data-card="true">
                        <div class="card-header">
                            <div class="card-title">
                                <div class="icon-box bg-light-primary text-primary mr-3">
                                    <?= sch_icon('user') ?>
                                </div>
                                <span class="font-weight-bolder text-dark">Data Customer</span>
                            </div>
                            <div class="card-toolbar">
                                <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary" data-card-tool="toggle">
                                    <i class="ki ki-arrow-down icon-nm"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Netpay ID</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($row['netpay_id']) ?></span>
                                </li>
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Nama</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($row['name']) ?></span>
                                </li>
                                <li class="list-group-item info-item d-flex justify-content-between align-items-center px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">No. HP</span>
                                    <?php if (!empty($row['phone'])): ?>
                                        <a href="tel:<?= sch_h($row['phone']) ?>" class="font-weight-bold text-dark"><?= sch_h($row['phone']) ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item info-item d-flex justify-content-between align-items-start px-5 py-3">
                                    <span class="label-col text-muted small font-weight-bold">Paket</span>
                                    <span class="font-weight-bold text-dark text-right"><?= sch_h($row['paket_internet']) ?> Mbps</span>
                                </li>
                                <li class="list-group-item px-5 py-3">
                                    <div class="text-muted small font-weight-bold mb-1">Lokasi</div>
                                    <div class="font-weight-bold text-dark"><?= sch_h($row['location']) ?></div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
                <!-- /kanan -->

            </div>
            <!-- /row -->

        </div>
    </div>
    <!--end::Entry-->

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

<script>
    const timeInput = document.getElementById('time-schedule');

    /* ===== Hanya angka ===== */
    timeInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d]/g, '');

        if (value.length >= 3) {
            value = value.substring(0, 2) + ':' + value.substring(2, 4);
        }

        e.target.value = value;
    });

    /* ===== Validasi ===== */
    timeInput.addEventListener('blur', function(e) {
        const regex = /^([01]\d|2[0-3]):([0-5]\d)$/;

        if (!regex.test(e.target.value)) {
            alert('Jam harus format 00:00 - 23:59');
            e.target.value = "<?= sch_h($jamSchedule) ?>";
        }
    });
    $(document).ready(function() {
        getJadwalTeknisi();
        // getCustomer()
    });
</script>