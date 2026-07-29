<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../helper/checkRowExist.php';
$_SESSION['menu'] = 'request ikr';

$jamKerja = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00"];

try {
    $rikr_key = isset($_GET['id']) ? $_GET['id'] : null;

    $perumahan = [
        "Puri Lestari"      => "Puri Lestari - 01",
        "Gramapuri Persada" => "Gramapuri Persada - 02",
        "Telaga Harapan"    => "Telaga Harapan - 03",
        "Telaga Murni"      => "Telaga Murni - 04",
    ];
    $paketInternet = [
        "5"   => "5 Mbps — Rp 150.000/bln",
        "10"  => "10 Mbps — Rp 300.000/bln",
        "30"  => "30 Mbps — Rp 650.000/bln",
        "50"  => "50 Mbps — Rp 850.000/bln",
        "100" => "100 Mbps — Rp 1.000.000/bln",
    ];
    $netpay_kode = [
        "20" => "Cikarang - 20",
        "21" => "Cikarang - 21",
        "22" => "Cikarang - 22",
        "52" => "Tasik Kab - 52",
        "55" => "Tasik Kot - 55",
        "27" => "Cipatat - 27",
        "24" => "Indramayu - 24",
        "28" => "Cibinong - 28",
    ];

    if ($rikr_key) {
        $sql = "SELECT
                    r.rikr_id,
                    r.registrasi_id,
                    r.catatan,
                    c.netpay_id,
                    c.name,
                    c.location,
                    c.phone,
                    c.paket_internet,
                    c.perumahan,
                    rg.registrasi_id,
                    rg.`date` AS date_request,
                    rg.`time` AS time_request
                FROM request_ikr r
                JOIN queue_scheduling q  ON r.queue_id  = q.queue_id
                JOIN customers c  ON q.netpay_id  = c.netpay_id
                JOIN register  rg ON r.registrasi_id = rg.registrasi_id
                WHERE r.rikr_id = :rikr_key";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':rikr_key' => $rikr_key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        checkRowExist($row, "pages/request/ikr");

        $daerah_id = substr($row['netpay_id'], 0, 2);
        $netpay_id = substr($row['netpay_id'], 2);
    } else {
        redirect("pages/request/ikr/");
    }
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon'   => 'error',
        'title'  => 'Oops! Ada yang Salah',
        'text'   => 'Gagal mendapatkan data, silakan coba lagi. ' . $e->getMessage(),
        'button' => 'Coba Lagi',
        'style'  => 'danger',
    ];
    redirect("pages/request/ikr/");
}

require __DIR__ . '/../../../includes/header.php';
require __DIR__ . '/../../../includes/aside.php';
require __DIR__ . '/../../../includes/navbar.php';
?>

