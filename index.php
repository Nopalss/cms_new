<?php
require __DIR__ . "/controllers/LoginController.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- <base href="../../../../"> -->
    <meta charset="utf-8" />
    <title>CMS Jabbar</title>
    <meta name="description" content="Login page example" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!--begin::Fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" />

    <!--begin::Page Custom Styles(used by this page)-->
    <link href="<?= asset_ver('assets/css/pages/login/classic/login-4.css') ?>" rel="stylesheet" type="text/css" />
    <!--end::Page Custom Styles-->

    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="<?= asset_ver('assets/plugins/global/plugins.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= asset_ver('assets/css/style.bundle.css') ?>" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles-->

    <!--begin::Layout Themes(used by all pages)-->
    <link href="<?= asset_ver('assets/css/themes/layout/brand/dark.css') ?>" rel="stylesheet" type="text/css" />
    <link href="<?= asset_ver('assets/css/themes/layout/aside/dark.css') ?>" rel="stylesheet" type="text/css" />
    <!--end::Layout Themes-->

    <link rel="shortcut icon" href="<?= asset_ver('assets/media/favicon.ico') ?>" />

    <!-- Progressive Web App (PWA) Meta & Manifest -->
    <link rel="manifest" href="<?= asset_ver('manifest.json') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JTracks">
    <link rel="apple-touch-icon" href="<?= asset_ver('assets/media/logos/icon-192.png') ?>">
    <meta name="theme-color" content="#0E7C7B">
</head>
<!--end::Head-->

<!--begin::Body-->

