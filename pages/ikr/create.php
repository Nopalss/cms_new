<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
require_once __DIR__ . '/../../helper/checkRowExist.php';
$_SESSION['menu'] = 'ikr';
$id       = isset($_GET['id'])       ? $_GET['id']       : (isset($_POST['id'])       ? $_POST['id']       : null);

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Schedule ID tidak valid.',
        'button' => 'Oke',
        'style' => 'warning'
    ];
    redirect("pages/ikr/");
}

try {

    $stmt = $pdo->prepare("
        SELECT s.schedule_id, s.tech_id, c.*
        FROM schedules s
        JOIN queue_scheduling q ON s.queue_id = q.queue_id
        JOIN customers c ON q.netpay_id = c.netpay_id
        WHERE s.schedule_id = :id
    ");
    $stmt->execute([":id" => $id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    checkRowExist($customer, "pages/ikr/");

    $ikr_id = "SI" . date("YmdHis");

    $paketInternet = [];
    try {
        $stmtPaket = $pdo->query("SELECT * FROM paket_internet ORDER BY harga ASC");
        if ($stmtPaket) {
            $listPaketInternet = $stmtPaket->fetchAll(PDO::FETCH_ASSOC);
            foreach ($listPaketInternet as $p) {
                $paketInternet[$p['paket']] = $p['name'] . ' - Rp ' . number_format($p['harga'], 0, ',', '.') . '/bln';
            }
        }
    } catch (Exception $e) {
        // Fallback if table query fails
    }

    if (empty($paketInternet)) {
        $paketInternet = [
            "5"   => "5 mbps - 150rb/bln",
            "10"  => "10 mbps - 300rb/bln",
            "20"  => "20 mbps - 500rb/bln",
            "30"  => "30 mbps - 650rb/bln",
            "50"  => "50 mbps - 850rb/bln",
            "100" => "100 mbps - 1.25jt/bln"
        ];
    }

    // Top Type ONT terbanyak dari ikr_report
    $listTypeOnt = [];
    try {
        $stmtOnt = $pdo->query("
            SELECT UPPER(TRIM(type_ont)) AS type_ont, COUNT(*) AS total
            FROM ikr_report
            WHERE type_ont IS NOT NULL 
              AND TRIM(type_ont) <> '' 
              AND TRIM(type_ont) <> '-'
            GROUP BY UPPER(TRIM(type_ont))
            ORDER BY total DESC, type_ont ASC
            LIMIT 30
        ");
        if ($stmtOnt) {
            $listTypeOnt = $stmtOnt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Fallback if query fails
    }

    $sql = "SELECT
                UPPER(TRIM(kab)) AS kab
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(kab) <> ''
            GROUP BY UPPER(TRIM(kab))
            HAVING COUNT(*) > 100
            ORDER BY kab ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kab = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
                UPPER(TRIM(kec)) AS kec
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(kec) <> ''
            GROUP BY UPPER(TRIM(kec))
            HAVING COUNT(*) > 5
            ORDER BY kec ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kec = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
                UPPER(TRIM(desa)) AS desa
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(desa) <> ''
            GROUP BY UPPER(TRIM(desa))
            HAVING COUNT(*) > 40
            ORDER BY desa ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $desa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Mapping Desa -> Kec & Kab (Most Frequent Relation) ───────────
    $sqlMapping = "SELECT
                    UPPER(TRIM(desa)) AS desa,
                    UPPER(TRIM(kec)) AS kec,
                    UPPER(TRIM(kab)) AS kab,
                    COUNT(*) AS total
                FROM ikr_report
                WHERE desa IS NOT NULL AND TRIM(desa) <> ''
                  AND kec IS NOT NULL AND TRIM(kec) <> ''
                  AND kab IS NOT NULL AND TRIM(kab) <> ''
                GROUP BY UPPER(TRIM(desa)), UPPER(TRIM(kec)), UPPER(TRIM(kab))
                ORDER BY UPPER(TRIM(desa)) ASC, total DESC";
    $stmtMapping = $pdo->prepare($sqlMapping);
    $stmtMapping->execute();
    $rowsMapping = $stmtMapping->fetchAll(PDO::FETCH_ASSOC);

    $desaMapping = [];
    foreach ($rowsMapping as $m) {
        $d = $m['desa'];
        if (!isset($desaMapping[$d])) {
            $desaMapping[$d] = [
                'kec' => $m['kec'],
                'kab' => $m['kab']
            ];
        }
    }

    // ── PIC Teknisi: pola sama persis dengan service report ─────────
    $isTim       = !empty($customer['tech_id']) && strpos($customer['tech_id'], 'TIM') === 0;
    $teamMembers = [];

    if (!empty($customer['tech_id'])) {
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
    }
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal mendapatkan data, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/ikr/");
}

// ── Build variables ──────────────────────────────────────────────
$cust_address = trim(($customer['perumahan'] ?? '') . ' ' . ($customer['location'] ?? ''));

// Format phone_contact ke format WA internasional (08... → 628...)
$phone = !empty($customer['phone_contact'])
    ? $customer['phone_contact']
    : ($customer['phone'] ?? '');

$phone_raw = preg_replace('/[^0-9]/', '', $phone);
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

    /* Bootstrap-select (desa/kec/kab/paket) — diselaraskan ke sr-control
       TANPA mengubah fungsi search & "Lainnya"-nya */
    .bootstrap-select>.dropdown-toggle {
        border-radius: 10px !important;
        border: 1.5px solid #E2E8F0 !important;
        background: #F8FAFF !important;
        min-height: 48px;
        display: flex !important;
        align-items: center;
        font-size: 14px !important;
    }

    .bootstrap-select>.dropdown-toggle:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15) !important;
        outline: none !important;
    }

    .bootstrap-select.is-invalid>.dropdown-toggle {
        border-color: #EF4444 !important;
        background: #FFF5F5 !important;
    }
</style>

<div class="content">
    <div class="container px-3 pb-5">

        <div id="validationAlert" class="alert alert-danger d-none mt-3 mb-0" role="alert"
            style="border-radius:12px; font-size:13px; font-weight:600">
            ⚠️ <strong>Form belum lengkap!</strong> Cek field yang merah di bawah.
        </div>
        <form id="formReport" method="post"
            action="<?= BASE_URL ?>controllers/report/ikr/create.php"
            novalidate>
            <input type="hidden" name="ikr_id" value="<?= htmlspecialchars($ikr_id) ?>">
            <input type="hidden" name="netpay_id" value="<?= htmlspecialchars($customer['netpay_id'] ?? '') ?>">
            <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($customer['schedule_id'] ?? '') ?>">
            <div id="captureForm">
                <div class="card sr-card shadow-sm mt-3 mb-3">
                    <span class="sr-stripe" style="background:linear-gradient(90deg,#0EA5E9,#38BDF8)"></span>
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge px-3 py-2"
                                style="background:#E0F2FE;color:#075985;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.5px">
                                📋 IKR REPORT
                            </span>
                            <small class="text-muted font-weight-bold"><?= htmlspecialchars($ikr_id) ?></small>
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
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#EFF6FF;color:#2563EB">🏠</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Identitas Customer</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="sr-label">Netpay ID</label>
                            <input class="form-control sr-control" value="<?= htmlspecialchars($customer['netpay_id'] ?? '') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Nama (IKR An) <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="ikr_an" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" placeholder="Nama pelanggan" readonly>
                            <div class="invalid-feedback">⚠️ Nama wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Telepon <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="telp" pattern="^08[0-9]{8,11}$" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                            <div class="invalid-feedback">⚠️ Telepon wajib diisi (format 08xxxxxxxxxx)</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Alamat <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="alamat" value="<?= htmlspecialchars($cust_address ?: ($customer['location'] ?? '')) ?>" placeholder="Alamat lengkap">
                            <div class="invalid-feedback">⚠️ Alamat wajib diisi</div>
                        </div>
                        <div class="form-row mb-0">
                            <div class="col-6">
                                <label class="sr-label">RT <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" type="number" name="rt" placeholder="RT">
                                <div class="invalid-feedback">⚠️ RT wajib diisi</div>
                            </div>
                            <div class="col-6">
                                <label class="sr-label">RW <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" type="number" name="rw" placeholder="RW">
                                <div class="invalid-feedback">⚠️ RW wajib diisi</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#ECFDF5;color:#10B981">📍</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Lokasi Wilayah</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="sr-label">Desa <span class="text-danger">*</span></label>
                            <select class="form-control sr-control validate-field selectpicker" id="desa" name="desa" data-size="7" data-live-search="true">
                                <option value="">— Pilih desa —</option>
                                <option value="LAINNYA">Lainnya</option>
                                <?php foreach ($desa as $p): ?>
                                    <option value="<?= htmlspecialchars($p['desa']) ?>"><?= htmlspecialchars($p['desa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">⚠️ Desa wajib dipilih</div>
                            <div class="form-group mt-3" id="desa_lainnya_group" style="display:none;">
                                <label class="sr-label">Nama Desa Lainnya</label>
                                <input type="text" class="form-control sr-control validate-field" id="desa_lainnya" placeholder="Masukkan nama desa">
                                <div class="invalid-feedback">⚠️ Nama desa wajib diisi</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="sr-label">Kecamatan <span class="text-danger">*</span></label>
                            <select class="form-control sr-control validate-field selectpicker" id="kec" name="kec" data-size="7" data-live-search="true">
                                <option value="">— Pilih Kecamatan —</option>
                                <option value="LAINNYA">Lainnya</option>
                                <?php foreach ($kec as $p): ?>
                                    <option value="<?= htmlspecialchars($p['kec']) ?>"><?= htmlspecialchars($p['kec']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">⚠️ Kecamatan wajib dipilih</div>
                            <div class="form-group mt-3" id="kec_lainnya_group" style="display:none;">
                                <label class="sr-label">Nama Kecamatan Lainnya</label>
                                <input type="text" class="form-control sr-control validate-field" id="kec_lainnya" placeholder="Masukkan nama kecamatan">
                                <div class="invalid-feedback">⚠️ Nama kecamatan wajib diisi</div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="sr-label">Kabupaten <span class="text-danger">*</span></label>
                            <select class="form-control sr-control validate-field selectpicker" id="kab" name="kab" data-size="7" data-live-search="true">
                                <option value="">— Pilih Kabupaten —</option>
                                <option value="LAINNYA">Lainnya</option>
                                <?php foreach ($kab as $p): ?>
                                    <option value="<?= htmlspecialchars($p['kab']) ?>"><?= htmlspecialchars($p['kab']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">⚠️ Kabupaten wajib dipilih</div>
                            <div class="form-group mt-3" id="kab_lainnya_group" style="display:none;">
                                <label class="sr-label">Nama Kabupaten Lainnya</label>
                                <input type="text" class="form-control sr-control validate-field" id="kab_lainnya" placeholder="Masukkan nama Kabupaten">
                                <div class="invalid-feedback">⚠️ Nama kabupaten wajib diisi</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#FEF3C7;color:#D97706">📶</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Detail Instalasi</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="sr-label">Type ONT <span class="text-danger">*</span></label>
                            <select class="form-control sr-control validate-field selectpicker" id="type_ont" name="type_ont" data-size="7" data-live-search="true">
                                <option value="">— Pilih Type ONT —</option>
                                <option value="LAINNYA">Lainnya</option>
                                <?php foreach ($listTypeOnt as $ont): ?>
                                    <option value="<?= htmlspecialchars($ont['type_ont']) ?>"><?= htmlspecialchars($ont['type_ont']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">⚠️ Type ONT wajib dipilih</div>
                            <div class="form-group mt-3" id="type_ont_lainnya_group" style="display:none;">
                                <label class="sr-label">Nama Type ONT Lainnya</label>
                                <input type="text" class="form-control sr-control validate-field" id="type_ont_lainnya" placeholder="Masukkan type ONT">
                                <div class="invalid-feedback">⚠️ Type ONT wajib diisi</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Nomor Seri (S/N) <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="sn" placeholder="Serial number">
                            <div class="invalid-feedback">⚠️ Nomor seri wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Paket <span class="text-danger">*</span></label>
                            <select class="form-control sr-control validate-field selectpicker" id="paket_internet" name="paket" data-size="7">
                                <option value="">Select</option>
                                <?php foreach ($paketInternet as $key => $value): ?>
                                    <?php $selected = ($key == $customer['paket_internet']) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $selected ?>><?= htmlspecialchars($value) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">⚠️ Paket wajib dipilih</div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="sr-label">Redaman <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="redaman" placeholder="-xx dBm">
                            <div class="invalid-feedback">⚠️ Redaman wajib diisi</div>
                        </div>
                    </div>
                </div>

                <!-- INFRASTRUKTUR JARINGAN -->
                <div class="card sr-card shadow-sm mb-3">
                    <div class="card-header d-flex align-items-center">
                        <span class="sr-sec-icon" style="background:#E0F2FE;color:#0284C7">🔌</span>
                        <span class="font-weight-bold" style="font-size:14px;color:#0F172A">Infrastruktur Jaringan</span>
                    </div>
                    <div class="card-body">
                        <div class="form-row mb-3">
                            <div class="col-4">
                                <label class="sr-label">ODP No <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="odp_no" value="-" placeholder="ODP No">
                                <div class="invalid-feedback">⚠️ Wajib diisi</div>
                            </div>
                            <div class="col-4">
                                <label class="sr-label">ODC No <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="odc_no" value="-" placeholder="ODC No">
                                <div class="invalid-feedback">⚠️ Wajib diisi</div>
                            </div>
                            <div class="col-4">
                                <label class="sr-label">JC No <span class="text-danger">*</span></label>
                                <input class="form-control sr-control validate-field" name="jc_no" value="-" placeholder="JC No">
                                <div class="invalid-feedback">⚠️ Wajib diisi</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="sr-label">ODP <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="odp" value="-" placeholder="ODP">
                            <div class="invalid-feedback">⚠️ ODP wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">ODC <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="odc" value="-" placeholder="ODC">
                            <div class="invalid-feedback">⚠️ ODC wajib diisi</div>
                        </div>
                        <div class="form-group">
                            <label class="sr-label">Enclosure <span class="text-danger">*</span></label>
                            <input class="form-control sr-control validate-field" name="enclosure" value="-" placeholder="Enclosure">
                            <div class="invalid-feedback">⚠️ Enclosure wajib diisi</div>
                        </div>

                        <hr class="my-3" style="border-color:#F0F4FA">

                        <div class="form-row mb-0">
                            <div class="col-6">
                                <label class="sr-label">MAC Sebelum <span class="text-muted" style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0">(opsional)</span></label>
                                <input class="form-control sr-control" name="mac_sebelum" value="-" placeholder="MAC sebelum">
                            </div>
                            <div class="col-6">
                                <label class="sr-label">MAC Sesudah <span class="text-muted" style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0">(opsional)</span></label>
                                <input class="form-control sr-control" name="mac_sesudah" value="-" placeholder="MAC sesudah">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PIC -->
                <div class="card sr-card shadow-sm mb-4">
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
                        <?php elseif (!empty($teamMembers)): ?>
                            <input class="form-control sr-control" readonly name="pic[]"
                                value="<?= htmlspecialchars($customer['tech_id'] ?? '') ?>">
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:12px">Belum ada teknisi yang ditugaskan pada jadwal ini.</p>
                        <?php endif; ?>
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
<?php require __DIR__ . '/../../includes/footer.php'; ?>

<script>
    var ratingSent = <?= $enable_rating ? 'false' : 'true' ?>;

    var TECHNICIAN_MAP = <?= json_encode(
                                array_reduce($teamMembers, function ($carry, $item) {
                                    $carry[$item['tech_id']] = $item['name'];
                                    return $carry;
                                }, [])
                            ) ?>;

    var PAKET_MAP = <?= json_encode($paketInternet) ?>;
    var DESA_MAPPING = <?= json_encode($desaMapping) ?>;

    var CUSTOMER_DATA = <?= json_encode([
                            'name'      => $customer['name']      ?? '',
                            'phone'     => $customer['phone']     ?? '',
                            'netpay_id' => $customer['netpay_id'] ?? '',
                            'address'   => $cust_address,
                        ]) ?>;

    // ── PENTING: phone_wa sudah diformat di PHP (08... → 628...) ────
    var RATING_DATA = {
        schedule_id: <?= json_encode($customer['schedule_id'] ?? '') ?>,
        tech_id: '<?= addslashes($customer['tech_id'] ?? '') ?>',
        netpay_id: <?= ($customer['netpay_id'] ?? 0) ?>,
        phone_contact: '<?= $phone_wa ?>'
    };

    var REQUIRED_FIELDS = [
        'ikr_an', 'telp', 'alamat', 'rt', 'rw',
        'desa', 'kec', 'kab',
        'type_ont', 'sn', 'paket', 'redaman',
        'odp_no', 'odc_no', 'jc_no', 'odp', 'odc', 'enclosure'
    ];

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.validate-field').forEach(function(el) {
            var clear = function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    if (!document.querySelectorAll('.sr-control.is-invalid').length) {
                        document.getElementById('validationAlert').classList.add('d-none');
                    }
                }
            };
            el.addEventListener('input', clear);
            el.addEventListener('change', clear);
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
                'Teknisi kami telah selesai melakukan instalasi di lokasi Anda.\n\n' +
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
            link.download = 'ikr-report-' + Date.now() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        });
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

        // Kompatibilitas: controller lama kemungkinan cek isset($_POST['submit'])
        // karena tombol submit asli sekarang type="button", bukan type="submit".
        formData.append('submit', '1');

        var picIds = formData.getAll('pic[]');
        var teknisiText = picIds.map(function(id) {
            return TECHNICIAN_MAP[id] || id;
        }).join(', ') || '-';

        var paketLabel = PAKET_MAP[formData.get('paket')] || formData.get('paket');

        try {
            var res = await fetch(form.getAttribute('action'), {
                method: 'POST',
                body: formData
            });
            var result = await res.json();

            if (result.status) {
                var text =
                    '📄 *IKR REPORT*\nID: ' + formData.get('ikr_id') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n👤 *Customer*\n' +
                    'Nama     : ' + formData.get('ikr_an') + '\n' +
                    'Telp     : ' + formData.get('telp') + '\n' +
                    'Alamat   : ' + formData.get('alamat') + ', RT ' + formData.get('rt') + '/RW ' + formData.get('rw') + '\n' +
                    'Desa     : ' + formData.get('desa') + '\n' +
                    'Kec      : ' + formData.get('kec') + '\n' +
                    'Kab      : ' + formData.get('kab') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n📶 *Instalasi*\n' +
                    'Type ONT  : ' + formData.get('type_ont') + '\n' +
                    'S/N       : ' + formData.get('sn') + '\n' +
                    'Paket     : ' + paketLabel + '\n' +
                    'Redaman   : ' + formData.get('redaman') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🔌 *Infrastruktur*\n' +
                    'ODP No    : ' + formData.get('odp_no') + '\n' +
                    'ODC No    : ' + formData.get('odc_no') + '\n' +
                    'JC No     : ' + formData.get('jc_no') + '\n' +
                    'ODP       : ' + formData.get('odp') + '\n' +
                    'ODC       : ' + formData.get('odc') + '\n' +
                    'Enclosure : ' + formData.get('enclosure') + '\n' +
                    'MAC Seb.  : ' + (formData.get('mac_sebelum') || '-') + '\n' +
                    'MAC Ses.  : ' + (formData.get('mac_sesudah') || '-') + '\n\n' +
                    '━━━━━━━━━━━━━━━━━━\n🛠️ *Teknisi*\n' + teknisiText + '\n━━━━━━━━━━━━━━━━━━';

                if (navigator.share) {
                    try {
                        await navigator.share({
                            title: 'IKR Report',
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
                    window.location.href = '<?= BASE_URL ?>pages/ikr/';
                });

            } else {
                if (result.message && result.message.includes('sudah pernah dibuat')) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Laporan Sudah Ada',
                        text: result.message,
                        confirmButtonText: 'Ke List Schedule'
                    }).then(() => {
                        window.location.href = '<?= BASE_URL ?>pages/schedule/';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Submit',
                        text: result.message || 'Gagal submit laporan.'
                    });
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: e.message || 'Terjadi error, silakan coba lagi.'
            });
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }
</script>

<script>
    $(function() {

        $('#desa').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#desa_lainnya_group').slideDown(function() {
                    $('#desa_lainnya').focus().select();
                });

                $('#desa_lainnya')
                    .attr('name', 'desa')
                    .prop('required', true);

                $('#desa').removeAttr('name');

            } else {

                $('#desa_lainnya_group').slideUp();

                $('#desa_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('')
                    .removeClass('is-invalid');

                $('#desa').attr('name', 'desa');

                // Auto-fill Kecamatan & Kabupaten based on selected Desa
                var selectedDesa = $(this).val();
                if (selectedDesa && selectedDesa !== 'LAINNYA' && typeof DESA_MAPPING !== 'undefined' && DESA_MAPPING[selectedDesa]) {
                    var targetKec = DESA_MAPPING[selectedDesa].kec;
                    var targetKab = DESA_MAPPING[selectedDesa].kab;

                    if (targetKec) {
                        $('#kec').val(targetKec).trigger('change');
                        if ($.fn.selectpicker) {
                            $('#kec').selectpicker('val', targetKec);
                        }
                    }
                    if (targetKab) {
                        $('#kab').val(targetKab).trigger('change');
                        if ($.fn.selectpicker) {
                            $('#kab').selectpicker('val', targetKab);
                        }
                    }
                }
            }

        });
        $('#kec').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#kec_lainnya_group').slideDown();
                $('#kec_lainnya_group').slideDown(function() {
                    $('#kec_lainnya').focus().select();
                });
                $('#kec_lainnya')
                    .attr('name', 'kec')
                    .prop('required', true);

                $('#kec').removeAttr('name');

            } else {

                $('#kec_lainnya_group').slideUp();

                $('#kec_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('')
                    .removeClass('is-invalid');

                $('#kec').attr('name', 'kec');
            }

        });
        $('#kab').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#kab_lainnya_group').slideDown(function() {
                    $('#kab_lainnya').focus().select();
                });
                $('#kab_lainnya')
                    .attr('name', 'kab')
                    .prop('required', true);

                $('#kab').removeAttr('name');

            } else {

                $('#kab_lainnya_group').slideUp();

                $('#kab_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('')
                    .removeClass('is-invalid');

                $('#kab').attr('name', 'kab');
            }

        });
        $('#type_ont').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#type_ont_lainnya_group').slideDown(function() {
                    $('#type_ont_lainnya').focus().select();
                });
                $('#type_ont_lainnya')
                    .attr('name', 'type_ont')
                    .prop('required', true);

                $('#type_ont').removeAttr('name');

            } else {

                $('#type_ont_lainnya_group').slideUp();

                $('#type_ont_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('')
                    .removeClass('is-invalid');

                $('#type_ont').attr('name', 'type_ont');
            }
        });

        // Auto-check PIC checkboxes from daily team partners in DB / localStorage
        (function() {
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
        })();

    });
</script>