<style>
    /* ── Token system ── */
    :root {
        --ikr-primary: #1B4FD8;
        --ikr-primary-light: #EEF2FF;
        --ikr-amber: #F59E0B;
        --ikr-amber-light: #FFFBEB;
        --ikr-surface: #F8FAFC;
        --ikr-border: #E2E8F0;
        --ikr-text: #1E293B;
        --ikr-muted: #64748B;
        --ikr-success: #10B981;
        --ikr-radius: 10px;
    }

    /* ── Page wrapper ── */
    .ikr-page {
        background: var(--ikr-surface);
        padding: 2rem 0 3rem;
        min-height: 100vh;
    }

    /* ── Step bar ── */
    .ikr-steps {
        display: flex;
        align-items: center;
        gap: 0;
        margin-bottom: 2rem;
    }

    .ikr-step {
        display: flex;
        align-items: center;
        gap: .6rem;
        font-size: .8rem;
        font-weight: 600;
        color: var(--ikr-muted);
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .ikr-step__num {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 700;
        background: var(--ikr-border);
        color: var(--ikr-muted);
        flex-shrink: 0;
        transition: background .2s, color .2s;
    }

    .ikr-step.active .ikr-step__num {
        background: var(--ikr-primary);
        color: #fff;
    }

    .ikr-step.active {
        color: var(--ikr-primary);
    }

    .ikr-step.done .ikr-step__num {
        background: var(--ikr-success);
        color: #fff;
    }

    .ikr-step.done {
        color: var(--ikr-success);
    }

    .ikr-step__line {
        flex: 1;
        height: 2px;
        background: var(--ikr-border);
        margin: 0 .75rem;
    }

    /* ── Cards ── */
    .ikr-card {
        background: #fff;
        border: 1px solid var(--ikr-border);
        border-radius: var(--ikr-radius);
        box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        height: 100%;
    }

    .ikr-card__head {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid var(--ikr-border);
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .ikr-card__icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .ikr-card__icon--blue {
        background: var(--ikr-primary-light);
        color: var(--ikr-primary);
    }

    .ikr-card__icon--amber {
        background: var(--ikr-amber-light);
        color: var(--ikr-amber);
    }

    .ikr-card__title {
        font-size: .9rem;
        font-weight: 700;
        color: var(--ikr-text);
        margin: 0;
        line-height: 1.3;
    }

    .ikr-card__sub {
        font-size: .75rem;
        color: var(--ikr-muted);
        margin: 0;
    }

    .ikr-card__body {
        padding: 1.5rem;
    }

    .ikr-card__foot {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--ikr-border);
        background: var(--ikr-surface);
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        border-bottom-left-radius: var(--ikr-radius);
        border-bottom-right-radius: var(--ikr-radius);
    }

    /* ── Form fields ── */
    .ikr-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--ikr-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .35rem;
        display: block;
    }

    .ikr-label span {
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        font-size: .75rem;
    }

    .ikr-field {
        border: 1.5px solid var(--ikr-border);
        border-radius: 7px;
        padding: .55rem .85rem;
        font-size: .88rem;
        color: var(--ikr-text);
        transition: border-color .15s, box-shadow .15s;
        width: 100%;
        background: #fff;
    }

    .ikr-field:focus {
        outline: none;
        border-color: var(--ikr-primary);
        box-shadow: 0 0 0 3px rgba(27, 79, 216, .1);
    }

    .ikr-field[disabled],
    .ikr-field[readonly] {
        background: var(--ikr-surface);
        color: var(--ikr-muted);
        cursor: not-allowed;
    }

    .ikr-field.is-readonly {
        background: var(--ikr-surface);
        color: var(--ikr-muted);
    }

    textarea.ikr-field {
        resize: vertical;
        min-height: 80px;
    }

    .form-group {
        margin-bottom: 1.15rem;
    }

    /* ── Badge status ── */
    .ikr-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .75rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 600;
    }

    .ikr-badge--unverified {
        background: #FEF3C7;
        color: #92400E;
    }

    .ikr-badge__dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    /* ── Netpay group ── */
    .netpay-group {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: .5rem;
        align-items: center;
    }

    /* ── ID badge display ── */
    .ikr-id-display {
        font-family: 'Courier New', monospace;
        font-size: .88rem;
        font-weight: 700;
        color: var(--ikr-primary);
        background: var(--ikr-primary-light);
        border: 1.5px dashed #93C5FD;
        border-radius: 7px;
        padding: .55rem .85rem;
        display: block;
        letter-spacing: .05em;
    }

    /* ── Buttons ── */
    .btn-ikr-primary {
        background: var(--ikr-primary);
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: .5rem 1.4rem;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, transform .1s;
    }

    .btn-ikr-primary:hover {
        background: #163EC0;
        color: #fff;
    }

    .btn-ikr-cancel {
        background: transparent;
        color: var(--ikr-muted);
        border: 1.5px solid var(--ikr-border);
        border-radius: 7px;
        padding: .5rem 1.2rem;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        transition: border-color .15s, color .15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    .btn-ikr-cancel:hover {
        border-color: #CBD5E1;
        color: var(--ikr-text);
        text-decoration: none;
    }

    .btn-edit-netpay {
        width: 38px;
        height: 38px;
        border-radius: 7px;
        border: 1.5px solid var(--ikr-border);
        background: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        color: var(--ikr-muted);
        transition: background .15s, color .15s, border-color .15s;
        flex-shrink: 0;
    }

    .btn-edit-netpay.editing {
        background: #ECFDF5;
        color: var(--ikr-success);
        border-color: var(--ikr-success);
    }

    /* ── Page header ── */
    .ikr-header {
        margin-bottom: 1.75rem;
    }

    .ikr-header__title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--ikr-text);
        margin: 0 0 .2rem;
    }

    .ikr-header__breadcrumb {
        font-size: .8rem;
        color: var(--ikr-muted);
        margin: 0;
    }

    .ikr-header__breadcrumb a {
        color: var(--ikr-primary);
        text-decoration: none;
    }

    .ikr-header__breadcrumb a:hover {
        text-decoration: underline;
    }

    /* ── Responsive ── */
    @media (max-width: 767px) {
        .ikr-page {
            padding: 1.25rem 0 2rem;
        }

        .ikr-step__label {
            display: none;
        }

        .ikr-card__body {
            padding: 1.1rem;
        }

        .netpay-group {
            grid-template-columns: 1fr auto;
        }

        .netpay-group .netpay-text {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="ikr-page">
    <div class="container">

        <!-- Page Header -->
        <div class="ikr-header d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="ikr-header__title">Update Request IKR</h1>
                <p class="ikr-header__breadcrumb">
                    <a href="<?= BASE_URL ?>dashboard">Dashboard</a>
                    &rsaquo; <a href="<?= BASE_URL ?>pages/request/ikr/">Request IKR</a>
                    &rsaquo; Edit
                </p>
            </div>
        </div>

        <!-- Step indicator — sama persis dengan create -->
        <div class="ikr-steps">
            <div class="ikr-step done">
                <span class="ikr-step__num"><i class="fas fa-check" style="font-size:.65rem"></i></span>
                <span class="ikr-step__label">Data Register</span>
            </div>
            <div class="ikr-step__line"></div>
            <div class="ikr-step active">
                <span class="ikr-step__num">2</span>
                <span class="ikr-step__label">Update IKR</span>
            </div>
            <div class="ikr-step__line"></div>
            <div class="ikr-step">
                <span class="ikr-step__num">3</span>
                <span class="ikr-step__label">Selesai</span>
            </div>
        </div>

        <!-- Main Form -->
        <form method="post" action="<?= BASE_URL ?>controllers/request/ikr/update.php">

            <!-- Hidden system fields -->
            <input type="hidden" name="rikr_id" value="<?= $row['rikr_id'] ?>">
            <input type="hidden" name="old_netpay_id" value="<?= $row['netpay_id'] ?>">
            <input type="hidden" name="is_verified" value="Unverified">

            <div class="row">

                <!-- ═══ Card 1 · Data Register ═══ -->
                <div class="col-lg-6 mb-4">
                    <div class="ikr-card">
                        <div class="ikr-card__head">
                            <div class="ikr-card__icon ikr-card__icon--blue">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <p class="ikr-card__title">Data Register</p>
                                <p class="ikr-card__sub">Calon pelanggan menunggu verifikasi</p>
                            </div>
                        </div>

                        <div class="ikr-card__body">

                            <!-- Registrasi ID -->
                            <div class="form-group">
                                <label class="ikr-label">
                                    Registrasi ID
                                    <span>— belum diverifikasi</span>
                                </label>
                                <input type="text" class="ikr-field" disabled value="<?= htmlspecialchars($row['registrasi_id']) ?>">
                                <input type="hidden" name="registrasi_id" value="<?= htmlspecialchars($row['registrasi_id']) ?>">
                            </div>

                            <!-- Nama & No HP -->
                            <div class="row">
                                <div class="col-sm-7">
                                    <div class="form-group">
                                        <label class="ikr-label" for="name">Nama Pelanggan</label>
                                        <input id="name" type="text" class="ikr-field" name="name"
                                            value="<?= htmlspecialchars($row['name']) ?>"
                                            placeholder="Nama lengkap" required>
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <div class="form-group">
                                        <label class="ikr-label" for="phone">No HP</label>
                                        <input id="phone" type="tel" class="ikr-field" name="phone"
                                            value="<?= htmlspecialchars($row['phone']) ?>"
                                            placeholder="08xx-xxxx-xxxx"
                                            pattern="^(?:\+62|62|0)8[0-9]{8,11}$" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Paket Internet -->
                            <div class="form-group">
                                <label class="ikr-label" for="paket_internet">Paket Internet</label>
                                <select class="ikr-field selectpicker w-100" id="paket_internet" name="paket_internet" required data-size="7">
                                    <option value="">Pilih paket...</option>
                                    <?php foreach ($paketInternet as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= ($key == $row['paket_internet']) ? 'selected' : '' ?>><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="ikr-label">Status Verifikasi</label>
                                <div class="pt-1">
                                    <span class="ikr-badge ikr-badge--unverified">
                                        <span class="ikr-badge__dot"></span>
                                        Unverified
                                    </span>
                                </div>
                            </div>

                            <!-- Divider -->
                            <hr style="border-color: var(--ikr-border); margin: 1rem 0;">
                            <p class="ikr-label mb-2" style="color: var(--ikr-muted);">Permintaan Customer <span>(dari form pendaftaran)</span></p>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="ikr-label">Tanggal Diinginkan</label>
                                        <input type="date" class="ikr-field" disabled value="<?= htmlspecialchars($row['date_request']) ?>">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="ikr-label">Jam Diinginkan</label>
                                        <input type="text" class="ikr-field" readonly value="<?= htmlspecialchars($row['time_request']) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Perumahan — select karena di update bisa diubah -->
                            <div class="form-group">
                                <label class="ikr-label" for="perumahan">Perumahan</label>
                                <input type="text" id="perumahan" class="ikr-field" name="perumahan" value="<?= $row['perumahan'] ?>" readonly>
                            </div>
                            <div class="form-group mb-0">
                                <label class="ikr-label" for="location">Alamat Lengkap</label>
                                <textarea class="ikr-field" id="location" name="location" rows="3" required
                                    placeholder="Jl. ..."><?= htmlspecialchars($row['location']) ?></textarea>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ═══ Card 2 · Update IKR ═══ -->
                <div class="col-lg-6 mb-4">
                    <div class="ikr-card d-flex flex-column">
                        <div class="ikr-card__head">
                            <div class="ikr-card__icon ikr-card__icon--amber">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <p class="ikr-card__title">Update Permintaan IKR</p>
                                <p class="ikr-card__sub">Ubah detail jadwal pemasangan yang dikonfirmasi</p>
                            </div>
                        </div>

                        <div class="ikr-card__body flex-grow-1">

                            <!-- Request IKR ID -->
                            <div class="form-group">
                                <label class="ikr-label">Request IKR ID</label>
                                <span class="ikr-id-display"><?= htmlspecialchars($row['rikr_id']) ?></span>
                                <input type="hidden" name="rikr_id" value="<?= htmlspecialchars($row['rikr_id']) ?>">
                            </div>

                            <!-- Netpay ID -->
                            <div class="form-group">
                                <label class="ikr-label">Netpay ID</label>
                                <div class="netpay-group">
                                    <select id="netpay_kode" name="netpay_kode" class="ikr-field selectpicker" required data-size="7">
                                        <option value="">Pilih kode area...</option>
                                        <?php foreach ($netpay_kode as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= ($key == $daerah_id) ? 'selected' : '' ?>>
                                                <?= $value ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input type="text" id="netpay_id_text" class="ikr-field netpay-text"
                                        value="<?= htmlspecialchars($netpay_id) ?>"
                                        placeholder="ID akan terisi otomatis" readonly>
                                    <input type="hidden" id="netpay_id" name="netpay_id" value="<?= htmlspecialchars($row['netpay_id']) ?>">

                                    <button type="button" id="btnEditNetpay" class="btn-edit-netpay" title="Edit manual">
                                        <i class="fas fa-pencil-alt" style="font-size:.75rem"></i>
                                    </button>
                                </div>
                                <small class="text-muted" style="font-size:.75rem">Klik ✏ untuk mengisi Netpay ID secara manual</small>
                            </div>

                            <!-- Registrasi ID mirror -->
                            <div class="form-group">
                                <label class="ikr-label">Registrasi ID</label>
                                <input type="text" class="ikr-field" id="registrasi_id2"
                                    value="<?= htmlspecialchars($row['registrasi_id']) ?>" disabled>
                            </div>

                            <hr style="border-color: var(--ikr-border); margin: 1rem 0;">
                            <p class="ikr-label mb-2" style="color: var(--ikr-muted);">Jadwal yang Dikonfirmasi</p>

                            <!-- Tanggal & Jam Pemasangan -->
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="ikr-label" for="date_pemasangan">Tanggal Pemasangan</label>
                                        <input type="date" id="date_pemasangan" class="ikr-field" name="date_pemasangan" min="<?= date('Y-m-d') ?>" value="<?= $row['date_request'] ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="ikr-label" for="time-schedule">Jam Pemasangan</label>
                                        <input type="text" id="time-schedule" class="ikr-field" name="time_pemasangan" value="<?= $row['time_request'] ?>" placeholder="HH:MM" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan -->
                            <div class="form-group mb-0">
                                <label class="ikr-label" for="catatan">Catatan Teknisi</label>
                                <textarea class="ikr-field" id="catatan" name="catatan" rows="4" required
                                    placeholder="Informasi tambahan untuk teknisi..."><?= htmlspecialchars($row['catatan']) ?></textarea>
                            </div>

                        </div>

                        <div class="ikr-card__foot">
                            <a href="<?= BASE_URL ?>pages/request/ikr/" class="btn-ikr-cancel">
                                <i class="fas fa-times" style="font-size:.75rem"></i> Batal
                            </a>
                            <button type="submit" name="submit" class="btn-ikr-primary">
                                <i class="fas fa-save" style="font-size:.8rem; margin-right:.35rem"></i>
                                Simpan Perubahan
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../../includes/footer.php'; ?>

<script>
    /* ── Netpay selector ── */
    $('#netpay_kode').on('change', function() {
        const kode = $(this).val();
        if (!kode) {
            $('#netpay_id, #netpay_id_text').val('');
            return;
        }
        $.getJSON("<?= BASE_URL ?>api/get_netpay_id.php", {
            kode
        }, function(res) {
            if (res.status) {
                $('#netpay_id, #netpay_id_text').val(res.netpay_id);
            }
        });
    });

    /* ── Manual edit toggle ── */
    let editMode = false;
    $('#btnEditNetpay').on('click', function() {
        editMode = !editMode;
        const $txt = $('#netpay_id_text');
        const $kode = $('#netpay_kode');
        const $btn = $(this);

        if (editMode) {
            $txt.prop('readonly', false).focus();
            $kode.prop('disabled', true);
            $btn.addClass('editing').html('<i class="fas fa-check" style="font-size:.75rem"></i>');
        } else {
            $txt.prop('readonly', true);
            $kode.prop('disabled', false);
            $('#netpay_id').val($txt.val());
            $btn.removeClass('editing').html('<i class="fas fa-pencil-alt" style="font-size:.75rem"></i>');
        }
        $('.selectpicker').selectpicker('refresh');
    });

    $('#netpay_id_text').on('input', function() {
        $('#netpay_id').val($(this).val());
    });

    /* ── Sync registrasi_id ke card 2 ── */
    $('#registrasi_id').on('changed.bs.select', function() {
        $('#registrasi_id2').val($(this).val());
    });

    /* ── Time input formatting & validation ── */
    const timeInput = document.getElementById('time-schedule');

    (function setCurrentTime() {
        if (timeInput.value.trim()) return;
        const now = new Date();
        timeInput.value = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    })();

    timeInput.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length >= 3) v = v.slice(0, 2) + ':' + v.slice(2, 4);
        e.target.value = v;
    });

    form.addEventListener('submit', function(e) {

        const regex = /^([01]\d|2[0-3]):([0-5]\d)$/;

        if (!regex.test(timeInput.value)) {

            e.preventDefault();

            Swal.fire({
                icon: 'error',
                title: 'Format Jam Salah',
                text: 'Jam harus menggunakan format 00:00 - 23:59'
            });

            return;
        }

    });
</script>