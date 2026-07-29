<?php
require_once __DIR__ . '/includes/config.php';


$success = isset($_GET['success']);

if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['form_token'];

$jamKerja = [
    "08:00",
    "09:00",
    "10:00",
    "11:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>CMS Jabbar</title>
    <meta name="description" content="Login page example" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" /> <!--end::Fonts-->
    <!--begin::Page Custom Styles(used by this page)-->
    <link href="assets/css/pages/login/classic/login-4.css" rel="stylesheet" type="text/css" />
    <!--end::Page Custom Styles-->

    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles-->

    <!--begin::Layout Themes(used by all pages)-->

    <link href="assets/css/themes/layout/header/base/light.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/themes/layout/header/menu/light.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/themes/layout/brand/dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/themes/layout/aside/dark.css" rel="stylesheet" type="text/css" /> <!--end::Layout Themes-->

    <link rel="shortcut icon" href="assets/media/favicon.ico" />

</head>
<!--end::Head-->

<!--begin::Body-->

<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

    <!--begin::Main-->
    <div class="d-flex flex-column flex-root">
        <!--begin::Login-->
        <div class="login login-4 login-signin-on d-flex flex-row-fluid" id="kt_login">
            <div class="d-flex flex-center flex-row-fluid bgi-size-cover bgi-position-top bgi-no-repeat" style="background-image: url('assets/media/bg/bg-3.jpg');">
                <div class="mt-10 card col-lg-5 card-custom shadow-sm">
                    <div class="card-header pt-5 d-flex flex-column justify-content-center align-items-center">
                        <a href="">
                            <img src="<?= BASE_URL ?>assets/media/logos/logo.png" alt="" width="160">
                        </a>
                        <h3>Halo, Selamat Datang di Jabbar23!</h3>
                        <p class="text-muted font-weight-bold">Registrasi User</p>
                    </div>
                    <form method="post" class="form" action="<?= BASE_URL ?>controllers/registrasi/registrasiController.php">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input id="name" type="text" class="form-control" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input id="phone" type="tel" class="form-control" name="phone" autocomplete="off" placeholder="08xxxxxxxxxx"
                                    pattern="^(?:\+62|62|0)8[0-9]{8,11}$"
                                    required>
                            </div>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="form-group">
                                <label for="paket_internet">Paket</label>
                                <select class="form-control selectpicker" id="paket_internet" required name="paket_internet" data-size=" 7">
                                    <option value="">Select</option>
                                    <option value="5">5 mbps - 150rb/bln</option>
                                    <option value="10">10 mbps - 300rb/bln</option>
                                    <option value="30">30 mbps - 650rb/bln</option>
                                    <option value="50">50 mbps - 850rb/bln</option>
                                    <option value="100">100 mbps - 1jt/bln</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exampleTextarea">Kapan Anda ingin jadwal pemasangan?</label>
                                <input type="date" min="<?= date('Y-m-d', strtotime('+1 day')); ?>" required name="date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Jam Kunjungan</label>
                                <select class="form-control selectpicker" required name="time" data-size=" 7">
                                    <option value="">Select</option>
                                    <?php foreach ($jamKerja as $j): ?>
                                        <option value="<?= $j ?>"><?= $j ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exampleTextarea">Alamat</label>
                                <textarea class="form-control" id="exampleTextarea" required name="location" autocomplete="off" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <?php if (isset($_SESSION['username'])): ?>
                                <a href="<?= BASE_URL ?>pages/registrasi/" class="btn btn-light-success font-weight-bold">Kembali</a>
                            <?php endif; ?>
                            <button type="reset" class="btn btn-light-danger font-weight-bold">Reset</button>
                            <button type="submit" name="submit" class="btn btn-primary font-weight-bold">Registrasi</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- sweetalert -->
            <?php if (isset($_SESSION['alert'])): ?>
                <script src="<?= BASE_URL ?>assets/js/pages/features/miscellaneous/sweetalert2.js"></script>
                <script>
                    Swal.fire({
                        icon: "<?= $_SESSION['alert']['icon'] ?>",
                        title: "<?= $_SESSION['alert']['title'] ?>",
                        text: "<?= $_SESSION['alert']['text'] ?>",
                        confirmButtonText: "<?= $_SESSION['alert']['button'] ?>",
                        customClass: {
                            confirmButton: "btn font-weight-bold btn-<?= $_SESSION['alert']['style'] ?>",
                            icon: "m-auto"
                        }
                    });
                </script>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>


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
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>

</body>
<!--end::Body-->

</html>