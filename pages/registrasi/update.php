<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'registrasi';
require_once __DIR__ . '/../../helper/redirect.php';
require_once __DIR__ . '/../../helper/checkRowExist.php';

try {
    $id = $_GET['id'];
    if (!$id) {
        redirect("pages/registrasi/");
    }
    $paketInternet = [
        "5"   => "5 Mbps — Rp 150.000/bln",
        "10"  => "10 Mbps — Rp 300.000/bln",
        "30"  => "30 Mbps — Rp 650.000/bln",
        "50"  => "50 Mbps — Rp 850.000/bln",
        "100" => "100 Mbps — Rp 1.000.000/bln",
    ];
    $jamKerja = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00"];

    $sql = "SELECT * FROM register WHERE registrasi_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        redirect("pages/registrasi/");
    }

    $sqlPerumahan = "SELECT DISTINCT
        UPPER(TRIM(perumahan)) AS perumahan
    FROM customers
    WHERE perumahan IS NOT NULL
      AND TRIM(perumahan) <> ''
    ORDER BY perumahan ASC";
    $stmtP = $pdo->prepare($sqlPerumahan);
    $stmtP->execute();
    $perumahan = $stmtP->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal mendapatkan data, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/registrasi/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<style>
    /* ── Form Redesign ────────────────────────────────────── */
    .create-page-header {
        margin-top: 2.25rem;
        margin-bottom: 1.5rem;
    }
    .create-page-header h1 {
        font-size: 1.65rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.15rem;
    }
    .create-page-header p {
        font-size: 0.92rem;
        color: #64748b;
        margin-bottom: 0;
    }

    .form-card {
        background: #fff;
        border: 1px solid #e8edf2;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .form-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .form-section-title i {
        font-size: 1.15rem;
        color: #3b82f6;
    }

    .field-icon {
        position: relative;
    }

    .field-icon .form-control {
        padding-left: 42px;
        height: 44px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 0.92rem;
        color: #334155;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .field-icon .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .field-icon .fi {
        position: absolute;
        top: 50%;
        left: 14px;
        transform: translateY(-50%);
        color: #94a3b8;
        width: 18px;
        height: 18px;
        pointer-events: none;
    }

    .form-group label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    textarea.form-control {
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-size: 0.92rem;
        padding: 10px 12px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    textarea.form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Bootstrap selectpicker custom styles */
    .bootstrap-select .btn {
        height: 44px !important;
        border-radius: 10px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #fff !important;
        font-size: 0.92rem !important;
        color: #334155 !important;
        padding-left: 14px !important;
    }

    .bootstrap-select .btn:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    .card-footer-actions {
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-save {
        padding: 0.65rem 2rem;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .btn-cancel {
        padding: 0.65rem 1.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 10px;
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">
            
            <!--begin::Page Header-->
            <div class="create-page-header">
                <h1>Edit Registration</h1>
                <p>Perbarui informasi data calon pelanggan dan penempatan jadwal pasang.</p>
            </div>
            <!--end::Page Header-->

            <div class="row justify-content-center">
                <div class="col-lg-12">
                    
                    <div class="card form-card">
                        <form method="post" id="registrasi-form" action="<?= BASE_URL ?>controllers/registrasi/update.php">
                            <input type="hidden" name="registrasi_key" value="<?= $row['registrasi_key'] ?>">
                            
                            <div class="card-body p-8 p-lg-10">
                                
                                <div class="row">
                                    <!-- Left Column: Data Pelanggan -->
                                    <div class="col-lg-6 pr-lg-8 border-right-lg">
                                        <div class="form-section-title">
                                            <i class="flaticon-avatar"></i>
                                            <span>Informasi Pelanggan & Alamat</span>
                                        </div>

                                        <div class="form-group">
                                            <label>Nama Lengkap</label>
                                            <div class="field-icon">
                                                <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                                    <circle cx="12" cy="7" r="4" />
                                                </svg>
                                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" placeholder="Masukkan nama lengkap" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>No. Telepon</label>
                                            <div class="field-icon">
                                                <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 12 19.79 19.79 0 0 1 1.04 3.38 2 2 0 0 1 3 1h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z" />
                                                </svg>
                                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" placeholder="Contoh: 081234567890" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Perumahan</label>
                                            <select class="form-control selectpicker" id="perumahan" name="perumahan" data-container="body" data-size="7" data-live-search="true" required>
                                                <option value="">— Pilih Perumahan —</option>
                                                <option value="LAINNYA">Lainnya</option>
                                                <?php foreach ($perumahan as $p): ?>
                                                    <option value="<?= htmlspecialchars($p['perumahan']) ?>" <?= ($p['perumahan'] === strtoupper(trim($row['perumahan'] ?? ''))) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($p['perumahan']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                            <div class="form-group mt-3" id="perumahan_lainnya_group" style="display:none;">
                                                <label class="font-weight-bold text-dark-50 font-size-sm">Nama Perumahan Baru</label>
                                                <input type="text" class="form-control" id="perumahan_lainnya" placeholder="Masukkan nama perumahan baru" style="height:44px; border-radius:10px;">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Alamat Pemasangan</label>
                                            <textarea class="form-control" name="location" rows="4" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan..." required><?= htmlspecialchars($row['location']) ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Right Column: Paket & Jadwal -->
                                    <div class="col-lg-6 pl-lg-8 mt-8 mt-lg-0">
                                        <div class="form-section-title">
                                            <i class="flaticon-layer"></i>
                                            <span>Paket & Jadwal Pemasangan</span>
                                        </div>

                                        <div class="form-group">
                                            <label>Pilih Paket Internet</label>
                                            <select class="form-control selectpicker" name="paket_internet" required>
                                                <option value="">— Pilih paket —</option>
                                                <?php foreach ($paketInternet as $key => $value): ?>
                                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($key == $row['paket_internet']) ? 'selected' : '' ?>><?= htmlspecialchars($value) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Tanggal Rencana Pemasangan</label>
                                            <div class="field-icon">
                                                <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                </svg>
                                                <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($row['date']) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Jam Kunjungan (Format HH:mm)</label>
                                            <div class="field-icon">
                                                <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                                <input type="text" class="form-control" name="time" id="time-schedule" value="<?= htmlspecialchars(substr($row['time'], 0, 5)) ?>" placeholder="09:00" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Card Actions Footer -->
                            <div class="card-footer-actions">
                                <a href="<?= BASE_URL ?>pages/registrasi/" class="btn btn-light-danger btn-cancel font-weight-bold">Batal</a>
                                <button type="submit" name="submit" class="btn btn-primary btn-save font-weight-bold px-8 shadow-sm">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-2" style="vertical-align:-2px">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    Update Registrasi
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
    $(function() {
        // Handle dropdown selectpicker 'Lainnya' logic
        function checkPerumahan() {
            if ($('#perumahan').val() === 'LAINNYA') {
                $('#perumahan_lainnya_group').show();
                $('#perumahan_lainnya')
                    .attr('name', 'perumahan')
                    .prop('required', true);
                $('#perumahan').removeAttr('name');
            } else {
                $('#perumahan_lainnya_group').hide();
                $('#perumahan_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');
                $('#perumahan').attr('name', 'perumahan');
            }
        }
        
        // Run once on load for editing if value is Lainnya
        checkPerumahan();

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

        // Time auto formatter and validation
        const timeInput = document.getElementById('time-schedule');
        const form = document.getElementById('registrasi-form');

        timeInput.addEventListener('input', function(e) {
            let value = e.target.value;
            value = value.replace(/[^\d]/g, '');
            if (value.length >= 3) {
                value = value.substring(0, 2) + ':' + value.substring(2, 4);
            }
            e.target.value = value;
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
            }
        });
    });
</script>