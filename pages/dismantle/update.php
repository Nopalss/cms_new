<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'dismantle';

try {


    $id = $_GET['id'] ?? null;
    if (!$id) redirect("pages/dismantle/");

    $stmt = $pdo->prepare("
        SELECT 
            dr.*,
            c.*,
            s.tech_id AS schedule_tech
        FROM dismantle_reports dr
        JOIN customers c ON dr.netpay_id = c.netpay_id
        LEFT JOIN schedules s ON dr.schedule_id = s.schedule_id
        WHERE dr.dismantle_id = :id
    ");
    $stmt->execute([':id' => $id]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception();

    $q = $pdo->prepare("
        SELECT drp.tech_id, t.name
        FROM dismantle_report_pic drp
        LEFT JOIN technician t ON drp.tech_id = t.tech_id
        WHERE drp.dismantle_id = :id
    ");
    $q->execute([':id' => $id]);
    $currentPics   = $q->fetchAll(PDO::FETCH_ASSOC);
    $selectedPic = array_column($currentPics, 'tech_id');
    $isTim       = strpos($customer['schedule_tech'], 'TIM') === 0;
    $teamMembers = [];

    if ($isTim) {
        $q = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tim_id = :id");
        $q->execute([':id' => $customer['schedule_tech']]);
        $teamMembers = $q->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if (!empty($customer['schedule_tech'])) {
            $q = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tech_id = :id");
            $q->execute([':id' => $customer['schedule_tech']]);
            $tech = $q->fetch(PDO::FETCH_ASSOC);
            if ($tech) $teamMembers[] = $tech;
        }
    }

    $cust_address = trim(($customer['perumahan'] ?? '') . ' ' . ($customer['location'] ?? ''));

    $actions     = ['Cabut Perangkat', 'Ambil Modem', 'Nonaktifkan ONU', 'Update Sistem'];
    $parts       = ['Modem', 'Adaptor', 'Kabel Dropcore', 'Router'];
    $kondisiList = ['Baik', 'Rusak', 'Tidak Lengkap', 'Layak Pakai', 'Tidak Layak'];
} catch (Throwable $e) {
    redirect("pages/dismantle/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>


<style>
   

    .sr-card {
        border: none !important;
        border-radius: 14px !important;
        overflow: hidden;
    }

    .sr-card .card-header {
        background: #fff !important;
        border-bottom: 1px solid #F0F4FA !important;
        padding: 12px 16px;
    }

    .sr-card .card-body {
        padding: 16px;
    }

    .sr-stripe {
        height: 4px;
        display: block;
    }

    .sr-sec-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
        margin-right: 8px;
    }

    .sr-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748B;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 6px;
        display: block;
    }

    .sr-control {
        border-radius: 10px !important;
        border: 1.5px solid #E2E8F0 !important;
        background: #F8FAFF !important;
        font-size: 14px !important;
        min-height: 48px;
        transition: border-color .15s, box-shadow .15s !important;
    }

    select.sr-control {
        -webkit-appearance: auto;
        appearance: auto;
    }

    .sr-control:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15) !important;
        background: #fff !important;
        outline: none !important;
    }

    .sr-control.is-invalid {
        border-color: #EF4444 !important;
        background: #FFF5F5 !important;
    }

    .sr-control.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239, 68, 68, .15) !important;
    }

    textarea.sr-control {
        min-height: 90px;
        padding-top: 12px;
    }

    .invalid-feedback {
        font-size: 12px !important;
        font-weight: 600 !important;
        margin-top: 6px !important;
        display: none;
    }

    .sr-control.is-invalid~.invalid-feedback {
        display: block !important;
    }

    .field-feedback {
        color: #EF4444;
        font-size: 12px;
        font-weight: 600;
        margin-top: 6px;
    }

    .pic-check {
        display: none !important;
    }

    .pic-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border: 2px solid #E2E8F0;
        border-radius: 50px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background: #F8FAFF;
        margin: 3px 3px 3px 0;
        transition: all .15s;
        user-select: none;
    }

    .pic-check:checked+.pic-label {
        background: #2563EB;
        border-color: #2563EB;
        color: #fff;
    }

    .alasan-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #FEF2F2;
        color: #991B1B;
        border: 1.5px solid #FECACA;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 700;
    }

    .btn-screenshot {
        background: #F0F4FA;
        color: #475569;
        border: 1.5px solid #E2E8F0;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        padding: 13px;
        transition: background .15s;
    }

    .btn-screenshot:hover {
        background: #E2E8F0;
        color: #0F172A;
    }

    .btn-submit-dr {
        background: linear-gradient(135deg, #DC2626, #EF4444);
        color: #fff !important;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 15px;
        padding: 15px;
        box-shadow: 0 4px 14px rgba(220, 38, 38, .35);
        transition: opacity .15s;
    }

    .btn-submit-dr:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .btn-submit-dr:not(:disabled):hover {
        opacity: .92;
        color: #fff;
    }
</style>

<div class="content">
    <div class="container px-3 pb-5">

        <div id="validationAlert" class="alert alert-danger d-none mt-3 mb-0" role="alert"
            style="border-radius:12px; font-size:13px; font-weight:600">
            ⚠️ <strong>Form belum lengkap!</strong> Cek field yang merah di bawah.
        </div>

        <form id="formUpdate" method="post"
            action="<?= BASE_URL ?>controllers/report/dismantle/update.php"
            novalidate>

            <input type="hidden" name="dismantle_id" value="<?= htmlspecialchars($customer['dismantle_id']) ?>">
            <input type="hidden" name="dismantle_id" value="<?= htmlspecialchars($customer['dismantle_id'])  ?>">
            <input type="hidden" name="alasan" value="<?= htmlspecialchars($customer['alasan'])        ?>">

            <div id="captureForm">

                <!-- HERO -->
                <div class="card sr-card shadow-sm mt-3 mb-3">
                    <span class="sr-stripe" style="background:linear-gradient(90deg,#DC2626,#EF4444)"></span>
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge px-3 py-2"
                                style="background:#FEE2E2;color:#991B1B;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.5px">
                                ✏️ UPDATE DISMANTLE REPORT
                            </span>
                            <small class="text-muted font-weight-bold"><?= htmlspecialchars($customer['dismantle_id']) ?></small>
                        </div>
                        <h5 class="font-weight-bolder mb-2" style="color:#0F172A;letter-spacing:-.3px">
                            <?= htmlspecialchars($customer['name'] ?? '-') ?>
                        </h5>
                        <div class="d-flex flex-wrap" style="gap:6px">
                            <span class="badge badge-light py-2 px-3" style="border-radius:20px;font-size:12px">
                                📞 <?= htmlspecialchars($customer['phone'] ?? '-') ?>
                            </span>
                            <span class="badge badge-light py-2 px-3" style="border-radius:20px;font-size:12px">
                                📶 <?= htmlspecialchars($customer['paket_internet'] ?? '-') ?> Mbps
                            </span>
                            <span class="badge badge-light py-2 px-3" style="border-radius:20px;font-size:12px">
                                📍 <?= htmlspecialchars($cust_address ?: '-') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- WAKTU -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#FEF3C7;color:#D97706">⏰</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Waktu</span>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-6">
                                <label class="sr-label">Tanggal</label>
                                <input type="date" class="form-control sr-control" name="tanggal"
                                    value="<?= htmlspecialchars($customer['tanggal']) ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">Jam</label>
                                <input type="time" class="form-control sr-control" name="jam"
                                    value="<?= htmlspecialchars($customer['jam']) ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DETAIL DISMANTLE -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#FEE2E2;color:#DC2626">🔌</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Detail Dismantle</span>
                    </div>
                    <div class="card-body">

                        <!-- Alasan readonly -->
                        <div class="form-group">
                            <label class="sr-label">Alasan Dismantle</label>
                            <div class="alasan-badge">
                                <i class="fa fa-exclamation-circle" style="flex-shrink:0"></i>
                                <?= htmlspecialchars($customer['alasan']) ?>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="form-group">
                            <label class="sr-label">Action <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="action_select">
                                <option value="">-- Pilih Action --</option>
                                <?php foreach ($actions as $a): ?>
                                    <option <?= $customer['action'] === $a ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= !in_array($customer['action'], $actions) ? 'selected' : '' ?>>
                                    Lainnya...
                                </option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 <?= in_array($customer['action'], $actions) ? 'd-none' : '' ?>"
                                id="action_custom"
                                placeholder="Masukkan action lain..."
                                value="<?= !in_array($customer['action'], $actions) ? htmlspecialchars($customer['action']) : '' ?>">
                            <input type="hidden" name="action" id="action_final"
                                value="<?= htmlspecialchars($customer['action']) ?>">
                            <div class="field-feedback d-none" id="action_feedback">⚠️ Action wajib diisi</div>
                        </div>

                        <!-- Part Removed -->
                        <div class="form-group">
                            <label class="sr-label">Part Removed <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="part_select">
                                <option value="">-- Pilih Part --</option>
                                <?php foreach ($parts as $p): ?>
                                    <option <?= $customer['part_removed'] === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= !in_array($customer['part_removed'], $parts) ? 'selected' : '' ?>>
                                    Lainnya...
                                </option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 <?= in_array($customer['part_removed'], $parts) ? 'd-none' : '' ?>"
                                id="part_custom"
                                placeholder="Masukkan part lain..."
                                value="<?= !in_array($customer['part_removed'], $parts) ? htmlspecialchars($customer['part_removed']) : '' ?>">
                            <input type="hidden" name="part_removed" id="part_final"
                                value="<?= htmlspecialchars($customer['part_removed']) ?>">
                            <div class="field-feedback d-none" id="part_feedback">⚠️ Part removed wajib diisi</div>
                        </div>

                        <!-- Kondisi Perangkat -->
                        <div class="form-group mb-0">
                            <label class="sr-label">Kondisi Perangkat <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="kondisi_select">
                                <option value="">-- Pilih Kondisi --</option>
                                <?php foreach ($kondisiList as $k): ?>
                                    <option <?= $customer['kondisi_perangkat'] === $k ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="__custom__" <?= !in_array($customer['kondisi_perangkat'], $kondisiList) ? 'selected' : '' ?>>
                                    Lainnya...
                                </option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 <?= in_array($customer['kondisi_perangkat'], $kondisiList) ? 'd-none' : '' ?>"
                                id="kondisi_custom"
                                placeholder="Masukkan kondisi lain..."
                                value="<?= !in_array($customer['kondisi_perangkat'], $kondisiList) ? htmlspecialchars($customer['kondisi_perangkat']) : '' ?>">
                            <input type="hidden" name="kondisi_perangkat" id="kondisi_final"
                                value="<?= htmlspecialchars($customer['kondisi_perangkat']) ?>">
                            <div class="field-feedback d-none" id="kondisi_feedback">⚠️ Kondisi perangkat wajib diisi</div>
                        </div>

                    </div>
                </div>

                <!-- PIC TEKNISI -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#F3E8FF;color:#9333EA">👤</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">PIC Teknisi</span>
                    </div>
                    <div class="card-body">
                        <?php if ($isTim): ?>
                            <p class="text-muted mb-2" style="font-size:12px">Pilih teknisi yang terlibat:</p>
                            <div class="pic-box">
                                <?php foreach ($teamMembers as $tm): ?>
                                    <input class="pic-check" type="checkbox"
                                        name="pic[]"
                                        id="pic_<?= htmlspecialchars($tm['tech_id']) ?>"
                                        value="<?= htmlspecialchars($tm['tech_id']) ?>"
                                        <?= in_array($tm['tech_id'], $selectedPic) ? 'checked' : '' ?>>
                                    <label class="pic-label" for="pic_<?= htmlspecialchars($tm['tech_id']) ?>">
                                        <?= htmlspecialchars($tm['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <input class="form-control sr-control" readonly
                                name="pic[]"
                                value="<?= htmlspecialchars($customer['schedule_tech'] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KETERANGAN -->
                <div class="card sr-card shadow-sm mb-4">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#FEF3C7;color:#D97706">📝</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Keterangan</span>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control sr-control validate-field" name="keterangan" rows="4"
                            placeholder="Kondisi akhir, catatan perangkat, informasi tambahan..."><?= htmlspecialchars($customer['keterangan']) ?></textarea>
                        <div class="invalid-feedback">⚠️ Keterangan wajib diisi</div>
                    </div>
                </div>

            </div><!-- #captureForm -->

            <!-- BUTTONS -->
            <button type="button" onclick="takeScreenshot()"
                class="btn btn-screenshot btn-block mb-3">
                📷 Screenshot Report
            </button>

            <button type="button" id="btnSubmit" onclick="submitUpdate()"
                class="btn btn-submit-dr btn-block mb-4">
                ✅ Update & Share
            </button>

        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    var TECHNICIAN_MAP = <?= json_encode(
                                array_reduce($teamMembers, function ($carry, $item) {
                                    $carry[$item['tech_id']] = $item['name'];
                                    return $carry;
                                }, [])
                            ) ?>;

    var CUSTOMER_DATA = <?= json_encode([
                            'name'      => $customer['name']      ?? '',
                            'phone'     => $customer['phone']     ?? '',
                            'netpay_id' => $customer['netpay_id'] ?? '',
                            'address'   => $cust_address,
                        ]) ?>;

    // ── Config dropdown ───────────────────────────────────────────────
    var DROPDOWNS = [{
            select: 'action_select',
            custom: 'action_custom',
            final: 'action_final',
            feedback: 'action_feedback'
        },
        {
            select: 'part_select',
            custom: 'part_custom',
            final: 'part_final',
            feedback: 'part_feedback'
        },
        {
            select: 'kondisi_select',
            custom: 'kondisi_custom',
            final: 'kondisi_final',
            feedback: 'kondisi_feedback'
        },
    ];

    DROPDOWNS.forEach(function(d) {
        var selectEl = document.getElementById(d.select);
        var customEl = document.getElementById(d.custom);
        var finalEl = document.getElementById(d.final);
        var feedbackEl = document.getElementById(d.feedback);

        selectEl.addEventListener('change', function() {
            if (this.value === '__custom__') {
                customEl.classList.remove('d-none');
                finalEl.value = '';
            } else {
                customEl.classList.add('d-none');
                finalEl.value = this.value;
            }
            selectEl.classList.remove('is-invalid');
            customEl.classList.remove('is-invalid');
            feedbackEl.classList.add('d-none');
        });

        customEl.addEventListener('input', function() {
            finalEl.value = this.value;
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                feedbackEl.classList.add('d-none');
            }
        });
    });

    // ── Live clear error untuk textarea ──────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.validate-field').forEach(function(el) {
            el.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    if (!document.querySelectorAll('.sr-control.is-invalid').length) {
                        document.getElementById('validationAlert').classList.add('d-none');
                    }
                }
            });
        });
    });

    // ── Validasi satu dropdown ────────────────────────────────────────
    function validateDropdown(d) {
        var selectEl = document.getElementById(d.select);
        var customEl = document.getElementById(d.custom);
        var feedbackEl = document.getElementById(d.feedback);

        if (selectEl.value === '') {
            selectEl.classList.add('is-invalid');
            feedbackEl.classList.remove('d-none');
            return false;
        }
        if (selectEl.value === '__custom__' && !customEl.value.trim()) {
            customEl.classList.add('is-invalid');
            feedbackEl.classList.remove('d-none');
            return false;
        }
        selectEl.classList.remove('is-invalid');
        customEl.classList.remove('is-invalid');
        feedbackEl.classList.add('d-none');
        return true;
    }

    // ── Validasi seluruh form ─────────────────────────────────────────
    function validateForm() {
        var isValid = true,
            firstError = null;

        DROPDOWNS.forEach(function(d) {
            if (!validateDropdown(d)) {
                isValid = false;
                if (!firstError) firstError = document.getElementById(d.select);
            }
        });

        document.querySelectorAll('.validate-field').forEach(function(el) {
            if (!el.value.trim()) {
                el.classList.add('is-invalid');
                isValid = false;
                if (!firstError) firstError = el;
            } else {
                el.classList.remove('is-invalid');
            }
        });

        var alertBanner = document.getElementById('validationAlert');
        if (!isValid) {
            alertBanner.classList.remove('d-none');
            if (firstError) firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        } else {
            alertBanner.classList.add('d-none');
        }
        return isValid;
    }

    // ── Screenshot ────────────────────────────────────────────────────
    function takeScreenshot() {
        html2canvas(document.getElementById('captureForm'), {
            scale: 2
        }).then(function(canvas) {
            var link = document.createElement('a');
            link.download = 'dismantle-report-' + Date.now() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    // ── Format tanggal ────────────────────────────────────────────────
    function formatTanggal(tgl) {
        var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        var d = new Date(tgl);
        return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    }

    // ── Submit & Share ────────────────────────────────────────────────
    async function submitUpdate() {
        if (!validateForm()) return;

        var btn = document.getElementById('btnSubmit');
        var originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Mengirim...';

        var form = document.getElementById('formUpdate');
        var formData = new FormData(form);
        var picIds = formData.getAll('pic[]');
        var teknisiText = picIds.map(function(id) {
            return TECHNICIAN_MAP[id] || id;
        }).join(', ');

        try {
            var res = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData
            });
            var result = await res.json();

            if (result.status) {
                var text =
                    '📄 *DISMANTLE REPORT (UPDATED)*\nID: ' + formData.get('dismantle_id') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🕒 *Waktu*\n' +
                    formatTanggal(formData.get('tanggal')) + ' • ' + formData.get('jam') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🔌 *Detail Dismantle*\n' +
                    'Alasan        : ' + formData.get('alasan') + '\n' +
                    'Action        : ' + formData.get('action') + '\n' +
                    'Part Removed  : ' + formData.get('part_removed') + '\n' +
                    'Kondisi       : ' + formData.get('kondisi_perangkat') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n📝 *Keterangan*\n' + formData.get('keterangan') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n👤 *Customer*\n' +
                    'Nama    : ' + CUSTOMER_DATA.name + '\n' +
                    'No HP   : ' + CUSTOMER_DATA.phone + '\n' +
                    'Netpay  : ' + CUSTOMER_DATA.netpay_id + '\n' +
                    'Alamat  : ' + CUSTOMER_DATA.address + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🛠️ *Teknisi*\n' + teknisiText + '\n━━━━━━━━━━━━━━━━━━';

                if (navigator.share) {
                    await navigator.share({
                        title: 'Dismantle Report',
                        text: text
                    });
                } else {
                    window.open('https://wa.me/?text=' + encodeURIComponent(text));
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Laporan  berhasil diperbarui.',
                    confirmButtonText: 'OK'
                }).then(function() {
                    window.location.href = '<?= BASE_URL ?>pages/dismantle/';
                });

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: result.message || 'Terjadi error.'
                });
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi error, silakan coba lagi.'
            });
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>