<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'dismantle';

try {
    $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
    if (!$id) redirect("pages/dismantle/");

    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.tech_id,c.*, q.queue_id
        FROM schedules s
        JOIN queue_scheduling q ON s.queue_id = q.queue_id
        JOIN customers c ON q.netpay_id = c.netpay_id
        WHERE s.schedule_id = :id
    ");
    $stmt->execute([':id' => $id]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception();

    $dismantle_id = "DR" . date("YmdHis");

    // Ambil alasan dari request_dismantle via queue
    $reasonStmt = $pdo->prepare("
        SELECT type_dismantle
        FROM request_dismantle 
        WHERE queue_id = :id
    ");
    $reasonStmt->execute([':id' => $customer['queue_id']]);
    $reason = $reasonStmt->fetchColumn();

    if (!$reason) throw new Exception("Alasan dismantle tidak ditemukan");

    // Deteksi tim / single tech
    $isTim       = strpos($customer['tech_id'], 'TIM') === 0;
    $teamMembers = [];

    if ($isTim) {
        $q = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tim_id = :id");
        $q->execute([':id' => $customer['tech_id']]);
        $teamMembers = $q->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $q = $pdo->prepare("SELECT tech_id, name FROM technician WHERE tech_id = :id");
        $q->execute([':id' => $customer['tech_id']]);
        $single = $q->fetch(PDO::FETCH_ASSOC);
        if ($single) $teamMembers = [$single];
    }
} catch (Throwable $e) {
    echo $e->getMessage();
    // redirect("pages/dismantle/");
}

$cust_address = trim(($customer['perumahan'] ?? '') . ' ' . ($customer['location'] ?? ''));

// Format phone ke WA internasional (sama seperti service report)
$phone_raw = preg_replace('/[^0-9]/', '', (string)($customer['phone_contact'] ?? $customer['phone'] ?? ''));
if ($phone_raw !== '' && substr($phone_raw, 0, 1) === '0') {
    $phone_wa = '62' . substr($phone_raw, 1);
} else {
    $phone_wa = $phone_raw;
}

$enable_rating = isRatingEnabled($pdo);

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

    .btn-rating {
        background: linear-gradient(135deg, #10B981, #34D399);
        color: #fff !important;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 14px;
        padding: 14px;
        box-shadow: 0 4px 14px rgba(16, 185, 129, .35);
        transition: opacity .15s;
    }

    .btn-rating:disabled {
        opacity: .7;
        cursor: not-allowed;
    }

    .btn-rating.sent {
        background: #F0FDF4 !important;
        color: #059669 !important;
        border: 2px solid #10B981 !important;
        box-shadow: none !important;
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

        <form id="formDismantle" method="post"
            action="<?= BASE_URL ?>controllers/report/dismantle/create.php"
            novalidate>

            <input type="hidden" name="dismantle_id" value="<?= htmlspecialchars($dismantle_id) ?>">
            <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($customer['schedule_id'] ?? '') ?>">
            <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($customer['netpay_id']   ?? '') ?>">
            <input type="hidden" name="alasan" value="<?= htmlspecialchars($reason) ?>">

            <div id="captureForm">

                <!-- HERO -->
                <div class="card sr-card shadow-sm mt-3 mb-3">
                    <span class="sr-stripe" style="background:linear-gradient(90deg,#DC2626,#EF4444)"></span>
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge px-3 py-2"
                                style="background:#FEE2E2;color:#991B1B;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.5px">
                                🔌 DISMANTLE REPORT
                            </span>
                            <small class="text-muted font-weight-bold"><?= htmlspecialchars($dismantle_id) ?></small>
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
                                <input type="date" class="form-control sr-control"
                                    name="tanggal" value="<?= date('Y-m-d') ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">Jam</label>
                                <input type="time" class="form-control sr-control"
                                    name="jam" value="<?= date('H:i') ?>" readonly>
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

                                <?= htmlspecialchars($reason) ?>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="form-group">
                            <label class="sr-label">Action <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="action_select">
                                <option value="">-- Pilih Action --</option>
                                <option>Cabut Perangkat</option>
                                <option>Ambil Modem</option>
                                <option>Nonaktifkan ONU</option>
                                <option>Update Sistem</option>
                                <option value="__custom__">Lainnya...</option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 d-none"
                                id="action_custom" placeholder="Masukkan action lain...">
                            <input type="hidden" name="action" id="action_final">
                            <div class="field-feedback d-none" id="action_feedback">⚠️ Action wajib diisi</div>
                        </div>

                        <!-- Part Removed -->
                        <div class="form-group">
                            <label class="sr-label">Part Removed <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="part_select">
                                <option value="">-- Pilih Part --</option>
                                <option>Modem</option>
                                <option>Adaptor</option>
                                <option>Kabel Dropcore</option>
                                <option>Router</option>
                                <option value="__custom__">Lainnya...</option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 d-none"
                                id="part_custom" placeholder="Masukkan part lain...">
                            <input type="hidden" name="part_removed" id="part_final">
                            <div class="field-feedback d-none" id="part_feedback">⚠️ Part removed wajib diisi</div>
                        </div>

                        <!-- Kondisi Perangkat -->
                        <div class="form-group mb-0">
                            <label class="sr-label">Kondisi Perangkat <span class="text-danger">*</span></label>
                            <select class="form-control sr-control" id="kondisi_select">
                                <option value="">-- Pilih Kondisi --</option>
                                <option>Baik</option>
                                <option>Rusak</option>
                                <option>Tidak Lengkap</option>
                                <option>Layak Pakai</option>
                                <option>Tidak Layak</option>
                                <option value="__custom__">Lainnya...</option>
                            </select>
                            <input type="text" class="form-control sr-control mt-2 d-none"
                                id="kondisi_custom" placeholder="Masukkan kondisi lain...">
                            <input type="hidden" name="kondisi_perangkat" id="kondisi_final">
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
                                        checked>
                                    <label class="pic-label" for="pic_<?= htmlspecialchars($tm['tech_id']) ?>">
                                        <?= htmlspecialchars($tm['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <input class="form-control sr-control" readonly
                                name="pic[]"
                                value="<?= htmlspecialchars($customer['tech_id'] ?? '') ?>">
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
                        <textarea class="form-control sr-control validate-field"
                            name="keterangan" rows="4"
                            placeholder="Kondisi akhir, catatan perangkat, informasi tambahan...">Done</textarea>
                        <div class="invalid-feedback">⚠️ Keterangan wajib diisi</div>
                    </div>
                </div>

            </div><!-- #captureForm -->

            <!-- BUTTONS -->
            <?php if ($enable_rating): ?>
            <button type="button" id="btnRating" onclick="sendRating()"
                class="btn btn-rating btn-block mb-3">
                ⭐ Kirim Link Rating ke Customer
            </button>
            <?php endif; ?>

            <button type="button" onclick="takeScreenshot()"
                class="btn btn-screenshot btn-block mb-3">
                Screenshot Report
            </button>

            <!-- Disabled dulu jika rating aktif, aktif langsung jika rating dinonaktifkan -->
            <button type="button" id="btnSubmit" onclick="submitAndShare()"
                class="btn btn-submit-dr btn-block mb-4" <?= $enable_rating ? 'disabled' : '' ?>>
                Submit & Share
            </button>

        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    var ratingSent = <?= $enable_rating ? 'false' : 'true' ?>;

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

    var RATING_DATA = {
        schedule_id: '<?= $customer['schedule_id'] ?? '' ?>',
        tech_id: '<?= addslashes($customer['tech_id'] ?? '') ?>',
        netpay_id: '<?= ($customer['netpay_id'] ?? '') ?>',
        phone_contact: '<?= $phone_wa ?>'
    };

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

    // ── Kirim Rating ──────────────────────────────────────────────────
    async function sendRating() {
        if (!validateForm()) {
            Swal.fire({
                icon: 'warning',
                title: 'Form belum lengkap',
                text: 'Lengkapi laporan terlebih dahulu sebelum mengirim link rating.'
            });
            return;
        }

        var btn = document.getElementById('btnRating');
        var originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Membuat link...';

        var form = document.getElementById('formDismantle');
        var formData = new FormData(form);

        try {
            var res = await fetch('<?= BASE_URL ?>controllers/rating/create_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    schedule_id: RATING_DATA.schedule_id,
                    tech_id: RATING_DATA.tech_id,
                    netpay_id: RATING_DATA.netpay_id,
                    pics: formData.getAll('pic[]')
                })
            });
            var result = await res.json();

            if (!result.status) throw new Error(result.message || 'Gagal membuat token');

            var ratingUrl = '<?= BASE_URL ?>pages/rating/index.php?token=' + result.token;

            var pesan =
                'Halo ' + CUSTOMER_DATA.name + ' \n\n' +
                'Teknisi kami telah selesai mengerjakan pekerjaan di lokasi Anda.\n\n' +
                'Mohon bantu berikan penilaian melalui link berikut:\n\n' +
                ratingUrl +
                '\n\nTerima kasih atas waktu dan penilaiannya ';

            window.open('https://wa.me/' + RATING_DATA.phone_contact + '?text=' + encodeURIComponent(pesan));

            ratingSent = true;

            btn.disabled = false;
            btn.classList.add('sent');
            btn.innerHTML = '✅ Kirim Ulang Link Rating';

            document.getElementById('btnSubmit').disabled = false;

            Swal.fire({
                icon: 'success',
                title: 'Link rating terkirim!',
                text: 'Sekarang isi form laporan lalu klik Submit.',
                confirmButtonText: 'Oke, lanjut'
            });

        } catch (e) {
            console.error(e);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: e.message || 'Terjadi error, coba lagi.'
            });
        }
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
    async function submitAndShare() {
        if (!ratingSent) {
            Swal.fire({
                icon: 'warning',
                title: 'Rating belum dikirim!',
                text: 'Kirim link rating ke customer dulu.'
            });
            return;
        }
        if (!validateForm()) return;

        var btn = document.getElementById('btnSubmit');
        var originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Mengirim...';

        var form = document.getElementById('formDismantle');
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
                    '📄 *DISMANTLE REPORT*\nID: ' + formData.get('dismantle_id') + '\n\n' +
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
                    try {
                        await navigator.share({
                            title: 'Dismantle Report',
                            text: text
                        });
                    } catch (shareErr) {
                        console.log('Share dibatalkan atau tidak didukung:', shareErr);
                    }
                } else {
                    window.open('https://wa.me/?text=' + encodeURIComponent(text));
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Laporan  berhasil disimpan.',
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
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var saved = <?= json_encode(getDailyShiftTeam($pdo, $customer['tech_id'] ?? '', $_SESSION['id_karyawan'] ?? '')) ?>;
        if (!saved || !Array.isArray(saved) || saved.length === 0) {
            var d = new Date();
            var month = '' + (d.getMonth() + 1);
            var day = '' + d.getDate();
            var year = d.getFullYear();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            var key = 'ops_daily_team_' + [year, month, day].join('-');

            var raw = localStorage.getItem(key);
            if (raw) {
                try { saved = JSON.parse(raw); } catch(e) {}
            }
        }

        if (saved && Array.isArray(saved) && saved.length > 0) {
            document.querySelectorAll('input[name="pic[]"]').forEach(function(chk) {
                if (chk.type === 'checkbox') {
                    chk.checked = saved.includes(chk.value);
                }
            });
        }
    });
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>