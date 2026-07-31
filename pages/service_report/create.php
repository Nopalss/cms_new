<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'service';

try {
    $id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);
    if (!$id) redirect("pages/service_report/");

    $stmt = $pdo->prepare("
        SELECT s.*, q.netpay_id,
            COALESCE(c.name, rm.nama, 'Infrastruktur Jaringan') AS name,
            COALESCE(c.perumahan, rm.perumahan, '-') AS perumahan,
            COALESCE(c.location, rm.location, '-') AS location,
            c.phone, c.phone_contact,
            cd.modem_sn, cd.device_brand,
            rm.verifikasi_noc, rm.type_issue, rm.deskripsi_issue,
            (SELECT sn FROM ikr_report WHERE netpay_id = c.netpay_id AND sn IS NOT NULL AND TRIM(sn) <> '' ORDER BY ikr_key DESC LIMIT 1) AS ikr_sn
        FROM schedules s
        JOIN queue_scheduling q ON s.queue_id = q.queue_id
        LEFT JOIN customers c ON q.netpay_id = c.netpay_id
        LEFT JOIN customer_details cd ON c.netpay_id = cd.netpay_id
        LEFT JOIN request_maintenance rm ON q.queue_id = rm.queue_id
        WHERE s.schedule_id = :id
    ");
    $stmt->execute([':id' => $id]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception();

    $srv_id = "SR" . date("YmdHis");

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
    redirect("pages/service_report/");
}

// ── Build variables ──────────────────────────────────────────────
$cust_address = trim(($customer['perumahan'] ?? '') . ' ' . ($customer['location'] ?? ''));

// Pre-fill ONT Lama (S/N) & Problem (Verifikasi NOC)
$ont_lama_default = !empty($customer['modem_sn']) ? $customer['modem_sn'] : (!empty($customer['ikr_sn']) ? $customer['ikr_sn'] : '');
$problem_default  = !empty($customer['verifikasi_noc']) ? $customer['verifikasi_noc'] : (!empty($customer['type_issue']) ? $customer['type_issue'] : (!empty($customer['deskripsi_issue']) ? $customer['deskripsi_issue'] : ''));

// Format phone_contact ke format WA internasional (08... → 628...)
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

    .btn-submit-main {
        background: linear-gradient(135deg, #2563EB, #3B82F6);
        color: #fff !important;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 15px;
        padding: 15px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .35);
        transition: opacity .15s;
    }

    .btn-submit-main:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .btn-submit-main:not(:disabled):hover {
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

        <form id="formReport" method="post"
            action="<?= BASE_URL ?>controllers/report/service/create.php"
            novalidate>

            <input type="hidden" name="srv_id" value="<?= htmlspecialchars($srv_id) ?>">
            <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($customer['schedule_id'] ?? '') ?>">
            <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($customer['netpay_id']   ?? '') ?>">

            <div id="captureForm">

                <!-- HERO -->
                <div class="card sr-card shadow-sm mt-3 mb-3">
                    <span class="sr-stripe" style="background:linear-gradient(90deg,#F59E0B,#FBBF24)"></span>
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge px-3 py-2"
                                style="background:#FEF3C7;color:#92400E;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.5px">
                                🔧 SERVICE REPORT
                            </span>
                            <small class="text-muted font-weight-bold"><?= htmlspecialchars($srv_id) ?></small>
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
                                <input type="date" class="form-control sr-control" name="tanggal" value="<?= date('Y-m-d') ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">Jam</label>
                                <input type="time" class="form-control sr-control" name="jam" value="<?= date('H:i') ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DETAIL PEKERJAAN -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#EFF6FF;color:#2563EB">⚙️</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Detail Pekerjaan</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="sr-label">Problem <span class="text-danger">*</span></label>
                            <textarea class="form-control sr-control validate-field" name="problem" rows="3" placeholder="Jelaskan masalah yang ditemukan..."><?= htmlspecialchars($problem_default) ?></textarea>
                            <div class="invalid-feedback">⚠️ Problem wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Action <span class="text-danger">*</span></label>
                            <textarea class="form-control sr-control validate-field" name="action" rows="3" placeholder="Tindakan yang sudah dilakukan..."></textarea>
                            <div class="invalid-feedback">⚠️ Action wajib diisi</div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="sr-label">Part <span class="text-danger">*</span></label>
                            <textarea class="form-control sr-control validate-field" name="part" rows="2" placeholder="Part yang digunakan (tulis '-' jika tidak ada)"></textarea>
                            <div class="invalid-feedback">⚠️ Part wajib diisi</div>
                        </div>
                    </div>
                </div>

                <!-- ONT & REDAMAN -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#ECFDF5;color:#10B981">📡</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Data ONT & Redaman</span>
                    </div>
                    <div class="card-body">
                        <div class="form-row mb-3">
                            <div class="col-6">
                                <label class="sr-label">ONT Lama <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="ont_bef" value="<?= htmlspecialchars($ont_lama_default) ?>" placeholder="SN ONT lama">
                                <div class="invalid-feedback">⚠️ ONT Lama wajib diisi</div>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">ONT Baru <span class="text-muted" style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0">(opsional)</span></label>
                                <input class="form-control sr-control" name="ont_aft" placeholder="SN ONT baru">
                            </div>
                        </div>
                        <hr class="my-3" style="border-color:#F0F4FA">
                        <div class="form-row">
                            <div class="col-6">
                                <label class="sr-label">Red. Sebelum <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="red_bef" placeholder="-xx dBm">
                                <div class="invalid-feedback">⚠️ Redaman sebelum wajib diisi</div>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">Red. Sesudah <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="red_aft" placeholder="-xx dBm">
                                <div class="invalid-feedback">⚠️ Redaman sesudah wajib diisi</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PIC -->
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
                                        name="pic[]" id="pic_<?= htmlspecialchars($tm['tech_id']) ?>"
                                        value="<?= htmlspecialchars($tm['tech_id']) ?>" checked>
                                    <label class="pic-label" for="pic_<?= htmlspecialchars($tm['tech_id']) ?>">
                                        <?= htmlspecialchars($tm['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <input class="form-control sr-control" readonly name="pic[]"
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
                        <textarea class="form-control sr-control validate-field" name="keterangan" rows="4"
                            placeholder="Kondisi akhir, catatan tambahan, rekomendasi...">Done</textarea>
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
                class="btn btn-submit-main btn-block mb-4" <?= $enable_rating ? 'disabled' : '' ?>>
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

    // ── PENTING: phone_wa sudah diformat di PHP (08... → 628...) ────
    var RATING_DATA = {
        schedule_id: '<?= $customer['schedule_id'] ?? 0 ?>',
        tech_id: '<?= addslashes($customer['tech_id'] ?? '') ?>',
        netpay_id: '<?= $customer['netpay_id'] ?? 0 ?>',
        phone_contact: '<?= $phone_wa ?>'
    };

    var IS_NON_CUSTOMER = <?= empty($customer['netpay_id']) ? 'true' : 'false' ?>;
    var REQUIRED_FIELDS = IS_NON_CUSTOMER 
        ? ['problem', 'action', 'part'] 
        : ['problem', 'action', 'part', 'ont_bef', 'red_bef', 'red_aft', 'keterangan'];

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
        var form = document.getElementById('formReport');
        var formData = new FormData(form);
        try {
            // 1. Panggil backend → simpan token ke tabel technician_ratings
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

            // 2. Buat link pakai token dari DB
            var ratingUrl = '<?= BASE_URL ?>pages/rating/index.php?token=' + result.token;

            var pesan =
                'Halo ' + CUSTOMER_DATA.name + ' \n\n' +
                'Teknisi kami telah selesai mengerjakan pekerjaan di lokasi Anda.\n\n' +
                'Mohon bantu berikan penilaian melalui link berikut:\n\n' +
                ratingUrl +
                '\n\n' +
                'Terima kasih atas waktu dan penilaiannya ';

            // 3. Buka WA dengan link rating
            window.open('https://wa.me/' + RATING_DATA.phone_contact + '?text=' + encodeURIComponent(pesan));

            // 4. Update state — aktifkan tombol Submit
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

    // ── Validasi ──────────────────────────────────────────────────────
    function validateForm() {
        var isValid = true,
            firstError = null;
        REQUIRED_FIELDS.forEach(function(name) {
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return;
            if (!el.value.trim()) {
                el.classList.add('is-invalid');
                isValid = false;
                if (!firstError) firstError = el;
            } else {
                el.classList.remove('is-invalid');
            }
        });
        if (!isValid) {
            document.getElementById('validationAlert').classList.remove('d-none');
            if (firstError) firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        } else {
            document.getElementById('validationAlert').classList.add('d-none');
        }
        return isValid;
    }

    function takeScreenshot() {
        html2canvas(document.getElementById('captureForm'), {
            scale: 2
        }).then(function(canvas) {
            var link = document.createElement('a');
            link.download = 'service-report-' + Date.now() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    function formatTanggal(tgl) {
        var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var d = new Date(tgl);
        return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    }

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

        var form = document.getElementById('formReport');
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
                var ont = formData.get('ont_bef') + (formData.get('ont_aft') ? ' → ' + formData.get('ont_aft') : '');
                var text =
                    '📄 *SERVICE REPORT*\nID: ' + formData.get('srv_id') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🕒 *Waktu*\n' +
                    formatTanggal(formData.get('tanggal')) + ' • ' + formData.get('jam') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n⚙️ *Detail*\n' +
                    'Problem  : ' + formData.get('problem') + '\n' +
                    'Action   : ' + formData.get('action') + '\n' +
                    'Part     : ' + formData.get('part') + '\n' +
                    'ONT      : ' + ont + '\n' +
                    'Redaman  :\n  Sebelum : ' + formData.get('red_bef') + '\n  Sesudah : ' + formData.get('red_aft') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n📝 *Keterangan*\n' + formData.get('keterangan') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n👤 *Customer*\n' +
                    'Nama    : ' + CUSTOMER_DATA.name + '\nNo HP   : ' + CUSTOMER_DATA.phone +
                    '\nNetpay  : ' + CUSTOMER_DATA.netpay_id + '\nAlamat  : ' + CUSTOMER_DATA.address + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🛠️ *Teknisi*\n' + teknisiText + '\n━━━━━━━━━━━━━━━━━━';

                if (navigator.share) {
                    try {
                        await navigator.share({
                            title: 'Service Report',
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
                    text: 'Laporan berhasil disimpan.',
                    confirmButtonText: 'OK'
                }).then(() => {

                    window.location.href =
                        '<?= BASE_URL ?>pages/service_report/';

                });

            } else {
                alert(result.message || 'Gagal submit laporan.');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi error, silakan coba lagi.');
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