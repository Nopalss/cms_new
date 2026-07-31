<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'user';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$id = $_GET['id'] ?? null;

$sql = "SELECT 
            u.username,
            u.role,
            COALESCE(t.name, a.name) AS name,
            COALESCE(t.phone, a.phone) AS phone,
            COALESCE(t.tech_id, a.admin_id) AS nip,
            a.jabatan
        FROM users u
        LEFT JOIN technician t ON u.username = t.username
        LEFT JOIN admin a ON u.username = a.username
        WHERE u.username= :username";

$stmt = $pdo->prepare($sql);
$stmt->execute([":username" => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops!',
        'text' => 'User tidak ditemukan.',
        'button' => 'Kembali',
        'style' => 'danger'
    ];
    header("Location: " . BASE_URL . "pages/user/");
    exit;
}

$roleOptions = ["Admin", "SuperAdmin", "NOC", "Manager", "Teknisi"];
$currentValue = ($row['role'] === 'teknisi') ? 'Teknisi' : (!empty($row['jabatan']) ? $row['jabatan'] : 'Admin');
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container pt-6 pb-8">
        
        <!-- Header Banner & Action Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-6 gap-3">
            <div>
                <h3 class="text-dark font-weight-bolder mb-1">
                    <i class="flaticon2-edit text-warning mr-2 font-size-h2"></i> Edit Data User
                </h3>
                <div class="text-muted font-size-sm">Perbarui profil, peran, atau kata sandi akun pengguna @<?= htmlspecialchars($row['username']) ?></div>
            </div>
            <div>
                <a href="<?= BASE_URL ?>pages/user/" class="btn btn-light-primary font-weight-bolder px-5 py-3">
                    <i class="la la-arrow-left"></i> Kembali ke Daftar User
                </a>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="row justify-content-center">
            <div class="col-12">
                
                <div class="card card-custom shadow-sm border-0 rounded-lg overflow-hidden bg-white">
                    <!-- Header Card Clean White -->
                    <div class="card-header border-bottom bg-white py-5">
                        <div class="card-title m-0">
                            <span class="card-icon mr-3">
                                <span class="svg-icon svg-icon-xl svg-icon-warning">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <rect x="0" y="0" width="24" height="24"/>
                                            <path d="M12.2674799,18.2323597 L12.0084872,5.45852451 C12.0004303,5.06114792 12.1504154,4.6768183 12.4255037,4.38993949 L15.0030167,1.70195304 L17.5910752,4.40093695 C17.8599071,4.6812911 18.0095067,5.05499603 18.0083938,5.44341307 L17.9718262,18.2062508 C17.9694575,19.0329966 17.2985816,19.701953 16.4718324,19.701953 L13.7671717,19.701953 C12.9505952,19.701953 12.2840328,19.0487684 12.2674799,18.2323597 Z" fill="#000000" fill-rule="nonzero" transform="translate(14.701953, 10.701953) rotate(-135.000000) translate(-14.701953, -10.701953) "/>
                                            <path d="M12.9,2 C13.4522847,2 13.9,2.44771525 13.9,3 C13.9,3.55228475 13.4522847,4 12.9,4 L6,4 C4.8954305,4 4,4.8954305 4,6 L4,18 C4,19.1045695 4.8954305,20 6,20 L18,20 C19.1045695,20 20,19.1045695 20,18 L20,13 C20,12.4477153 20.4477153,12 21,12 C21.5522847,12 22,12.4477153 22,13 L22,18 C22,20.209139 20.209139,22 18,22 L6,22 C3.790861,22 2,20.209139 2,18 L2,6 C2,3.790861 3.790861,2 6,2 L12.9,2 Z" fill="#000000" opacity="0.3"/>
                                        </g>
                                    </svg>
                                </span>
                            </span>
                            <h3 class="card-label font-weight-bolder text-dark m-0">
                                Form Edit User @<?= htmlspecialchars($row['username']) ?>
                                <small class="text-muted d-block font-size-sm mt-1">Sesuaikan informasi akun di bawah ini</small>
                            </h3>
                        </div>
                    </div>

                    <!-- Card Body Clean White -->
                    <div class="card-body bg-white p-6 p-lg-9">
                        <form method="post" class="form" action="<?= BASE_URL ?>controllers/user/update.php">

                            <input type="hidden" name="old_username" value="<?= htmlspecialchars($row['username']) ?>">

                            <div class="row">
                                <!-- Kolom Kiri: Personal Info -->
                                <div class="col-md-6 border-right-md pr-md-6">
                                    <h6 class="text-dark font-weight-bolder mb-4">
                                        <i class="flaticon2-user text-warning mr-1"></i> Data Pribadi Pekerja
                                    </h6>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="name">
                                            Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-user text-muted"></i></span>
                                            </div>
                                            <input id="name" type="text" class="form-control pl-3" name="name" value="<?= htmlspecialchars($row['name'] ?? '') ?>" placeholder="Masukkan nama lengkap" required>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="nip">
                                            No Induk Pekerja (NIP) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-badge text-muted"></i></span>
                                            </div>
                                            <input id="nip" type="text" class="form-control pl-3" name="nip" value="<?= htmlspecialchars($row['nip'] ?? '') ?>" placeholder="Masukkan NIP" required>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="phone">
                                            No WhatsApp / Telepon <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="la la-whatsapp text-success font-size-h4"></i></span>
                                            </div>
                                            <input id="phone" type="tel" class="form-control pl-3" name="phone" value="<?= htmlspecialchars($row['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Role & Password -->
                                <div class="col-md-6 pl-md-6 mt-4 mt-md-0">
                                    <h6 class="text-dark font-weight-bolder mb-4">
                                        <i class="flaticon2-shield text-warning mr-1"></i> Otorisasi & Akses
                                    </h6>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="role">
                                            Role / Jabatan Pekerja <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-layers text-muted"></i></span>
                                            </div>
                                            <select class="form-control pl-3 custom-select" id="role" name="role" required>
                                                <option value="">-- Pilih Role / Jabatan --</option>
                                                <?php foreach ($roleOptions as $opt): ?>
                                                    <?php $selected = (strcasecmp($opt, $currentValue) === 0) ? 'selected' : ''; ?>
                                                    <option value="<?= $opt ?>" <?= $selected ?>><?= $opt ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="username">
                                            Username Sistem <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-user-outline-symbol text-muted"></i></span>
                                            </div>
                                            <input id="username" type="text" class="form-control font-weight-bolder text-primary pl-3" name="username" value="<?= htmlspecialchars($row['username']) ?>" placeholder="mis. udin123" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-light-primary border" type="button" id="btnGenerateUsername" title="Generate Username dari Nama">
                                                    ⚡ Auto
                                                </button>
                                            </div>
                                        </div>
                                        <span class="form-text text-muted font-size-xs">Format mudah diingat: <strong>nama123</strong> (misal: <em>udin123</em>). Anda dapat mengubahnya.</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="password">
                                            Ubah Password (Opsional)
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-lock text-muted"></i></span>
                                            </div>
                                            <input id="password" type="password" class="form-control pl-3" minlength="5" name="password" placeholder="Kosongkan jika tidak ingin merubah password">
                                            <div class="input-group-append">
                                                <button class="btn btn-light-secondary border" type="button" id="togglePassword">
                                                    <i class="la la-eye" id="eyeIcon"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Card Clean White -->
                            <div class="card-footer border-top bg-white px-0 pt-6 mt-4 d-flex justify-content-between align-items-center">
                                <a href="<?= BASE_URL ?>pages/user/" class="btn btn-light-danger font-weight-bold px-6 py-3">
                                    <i class="la la-close"></i> Batal
                                </a>
                                <button type="submit" name="submit" class="btn btn-warning font-weight-bold px-8 py-3 shadow-sm">
                                    <i class="la la-save"></i> Update User
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const usernameInput = document.getElementById('username');
    const btnGenerate = document.getElementById('btnGenerateUsername');

    if (btnGenerate && nameInput && usernameInput) {
        btnGenerate.addEventListener('click', function() {
            const fullName = nameInput.value || '';
            const words = fullName.trim().split(/\s+/);
            let firstWord = words[0].toLowerCase().replace(/[^a-z0-9]/g, '');
            if (!firstWord) firstWord = 'user';
            usernameInput.value = firstWord + '123';
        });
    }

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
        });
    }

    // Password Visibility Toggle
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.className = type === 'password' ? 'la la-eye' : 'la la-eye-slash text-warning';
        });
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>