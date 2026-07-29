<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'registrasi';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$paketInternet = [
    "5"   => "5 Mbps — Rp 150.000/bln",
    "10"  => "10 Mbps — Rp 300.000/bln",
    "30"  => "30 Mbps — Rp 650.000/bln",
    "50"  => "50 Mbps — Rp 850.000/bln",
    "100" => "100 Mbps — Rp 1.000.000/bln",
];
$jamKerja = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00"];

$sql = "SELECT DISTINCT
    UPPER(TRIM(perumahan)) AS perumahan
FROM customers
WHERE perumahan IS NOT NULL
  AND TRIM(perumahan) <> ''
ORDER BY perumahan ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$perumahan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .field-icon {
        position: relative;
    }

    .field-icon .form-control {
        padding-left: 38px;
    }

    .field-icon .fi {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: #B5B5C3;
        width: 16px;
        height: 16px;
        pointer-events: none;
    }

    .jam-btn {
        border: 1.5px solid #E4E6EF;
        border-radius: 8px;
        padding: 8px 0;
        text-align: center;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #5E6278;
        background: #fff;
        transition: all .15s;
    }

    .jam-btn:hover {
        border-color: #009EF7;
        color: #009EF7;
        background: #F1FAFF;
    }

    .jam-btn.active {
        background: #009EF7;
        border-color: #009EF7;
        color: #fff;
    }

    .jam-btn input {
        position: absolute;
        opacity: 0;
        width: 0;
    }

    .section-label {
        font-size: 11px;
        font-weight: 700;
        color: #A1A5B7;
        letter-spacing: .8px;
        text-transform: uppercase;
        margin-bottom: 16px;
    }
</style>

<div class="content d-flex flex-column flex-column-fluid mt-7" id="kt_content">


    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7">

                    <div class="card card-custom shadow-sm">

                        <div class="card-header">
                            <div class="card-title">
                                <svg class="mr-3" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#009EF7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="8.5" cy="7" r="4" />
                                    <line x1="20" y1="8" x2="20" y2="14" />
                                    <line x1="23" y1="11" x2="17" y2="11" />
                                </svg>
                                <span class="card-label font-weight-bolder text-dark">Tambah Pelanggan Baru</span>
                            </div>
                            <div class="card-toolbar">
                                <a href="<?= BASE_URL ?>pages/registrasi/" class="btn btn-sm btn-danger font-weight-bold">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1" style="vertical-align:-2px">
                                        <line x1="19" y1="12" x2="5" y2="12" />
                                        <polyline points="12 19 5 12 12 5" />
                                    </svg>
                                    Kembali
                                </a>
                            </div>
                        </div>

                        <form method="post" action="<?= BASE_URL ?>controllers/registrasi/create.php">
                            <div class="card-body">

                                <!-- Data Pelanggan -->
                                <div class="section-label">Data Pelanggan</div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Nama Lengkap</label>
                                    <div class="field-icon">
                                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg>
                                        <input type="text" class="form-control" name="name" placeholder="Masukkan nama lengkap">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">No. Telepon</label>
                                    <div class="field-icon">
                                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 12 19.79 19.79 0 0 1 1.04 3.38 2 2 0 0 1 3 1h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
                                        </svg>
                                        <input type="tel" class="form-control " name="phone" placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Perumahan</label>
                                    <select class="form-control selectpicker" id="perumahan" name="perumahan" data-size="7" data-live-search="true">
                                        <option value="">— Pilih Perumahan —</option>
                                        <option value="LAINNYA">Lainnya</option>
                                        <?php foreach ($perumahan as $p): ?>
                                            <option value="<?= $p['perumahan'] ?>"><?= $p['perumahan'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-group mt-3" id="perumahan_lainnya_group" style="display:none;">
                                        <label class="font-weight-bold text-dark-50 font-size-sm">
                                            Nama Perumahan
                                        </label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="perumahan_lainnya"
                                            placeholder="Masukkan nama perumahan">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Alamat Pemasangan</label>
                                    <textarea class="form-control " name="location" rows="3" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan..."></textarea>
                                </div>

                                <div class="separator separator-dashed my-6"></div>

                                <!-- Paket -->
                                <div class="section-label">Paket Internet</div>

                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Pilih Paket</label>
                                    <select class="form-control  selectpicker" name="paket_internet">
                                        <option value="">— Pilih paket —</option>
                                        <?php foreach ($paketInternet as $key => $value): ?>
                                            <option value="<?= $key ?>"><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="separator separator-dashed my-6"></div>

                                <!-- Jadwal -->
                                <div class="section-label">Jadwal Pemasangan</div>

                                <div class="form-group">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Tanggal</label>
                                    <div class="field-icon">
                                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg>
                                        <input type="date" class="form-control" name="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                    </div>
                                </div>

                                <!-- <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Jam Kunjungan</label>
                                    <div class="row no-gutters" style="gap:8px;flex-wrap:wrap;display:flex">
                                        <?php foreach ($jamKerja as $j): ?>
                                            <label class="jam-btn col" style="position:relative;min-width:70px;flex:1">
                                                <input type="radio" name="time" value="<?= $j ?>" >
                                                <?= $j ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div> -->
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">Jam Kunjungan</label>
                                    <input type="text" class="form-control " name="time" id="time-schedule">

                                </div>


                            </div>

                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <a href="<?= BASE_URL ?>pages/registrasi/" class="btn btn-light-danger font-weight-bold">Batal</a>
                                <button type="submit" name="submit" class="btn btn-primary font-weight-bold px-8">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-2" style="vertical-align:-2px">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

<script>
    document.querySelectorAll('.jam-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.jam-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        });
    });
    $(function() {

        $('#perumahan').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#perumahan_lainnya_group').slideDown();

                $('#perumahan_lainnya')
                    .attr('name', 'perumahan')
                    .prop('required', true);

                $('#perumahan').removeAttr('name');

            } else {

                $('#perumahan_lainnya_group').slideUp();

                $('#perumahan_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');

                $('#perumahan').attr('name', 'perumahan');
            }

        });

    });
</script>


<script>
    const timeInput = document.getElementById('time-schedule');

    /* ===== Default Jam Sekarang (Realtime) ===== */
    function setCurrentTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInput.value = hours + ':' + minutes;
    }
    setCurrentTime();

    /* ===== Hanya boleh angka dan : ===== */
    timeInput.addEventListener('input', function(e) {
        let value = e.target.value;

        // Hapus karakter selain angka
        value = value.replace(/[^\d]/g, '');

        // Auto format HH:mm
        if (value.length >= 3) {
            value = value.substring(0, 2) + ':' + value.substring(2, 4);
        }

        e.target.value = value;
    });

    /* ===== Validasi langsung tolak ===== */
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