<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

    <!--begin::Main-->
    <div class="d-flex flex-column flex-root">
        <!--begin::Login-->
        <div class="login login-4 login-signin-on d-flex flex-row-fluid" id="kt_login">
            <div class="d-flex flex-center flex-row-fluid bgi-size-cover bgi-position-top bgi-no-repeat" style="background-image: url('assets/media/bg/bg-3.jpg');">
                <div class="login-form text-center p-7 position-relative overflow-hidden">
                    <!--begin::Login Header-->
                    <div class="d-flex flex-center mb-3">
                        <a href="#">
                            <img src="assets/media/logos/logo.png" class="max-h-150px" alt="" />
                        </a>
                    </div>
                    <!--end::Login Header-->

                    <!--begin::Login Sign in form-->
                    <div class="login-signin">
                        <div class="mb-15">
                            <h3>Sign In</h3>
                            <div class="text-muted font-weight-bold">Enter your details to login to your account:</div>

                        </div>
                        <form class="form" id="kt_login_signin_form" method="post">
                            <div class="form-group mb-5">
                                <input class="form-control h-auto form-control-solid py-4 px-8" type="text" placeholder="Username" name="username" autocomplete="off" />
                            </div>
                            <div class="form-group mb-5">
                                <input class="form-control h-auto form-control-solid py-4 px-8" type="password" placeholder="Password" name="password" autocomplete="current-password" />
                            </div>
                            <button id="kt_login_signin_submit" type="submit" name="login" class="btn btn-primary font-weight-bold px-9 py-4 my-3 mx-4">Login</button>
                        </form>
                    </div>

                    <!-- Tombol Download / Install Application Mobile -->
                    <div class="mt-6 pt-4" style="border-top: 1px dashed #E4E6EF;">
                        <button type="button" id="btnDownloadApp" class="btn btn-outline-primary font-weight-bolder py-3 px-6 d-inline-flex align-items-center justify-content-center" style="border-radius: 12px; border-width: 2px; transition: all 0.2s;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            📲 Download &amp; Install App Mobile
                        </button>
                    </div>
                    <!--end::Login Sign in form-->

                    <!--begin::Login forgot password form-->
                    <div class="login-forgot">
                        <div class="mb-20">
                            <h3>Forgotten Password ?</h3>
                            <div class="text-muted font-weight-bold">Enter your email to reset your password</div>
                        </div>
                        <form class="form" id="kt_login_forgot_form">
                            <div class="form-group mb-10">
                                <input class="form-control form-control-solid h-auto py-4 px-8" type="text" placeholder="Email" name="email" autocomplete="off" />
                            </div>
                            <div class="form-group d-flex flex-wrap flex-center mt-10">
                                <button id="kt_login_forgot_submit" class="btn btn-primary font-weight-bold px-9 py-4 my-3 mx-2">Request</button>
                                <button id="kt_login_forgot_cancel" class="btn btn-light-primary font-weight-bold px-9 py-4 my-3 mx-2">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <!--end::Login forgot password form-->
                </div>
            </div>

        </div>
        <!--end::Login-->
    </div>
    <!--end::Main-->
    <!--begin::Global Config(global config for global JS scripts)-->
    <script>
        var KTAppSettings = {
            "breakpoints": {
                "sm": 576,
                "md": 768,
                "lg": 992,
                "xl": 1200,
                "xxl": 1400
            },
            "colors": {
                "theme": {
                    "base": {
                        "white": "#ffffff",
                        "primary": "#3699FF",
                        "secondary": "#E5EAEE",
                        "success": "#1BC5BD",
                        "info": "#8950FC",
                        "warning": "#FFA800",
                        "danger": "#F64E60",
                        "light": "#E4E6EF",
                        "dark": "#181C32"
                    },
                    "light": {
                        "white": "#ffffff",
                        "primary": "#E1F0FF",
                        "secondary": "#EBEDF3",
                        "success": "#C9F7F5",
                        "info": "#EEE5FF",
                        "warning": "#FFF4DE",
                        "danger": "#FFE2E5",
                        "light": "#F3F6F9",
                        "dark": "#D6D6E0"
                    },
                    "inverse": {
                        "white": "#ffffff",
                        "primary": "#ffffff",
                        "secondary": "#3F4254",
                        "success": "#ffffff",
                        "info": "#ffffff",
                        "warning": "#ffffff",
                        "danger": "#ffffff",
                        "light": "#464E5F",
                        "dark": "#ffffff"
                    }
                },
                "gray": {
                    "gray-100": "#F3F6F9",
                    "gray-200": "#EBEDF3",
                    "gray-300": "#E4E6EF",
                    "gray-400": "#D1D3E0",
                    "gray-500": "#B5B5C3",
                    "gray-600": "#7E8299",
                    "gray-700": "#5E6278",
                    "gray-800": "#3F4254",
                    "gray-900": "#181C32"
                }
            },
            "font-family": "Poppins"
        };
    </script>
    <!--end::Global Config-->

    <!--begin::Global Theme Bundle(used by all pages)-->
    <script src="<?= asset_ver('assets/plugins/global/plugins.bundle.js') ?>"></script>
    <!--end::Global Theme Bundle-->


    <!-- PWA Installation Handler Script -->
    <script>
        let pwaInstallPrompt = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            pwaInstallPrompt = e;
            const btn = document.getElementById('btnDownloadApp');
            if (btn) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    📲 Download & Install JTracks App
                `;
            }
        });

        document.getElementById('btnDownloadApp')?.addEventListener('click', () => {
            if (pwaInstallPrompt) {
                pwaInstallPrompt.prompt();
                pwaInstallPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('[PWA] User accepted installation');
                    }
                    pwaInstallPrompt = null;
                });
            } else {
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (isIOS) {
                    if (typeof Swal === 'function') {
                        Swal.fire({
                            title: 'Install di iPhone (Safari)',
                            html: '<div style="text-align:left; font-size:13px; line-height:1.6;">1. Klik tombol <strong>Share 📤</strong> di bagian bawah Safari.<br>2. Gulir ke bawah dan pilih <strong>"Add to Home Screen" ➕</strong> (Tambah ke Layar Utama).</div>',
                            icon: 'info',
                            confirmButtonText: 'Mengerti'
                        });
                    } else {
                        alert("Untuk iPhone:\n1. Klik tombol Share (ikon panah ke atas) di Safari.\n2. Pilih 'Add to Home Screen' (Tambah ke Layar Utama).");
                    }
                } else {
                    if (typeof Swal === 'function') {
                        Swal.fire({
                            title: 'Install Aplikasi JTracks',
                            html: '<div style="text-align:left; font-size:13px; line-height:1.6;">1. Klik menu titik tiga (<strong>⋮</strong>) di pojok kanan atas browser HP kamu.<br>2. Pilih <strong>"Install Aplikasi"</strong> atau <strong>"Tambah ke Layar Utama"</strong>.</div>',
                            icon: 'info',
                            confirmButtonText: 'Mengerti'
                        });
                    } else {
                        alert("Untuk meng-install aplikasi JTracks:\n1. Buka menu titik tiga (⋮) di pojok kanan atas Chrome/Browser HP.\n2. Pilih 'Install Aplikasi' atau 'Tambahkan ke Layar Utama'.");
                    }
                }
            }
        });
    </script>
</body>
<!--end::Body-->
<!-- sweetalert -->


<?php if (isset($_SESSION['alert'])): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/features/miscellaneous/sweetalert2.js"></script>
    <script>
        Swal.fire({
            icon: "<?= $_SESSION['alert']['icon'] ?>",
            title: "<?= $_SESSION['alert']['title'] ?>",
            text: "<?= $_SESSION['alert']['text'] ?>",
            confirmButtonText: "<?= $_SESSION['alert']['button'] ?>",
            heightAuto: false,
            customClass: {
                confirmButton: "btn font-weight-bold btn-<?= $_SESSION['alert']['style'] ?>",
                icon: "m-auto"
            }
        });
    </script>
    <?php unset($_SESSION['alert']); ?>
<?php endif; ?>


</html>