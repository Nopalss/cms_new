<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'user';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container pt-6 pb-8">
        
        <!-- Header Banner & Action Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-6 gap-3">
            <div>
                <h3 class="text-dark font-weight-bolder mb-1">
                    <i class="flaticon2-user-outline text-primary mr-2 font-size-h2"></i> Tambah User Baru
                </h3>
                <div class="text-muted font-size-sm">Isi formulir berikut untuk menambahkan akun pengguna baru ke dalam sistem</div>
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
                                <span class="svg-icon svg-icon-xl svg-icon-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <polygon points="0 0 24 0 24 24 0 24"/>
                                            <path d="M18,8 L16,8 C15.4477153,8 15,7.55228475 15,7 C15,6.44771525 15.4477153,6 16,6 L18,6 L18,4 C18,3.44771525 18.4477153,3 19,3 C19.5522847,3 20,3.44771525 20,4 L20,6 L22,6 C22.5522847,6 23,6.44771525 23,7 C23,7.55228475 22.5522847,8 22,8 L20,8 L20,10 C20,10.5522847 19.5522847,11 19,11 C18.4477153,11 18,10.5522847 18,10 L18,8 Z M9,11 C6.790861,11 5,9.209139 5,7 C5,4.790861 6.790861,3 9,3 C11.209139,3 13,4.790861 13,7 C13,9.209139 11.209139,11 9,11 Z" fill="#000000" opacity="0.3"/>
                                            <path d="M0.00065168429,20.1992055 C0.388258525,15.4265159 4.26191235,13 8.98334134,13 C13.7712164,13 17.7048837,15.2931929 17.9979143,20.2 C18.0095879,20.3954741 17.9979143,21 17.2466999,21 C13.541124,21 8.03472472,21 0.727502227,21 C0.476712155,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#000000"/>
                                        </g>
                                    </svg>
                                </span>
                            </span>
                            <h3 class="card-label font-weight-bolder text-dark m-0">
                                Form Registrasi Pekerja
                                <small class="text-muted d-block font-size-sm mt-1">Lengkapi data pribadi & kredensial di bawah ini</small>
                            </h3>
                        </div>
                    </div>

                    <!-- Card Body Clean White -->
                    <div class="card-body bg-white p-6 p-lg-9">
                        <form method="post" class="form" action="<?= BASE_URL ?>controllers/user/create.php">

                            <div class="row">
                                <!-- Kolom Kiri: Personal Info -->
                                <div class="col-md-6 border-right-md pr-md-6">
                                    <h6 class="text-dark font-weight-bolder mb-4">
                                        <i class="flaticon2-user text-primary mr-1"></i> Data Pribadi Pekerja
                                    </h6>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="name">
                                            Nama Lengkap <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-user text-muted"></i></span>
                                            </div>
                                            <input id="name" type="text" class="form-control pl-3" name="name" placeholder="Masukkan nama lengkap" required>
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
                                            <input id="nip" type="text" class="form-control pl-3" name="nip" placeholder="Contoh: A202607001" required>
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
                                            <input id="phone" type="tel" class="form-control pl-3" name="phone" placeholder="08xxxxxxxxxx" required>
                                        </div>
                                        <span class="form-text text-muted font-size-xs">Format: 08xx / 628xx</span>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Role & Credential -->
                                <div class="col-md-6 pl-md-6 mt-4 mt-md-0">
                                    <h6 class="text-dark font-weight-bolder mb-4">
                                        <i class="flaticon2-shield text-primary mr-1"></i> Otorisasi & Kredensial
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
                                                <option value="Admin">Admin</option>
                                                <option value="SuperAdmin">SuperAdmin</option>
                                                <option value="NOC">NOC</option>
                                                <option value="Manager">Manager</option>
                                                <option value="Teknisi">Teknisi</option>
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
                                            <input id="username" type="text" class="form-control font-weight-bolder text-primary pl-3" name="username" placeholder="mis. udin123" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-light-primary border" type="button" id="btnGenerateUsername" title="Generate Username Mudah Diingat">
                                                    ⚡ Auto
                                                </button>
                                            </div>
                                        </div>
                                        <span class="form-text text-muted font-size-xs">Format mudah diingat: <strong>nama123</strong> (misal: <em>udin123</em>). Anda dapat mengubahnya sesuai keinginan.</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="font-weight-bolder text-dark" for="password">
                                            Password Akun <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white border-right-0"><i class="flaticon2-lock text-muted"></i></span>
                                            </div>
                                            <input id="password" type="password" class="form-control pl-3" minlength="5" name="password" placeholder="Minimal 5 karakter" required>
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
                                <button type="submit" name="submit" class="btn btn-primary font-weight-bold px-8 py-3 shadow-sm">
                                    <i class="la la-check"></i> Simpan User
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
    let userEditedUsername = false;

    function generateCatchyUsername(fullName) {
        if (!fullName || !fullName.trim()) return '';
        const words = fullName.trim().split(/\s+/);
        let firstWord = words[0].toLowerCase().replace(/[^a-z0-9]/g, '');
        if (!firstWord) firstWord = 'user';
        return firstWord + '123';
    }

    if (nameInput && usernameInput) {
        nameInput.addEventListener('input', function() {
            if (!userEditedUsername) {
                usernameInput.value = generateCatchyUsername(nameInput.value);
            }
        });

        usernameInput.addEventListener('input', function() {
            userEditedUsername = true;
            this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
        });
    }

    if (btnGenerate && nameInput && usernameInput) {
        btnGenerate.addEventListener('click', function() {
            userEditedUsername = false;
            usernameInput.value = generateCatchyUsername(nameInput.value);
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
            eyeIcon.className = type === 'password' ? 'la la-eye' : 'la la-eye-slash text-primary';
        });
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>