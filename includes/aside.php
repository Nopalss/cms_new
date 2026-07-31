<!--begin::Body-->
<?php
require_once __DIR__ . '/config.php';
$role         = strtolower(trim($_SESSION['role'] ?? ''));
$jabatan      = strtolower(trim($_SESSION['jabatan'] ?? ''));
$current_menu = $_SESSION['menu'] ?? '';

/**
 * Pengecekan Akses Menu berdasarkan Role & Jabatan:
 * - SuperAdmin : Semua Menu
 * - Admin      : Dashboard, Registrasi, Request (IKR/Service/Dismantle), Customers, Paket Internet, User
 * - NOC        : Dashboard, ALL tiketing, All Reports, Stok ONT, Teknisi Report, Tim Teknisi, Kinerja Tim SAW, User, Paket Internet, Customers, Rangking
 * - Manager    : Dashboard, All Reports (IKR, Service, Dismantle, Teknisi Report), User, Paket Internet, Kinerja Tim SAW, Rangking
 * - Teknisi    : Schedule/jadwal pekerjaan, All Reports, Teknisi Report.
 */
function canAccessMenu($menuKey, $role, $jabatan) {
    $role    = strtolower(trim($role));
    $jabatan = strtolower(trim($jabatan));

    // 1. SuperAdmin (akses semua menu)
    if ($role === 'superadmin' || $jabatan === 'superadmin') {
        return true;
    }

    // 2. NOC (diutamakan sebelum Admin umum)
    if ($jabatan === 'noc' || $role === 'noc') {
        return in_array($menuKey, [
            'dashboard', 'request', 
            'ticketing', 'ticket_instalasi', 'ticket_service', 
            'ikr', 'service', 'dismantle', 
            'ont_stock', 'teknisi', 'tim_teknisi', 'team_saw', 
            'user', 'packages', 'customer', 'ranking'
        ]);
    }

    // 3. Manager
    if ($jabatan === 'manager' || $jabatan === 'manajer' || $role === 'manager' || $role === 'manajer') {
        return in_array($menuKey, [
            'dashboard', 'request', 'ikr', 'service', 'dismantle', 
            'teknisi', 'user', 'packages', 'team_saw', 'ranking'
        ]);
    }

    // 4. Teknisi
    if ($jabatan === 'teknisi' || $role === 'teknisi') {
        return in_array($menuKey, [
            'schedule', 'request', 'ikr', 'service', 'dismantle', 'teknisi'
        ]);
    }

    // 5. Admin umum
    if ($jabatan === 'admin' || $role === 'admin') {
        return in_array($menuKey, [
            'dashboard', 'registrasi', 'request', 
            'ticketing', 'ticket_instalasi', 'ticket_service', 
            'customer', 'packages', 'user'
        ]);
    }

    // Fallback: jika jabatan/role tidak dikenali, tolak akses
    return false;
}

// ==========================================
// MIDDLEWARE OTORISASI PERLINDUNGAN HALAMAN
// ==========================================
$menuKeyMap = [
    'dashboard'           => 'dashboard',
    'registrasi'          => 'registrasi',
    'ticket_instalasi'    => 'ticket_instalasi',
    'ticket_service'      => 'ticket_service',
    'request ikr'         => 'request',
    'request maintenance' => 'request',
    'request dismantle'   => 'request',
    'queue'               => 'queue',
    'schedule'            => 'schedule',
    'ikr'                 => 'ikr',
    'service'             => 'service',
    'dismantle'           => 'dismantle',
    'ont_stock'           => 'ont_stock',
    'customer'            => 'customer',
    'packages'            => 'packages',
    'teknisi'             => 'teknisi',
    'status_master'       => 'status_master',
    'tim_teknisi'         => 'tim_teknisi',
    'team_saw'            => 'team_saw',
    'ranking'             => 'ranking',
    'user'                => 'user',
];

if (!empty($current_menu) && isset($menuKeyMap[$current_menu])) {
    $menuCheck = $menuKeyMap[$current_menu];
    if (!canAccessMenu($menuCheck, $role, $jabatan)) {
        $_SESSION['alert'] = [
            'icon'   => 'warning',
            'title'  => 'Akses Ditolak!',
            'text'   => 'Role / Jabatan Anda tidak memiliki wewenang untuk mengakses halaman ini.',
            'button' => 'Kembali',
            'style'  => 'warning'
        ];
        $fallbackUrl = ($role === 'teknisi') ? BASE_URL . 'pages/schedule/' : BASE_URL . 'pages/dashboard.php';
        header("Location: " . $fallbackUrl);
        exit;
    }
}
?>

<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

    <!--begin::Main-->
    <!--begin::Header Mobile-->
    <div id="kt_header_mobile" class="header-mobile align-items-center header-mobile-fixed">
        <!--begin::Logo-->
        <a href="<?= BASE_URL ?>pages/dashboard.php">
            <img alt="Logo" src="<?= BASE_URL ?>assets/media/logos/logo.png" class="max-w-65px" />
        </a>
        <!--end::Logo-->

        <!--begin::Toolbar-->
        <div class="d-flex align-items-center">
            <button class="btn p-0 burger-icon burger-icon-left" id="kt_aside_mobile_toggle">
                <span></span>
            </button>
            <button class="btn btn-hover-text-primary p-0 ml-2" id="kt_header_mobile_topbar_toggle">
                <span class="svg-icon svg-icon-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <polygon points="0 0 24 0 24 24 0 24" />
                            <path d="M12,11 C9.790861,11 8,9.209139 8,7 C8,4.790861 9.790861,3 12,3 C14.209139,3 16,4.790861 16,7 C16,9.209139 14.209139,11 12,11 Z" fill="#000000" opacity="0.3" />
                            <path d="M3.00065168,20.1992055 C3.38825852,15.4265159 7.26191235,13 11.9833413,13 C16.7712164,13 20.7048837,15.2931929 20.9979143,20.2 C21.0095879,20.3954741 20.9979143,21 20.2466999,21 C16.541124,21 11.0347247,21 3.72750223,21 C3.47671215,21 2.97953825,20.45918 3.00065168,20.1992055 Z" fill="#000000" />
                        </g>
                    </svg>
                </span>
            </button>
        </div>
        <!--end::Toolbar-->
    </div>
    <!--end::Header Mobile-->

    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-row flex-column-fluid page">

            <!--begin::Aside-->
            <div class="aside aside-left aside-fixed d-flex flex-column flex-row-auto" id="kt_aside">
                <!--begin::Brand-->
                <div class="brand flex-column-auto" id="kt_brand">
                    <a href="<?= BASE_URL ?>pages/dashboard.php" class="brand-logo">
                        <img alt="Logo" src="<?= BASE_URL ?>assets/media/logos/logo.png" class="max-w-90px" />
                    </a>
                    <button class="brand-toggle btn btn-sm px-0" id="kt_aside_toggle">
                        <span class="svg-icon svg-icon-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <polygon points="0 0 24 0 24 24 0 24" />
                                    <path d="M5.29288961,6.70710318 C4.90236532,6.31657888 4.90236532,5.68341391 5.29288961,5.29288961 C5.68341391,4.90236532 6.31657888,4.90236532 6.70710318,5.29288961 L12.7071032,11.2928896 C13.0856821,11.6714686 13.0989277,12.281055 12.7371505,12.675721 L7.23715054,18.675721 C6.86395813,19.08284 6.23139076,19.1103429 5.82427177,18.7371505 C5.41715278,18.3639581 5.38964985,17.7313908 5.76284226,17.3242718 L10.6158586,12.0300721 L5.29288961,6.70710318 Z" fill="#000000" transform="translate(8.999997, 11.999999) scale(-1, 1) translate(-8.999997, -11.999999) " />
                                    <path d="M10.7071009,15.7071068 C10.3165766,16.0976311 9.68341162,16.0976311 9.29288733,15.7071068 C8.90236304,15.3165825 8.90236304,14.6834175 9.29288733,14.2928932 L15.2928873,8.29289322 C15.6714663,7.91431428 16.2810527,7.90106866 16.6757187,8.26284586 L22.6757187,13.7628459 C23.0828377,14.1360383 23.1103407,14.7686056 22.7371482,15.1757246 C22.3639558,15.5828436 21.7313885,15.6103465 21.3242695,15.2371541 L16.0300699,10.3841378 L10.7071009,15.7071068 Z" fill="#000000" opacity="0.3" transform="translate(15.999997, 11.999999) scale(-1, 1) rotate(-270.000000) translate(-15.999997, -11.999999) " />
                                </g>
                            </svg>
                        </span>
                    </button>
                </div>
                <!--end::Brand-->

                <!--begin::Aside Menu-->
                <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
                    <div id="kt_aside_menu" class="aside-menu my-4" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">
                        <ul class="menu-nav">

                            <!-- Dashboard -->
                            <?php if (canAccessMenu('dashboard', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'dashboard') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/dashboard.php" class="menu-link">
                                        <span class="svg-icon menu-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><polygon points="0 0 24 0 24 24 0 24"/><path d="M12.9336061,16.072447 L19.36,10.9564761 L19.5181585,10.8312381 C20.1676248,10.3169571 20.2772143,9.3735535 19.7629333,8.72408713 C19.6917232,8.63415859 19.6104327,8.55269514 19.5206557,8.48129411 L12.9336854,3.24257445 C12.3871201,2.80788259 11.6128799,2.80788259 11.0663146,3.24257445 L4.47482784,8.48488609 C3.82645598,9.00054628 3.71887192,9.94418071 4.23453211,10.5925526 C4.30500305,10.6811601 4.38527899,10.7615046 4.47382636,10.8320511 L4.63,10.9564761 L11.0659024,16.0730648 C11.6126744,16.5077525 12.3871218,16.5074963 12.9336061,16.072447 Z" fill="#000000"/><path d="M11.0563554,18.6706981 L5.33593024,14.122919 C4.94553994,13.8125559 4.37746707,13.8774308 4.06710397,14.2678211 C4.06471678,14.2708238 4.06234874,14.2738418 4.06,14.2768747 L4.06,14.2768747 C3.75257288,14.6738539 3.82516916,15.244888 4.22214834,15.5523151 C4.22358765,15.5534297 4.2250303,15.55454 4.22647627,15.555646 L11.0872776,20.8031356 C11.6250734,21.2144692 12.371757,21.2145375 12.909628,20.8033023 L19.7677785,15.559828 C20.1693192,15.2528257 20.2459576,14.6784381 19.9389553,14.2768974 C19.9376429,14.2751809 19.9363245,14.2734691 19.935,14.2717619 L19.935,14.2717619 C19.6266937,13.8743807 19.0546209,13.8021712 18.6572397,14.1104775 C18.654352,14.112718 18.6514778,14.1149757 18.6486172,14.1172508 L12.9235044,18.6705218 C12.377022,19.1051477 11.6029199,19.1052208 11.0563554,18.6706981 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">Dashboard</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Jadwal Tugas -->
                            <?php if (canAccessMenu('schedule', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'schedule') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/schedule/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <rect x="0" y="0" width="24" height="24"/>
                                                    <path d="M6,3 L18,3 C19.1045695,3 20,3.8954305 20,5 L20,19 C20,20.1045695 19.6568542,21 18,21 L6,21 C4.34314575,21 3,20.1045695 3,19 L3,5 C3,3.8954305 3.8954305,3 6,3 Z" fill="#000000" opacity="0.3"/>
                                                    <path d="M7,7 L17,7 C17.5522847,7 18,7.44771525 18,8 C18,8.55228475 17.5522847,9 17,9 L7,9 C6.44771525,9 6,8.55228475 6,8 C6,7.44771525 6.44771525,7 7,7 Z M7,11 L17,11 C17.5522847,11 18,11.44771525 18,12 C18,12.5522847 17.5522847,13 17,13 L7,13 C6.44771525,13 6,12.5522847 6,12 C6,11.44771525 6.44771525,11 7,11 Z M7,15 L13,15 C13.5522847,15 14,15.4477153 14,16 C14,16.5522847 13.5522847,17 13,17 L7,17 C6.44771525,17 6,16.5522847 6,16 C6,15.4477153 6.44771525,15 7,15 Z" fill="#000000"/>
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="menu-text">Jadwal Tugas</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Tiketing Submenu -->
                            <?php if (canAccessMenu('ticketing', $role, $jabatan)): ?>
                                <?php $is_ticket_active = in_array($current_menu, ['ticket_instalasi', 'ticket_service']); ?>
                                <li class="menu-item menu-item-submenu <?= $is_ticket_active ? 'menu-item-open' : '' ?>" aria-haspopup="true" data-menu-toggle="hover">
                                    <a href="javascript:;" class="menu-link menu-toggle">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M4,16 L5,16 C5.55228475,16 6,16.4477153 6,17 C6,17.5522847 5.55228475,18 5,18 L4,18 C3.44771525,18 3,17.5522847 3,17 C3,16.4477153 3.44771525,16 4,16 Z M1,11 L5,11 C5.55228475,11 6,11.4477153 6,12 C6,12.5522847 5.55228475,13 5,13 L1,13 C0.44771525,13 6.76353751e-17,12.5522847 0,12 C-6.76353751e-17,11.4477153 0.44771525,11 1,11 Z M3,6 L5,6 C5.55228475,6 6,6.44771525 6,7 C6,7.55228475 5.55228475,8 5,8 L3,8 C2.44771525,8 2,7.55228475 2,7 C2,6.44771525 2.44771525,6 3,6 Z" fill="#000000" opacity="0.3"/><path d="M10,6 L22,6 C23.1045695,6 24,6.8954305 24,8 L24,16 C24,17.1045695 23.1045695,18 22,18 L10,18 C8.8954305,18 8,17.1045695 8,16 L8,8 C8,6.8954305 8.8954305,6 10,6 Z M21.0849395,8.0718316 L16,10.7185839 L10.9150605,8.0718316 C10.6132433,7.91473331 10.2368262,8.02389331 10.0743092,8.31564728 C9.91179228,8.60740125 10.0247174,8.9712679 10.3265346,9.12836619 L15.705737,11.9282847 C15.8894428,12.0239051 16.1105572,12.0239051 16.294263,11.9282847 L21.6734654,9.12836619 C21.9752826,8.9712679 22.0882077,8.60740125 21.9256908,8.31564728 C21.7631738,8.02389331 21.3867567,7.91473331 21.0849395,8.0718316 Z" fill="#000000"/></g></svg></span>
                                        <span class="menu-text">Tiketing</span>
                                        <i class="menu-arrow"></i>
                                    </a>
                                    <div class="menu-submenu">
                                        <i class="menu-arrow"></i>
                                        <ul class="menu-subnav">
                                            <li class="menu-item menu-item-parent" aria-haspopup="true"><span class="menu-link"><span class="menu-text">Tiketing</span></span></li>
                                            <li class="menu-item <?= ($current_menu == 'ticket_instalasi') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                                <a href="<?= BASE_URL ?>pages/ticketing/instalasi/dashboard.php" class="menu-link" target="_blank">
                                                    <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                    <span class="menu-text">Tiket Instalasi ↗</span>
                                                </a>
                                            </li>
                                            <li class="menu-item <?= ($current_menu == 'ticket_service') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                                <a href="<?= BASE_URL ?>pages/ticketing/service/dashboard.php" class="menu-link" target="_blank">
                                                    <i class="menu-bullet menu-bullet-dot"><span></span></i>
                                                    <span class="menu-text">Tiket Service ↗</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                            <?php endif; ?>

                            <!-- IKR Report -->
                            <?php if (canAccessMenu('ikr', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'ikr') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/ikr/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><rect fill="#000000" x="3" y="13" width="18" height="7" rx="2"/><path d="M17.4029496,9.54910207 L15.8599014,10.8215022 C14.9149052,9.67549895 13.5137472,9 12,9 C10.4912085,9 9.09418404,9.67104182 8.14910121,10.8106159 L6.60963188,9.53388797 C7.93073905,7.94090645 9.88958759,7 12,7 C14.1173586,7 16.0819686,7.94713944 17.4029496,9.54910207 Z M20.4681628,6.9788888 L18.929169,8.25618985 C17.2286725,6.20729644 14.7140097,5 12,5 C9.28974232,5 6.77820732,6.20393339 5.07766256,8.24796852 L3.54017812,6.96885102 C5.61676443,4.47281829 8.68922234,3 12,3 C15.3153667,3 18.3916375,4.47692603 20.4681628,6.9788888 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">IKR Report</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Service Report -->
                            <?php if (canAccessMenu('service', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'service') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/service_report/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M15.9497475,3.80761184 L13.0246125,6.73274681 C12.2435639,7.51379539 12.2435639,8.78012535 13.0246125,9.56117394 L14.4388261,10.9753875 C15.2198746,11.7564361 16.4862046,11.7564361 17.2672532,10.9753875 L20.1923882,8.05025253 C20.7341101,10.0447871 20.2295941,12.2556873 18.674559,13.8107223 C16.8453326,15.6399488 14.1085592,16.0155296 11.8839934,14.9444337 L6.75735931,20.0710678 C5.97631073,20.8521164 4.70998077,20.8521164 3.92893219,20.0710678 C3.1478836,19.2900192 3.1478836,18.0236893 3.92893219,17.2426407 L9.05556629,12.1160066 C7.98447038,9.89144078 8.36005124,7.15466739 10.1892777,5.32544095 C11.7443127,3.77040588 13.9552129,3.26588995 15.9497475,3.80761184 Z" fill="#000000"/><path d="M16.6568542,5.92893219 L18.0710678,7.34314575 C18.4615921,7.73367004 18.4615921,8.36683502 18.0710678,8.75735931 L16.6913928,10.1370344 C16.3008685,10.5275587 15.6677035,10.5275587 15.2771792,10.1370344 L13.8629656,8.7228208 C13.4724413,8.33229651 13.4724413,7.69913153 13.8629656,7.30860724 L15.2426407,5.92893219 C15.633165,5.5384079 16.26633,5.5384079 16.6568542,5.92893219 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">Service Report</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Dismantle Report -->
                            <?php if (canAccessMenu('dismantle', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'dismantle') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/dismantle/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z" fill="#000000"/><path d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">Dismantle Report</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Ranking Wilayah -->
                            <?php if (canAccessMenu('ranking', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'ranking') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/ranking.php" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><rect fill="#000000" opacity="0.3" x="13" y="4" width="3" height="16" rx="1.5"/><rect fill="#000000" x="8" y="9" width="3" height="11" rx="1.5"/><rect fill="#000000" x="18" y="11" width="3" height="9" rx="1.5"/><rect fill="#000000" x="3" y="13" width="3" height="7" rx="1.5"/></g></svg></span>
                                        <span class="menu-text">Ranking Wilayah</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Stok ONT -->
                            <?php if (canAccessMenu('ont_stock', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'ont_stock') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/ont/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><rect fill="#000000" x="3" y="13" width="18" height="7" rx="2"/><path d="M17.4029496,9.54910207 L15.8599014,10.8215022 C14.9149052,9.67549895 13.5137472,9 12,9 C10.4912085,9 9.09418404,9.67104182 8.14910121,10.8106159 L6.60963188,9.53388797 C7.93073905,7.94090645 9.88958759,7 12,7 C14.1173586,7 16.0819686,7.94713944 17.4029496,9.54910207 Z M20.4681628,6.9788888 L18.929169,8.25618985 C17.2286725,6.20729644 14.7140097,5 12,5 C9.28974232,5 6.77820732,6.20393339 5.07766256,8.24796852 L3.54017812,6.96885102 C5.61676443,4.47281829 8.68922234,3 12,3 C15.3153667,3 18.3916375,4.47692603 20.4681628,6.9788888 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">Stok ONT</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Customers -->
                            <?php if (canAccessMenu('customer', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'customer') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/customers/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M6,2 L18,2 C19.6568542,2 21,3.34314575 21,5 L21,19 C21,20.6568542 19.6568542,22 18,22 L6,22 C4.34314575,22 3,20.6568542 3,19 L3,5 C3,3.34314575 4.34314575,2 6,2 Z M12,11 C13.1045695,11 14,10.1045695 14,9 C14,7.8954305 13.1045695,7 12,7 C10.8954305,7 10,7.8954305 10,9 C10,10.1045695 10.8954305,11 12,11 Z M7.00036205,16.4995035 C6.98863236,16.6619875 7.26484009,17 7.4041679,17 C11.463736,17 14.5228466,17 16.5815,17 C16.9988413,17 17.0053266,16.6221713 16.9988413,16.5 C16.8360465,13.4332455 14.6506758,12 11.9907452,12 C9.36772908,12 7.21569918,13.5165724 7.00036205,16.4995035 Z" fill="#000000"/></g></svg></span>
                                        <span class="menu-text">Customers</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Paket Internet -->
                            <?php if (canAccessMenu('packages', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'packages') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/packages/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M12,3 C7.02943725,3 3,7.02943725 3,12 C3,16.9705627 7.02943725,21 12,21 C16.9705627,21 21,16.9705627 21,7.02943725 16.9705627,3 12,3 Z M12,18 C8.6862915,18 6,15.3137085 6,12 C6,8.6862915 8.6862915,6 12,6 C15.3137085,6 18,8.6862915 18,12 C18,15.3137085 15.3137085,18 12,18 Z" fill="#000000" opacity="0.3"/><circle fill="#000000" cx="12" cy="12" r="3"/></g></svg></span>
                                        <span class="menu-text">Paket Internet</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Teknisi Report -->
                            <?php if (canAccessMenu('teknisi', $role, $jabatan)): ?>
                                <?php 
                                $teknisiReportUrl = ($role === 'teknisi' && !empty($_SESSION['id_karyawan']))
                                    ? BASE_URL . 'pages/teknisi/detail_teknisi.php?id=' . urlencode($_SESSION['id_karyawan'])
                                    : BASE_URL . 'pages/teknisi/';
                                ?>
                                <li class="menu-item <?= ($current_menu == 'teknisi') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= $teknisiReportUrl ?>" class="menu-link">
                                        <span class="svg-icon menu-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M8.46446609,11.2928932 L7.40380592,10.232233 C7.20854378,10.0369709 7.20854378,9.72038841 7.40380592,9.52512627 L8.1109127,8.81801948 C8.30617485,8.62275734 8.62275734,8.62275734 8.81801948,8.81801948 L15.1819805,15.1819805 C15.3772427,15.3772427 15.3772427,15.6938252 15.1819805,15.8890873 L14.4748737,16.5961941 C14.2796116,16.7914562 13.9630291,16.7914562 13.767767,16.5961941 L12.7071068,15.5355339 L7.05025253,21.1923882 C6.26920395,21.9734367 5.00287399,21.9734367 4.22182541,21.1923882 L2.80761184,19.7781746 C2.02656326,18.997126 2.02656326,17.7307961 2.80761184,16.9497475 L8.46446609,11.2928932 Z M4.5753788,18.0104076 C4.38011665,18.2056698 4.38011665,18.5222523 4.5753788,18.7175144 C4.77064094,18.9127766 5.08722343,18.9127766 5.28248558,18.7175144 L9.52512627,14.4748737 C9.72038841,14.2796116 9.72038841,13.9630291 9.52512627,13.767767 C9.32986412,13.5725048 9.01328163,13.5725048 8.81801948,13.767767 L4.5753788,18.0104076 Z" fill="#000000" opacity="0.3"/><path d="M16.9497475,5.63603897 L16.7788182,5.4651097 C16.5835561,5.26984755 16.5835561,4.95326506 16.7788182,4.75800292 C16.8266988,4.71012232 16.8838059,4.67246608 16.9466763,4.64731796 L19.4720576,3.63716542 C19.657766,3.56288206 19.869875,3.60641908 20.0113063,3.74785037 L20.2521496,3.98869366 C20.3935809,4.13012495 20.4371179,4.342234 20.3628346,4.52794239 L19.352682,7.05332375 C19.2501253,7.30971551 18.9591401,7.43442346 18.7027484,7.33186676 C18.6398781,7.30671864 18.5827709,7.2690624 18.5348903,7.2211818 L18.363961,7.05025253 L12.7071068,12.7071068 L11.2928932,11.2928932 L16.9497475,5.63603897 Z" fill="#000000"/></g></svg></span>
                                        <span class="menu-text">Teknisi Report</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Status Master -->
                            <?php if (canAccessMenu('status_master', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'status_master') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/status_master/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M12.2674799,18.2323597 L12.0084872,5.45852451 C12.0004303,5.06114792 12.1504154,4.6768183 12.4255037,4.38993949 L15.0030167,1.70195304 L17.5910752,4.40093695 C17.8599071,4.6812911 18.0095067,5.05499603 18.0083938,5.44341307 L17.9718262,18.2062508 C17.9694575,19.0329966 17.2985816,19.701953 16.4718324,19.701953 L13.7671717,19.701953 C12.9505952,19.701953 12.2840328,19.0487684 12.2674799,18.2323597 Z" fill="#000000" transform="translate(14.701953, 10.701953) rotate(-135.000000) translate(-14.701953, -10.701953) "/><path d="M12.9,2 C13.4522847,2 13.9,2.44771525 13.9,3 C13.9,3.55228475 13.4522847,4 12.9,4 L6,4 C4.8954305,4 4,4.8954305 4,6 L4,18 C4,19.1045695 4.8954305,20 6,20 L18,20 C19.1045695,20 20,19.1045695 20,18 L20,13 C20,12.4477153 20.4477153,12 21,12 C21.5522847,12 22,12.4477153 22,13 L22,18 C22,20.209139 20.209139,22 18,22 L6,22 C3.790861,22 2,20.209139 2,18 L2,6 C2,3.790861 3.790861,2 6,2 L12.9,2 Z" fill="#000000" opacity="0.3"/></g></svg></span>
                                        <span class="menu-text">Status Master</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Tim Teknisi -->
                            <?php if (canAccessMenu('tim_teknisi', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'tim_teknisi') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/tim_teknisi/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><polygon points="0 0 24 0 24 24 0 24"/><path d="M18,14 C16.3431458,14 15,12.6568542 15,11 C15,9.34314575 16.3431458,8 18,8 C19.6568542,8 21,9.34314575 21,11 C21,12.6568542 19.6568542,14 18,14 Z M9,11 C6.790861,11 5,9.209139 5,7 C5,4.790861 6.790861,3 9,3 C11.209139,3 13,4.790861 13,7 C13,9.209139 11.209139,11 9,11 Z" fill="#000000" opacity="0.3"/><path d="M17.6011961,15.0006174 C21.0077043,15.0378534 23.7891749,16.7601418 23.9984937,20.4 C24.0069246,20.5466056 23.9984937,21 23.4559499,21 L19.6,21 C19.6,18.7490654 18.8562935,16.6718327 17.6011961,15.0006174 Z M0.00065168429,20.1992055 C0.388258525,15.4265159 4.26191235,13 8.98334134,13 C13.7712164,13 17.7048837,15.2931929 17.9979143,20.2 C18.0095879,20.3954741 17.9979143,21 17.2466999,21 C13.541124,21 8.03472472,21 0.727502227,21 C0.476712155,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#000000"/></g></svg></span>
                                        <span class="menu-text">Tim Teknisi</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Kinerja Tim (SAW) -->
                            <?php if (canAccessMenu('team_saw', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'team_saw') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/tim_teknisi/saw.php" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><path d="M8,3 L8,21 C8,21.5522847 7.55228475,22 7,22 C6.44771525,22 6,21.5522847 6,21 L6,3 C6,2.44771525 6.44771525,2 7,2 C7.55228475,2 8,2.44771525 8,3 Z M18,3 L18,21 C18,21.5522847 17.5522847,22 17,22 C16.4477153,22 16,21.5522847 16,3 Z" fill="#000000"/><rect fill="#000000" opacity="0.3" x="10" y="6" width="4" height="12" rx="2"/></g></svg></span>
                                        <span class="menu-text">Kinerja Tim (SAW)</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- User -->
                            <?php if (canAccessMenu('user', $role, $jabatan)): ?>
                                <li class="menu-item <?= ($current_menu == 'user') ? 'menu-item-active' : '' ?>" aria-haspopup="true">
                                    <a href="<?= BASE_URL ?>pages/user/" class="menu-link">
                                        <span class="svg-icon menu-icon svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><polygon points="0 0 24 0 24 24 0 24"/><path d="M12,11 C9.790861,11 8,9.209139 8,7 C8,4.790861 9.790861,3 12,3 C14.209139,3 16,4.790861 16,7 C16,9.209139 11.209139,11 12,11 Z" fill="#000000" opacity="0.3"/><path d="M3.00065168,20.1992055 C3.38825852,15.4265159 7.26191235,13 11.9833413,13 C16.7712164,13 20.7048837,15.2931929 20.9979143,20.2 C21.0095879,20.3954741 20.9979143,21 20.2466999,21 C16.541124,21 11.0347247,21 3.72750223,21 C3.47671215,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#000000"/></g></svg></span>
                                        <span class="menu-text">User</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Sign Out / Logout -->
                            <li class="menu-item mt-6" aria-haspopup="true" style="border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 8px;">
                                <a href="javascript:;" onclick="typeof logoutConfirm === 'function' ? logoutConfirm() : window.location.href='<?= BASE_URL ?>includes/signout.php'" class="menu-link">
                                    <span class="svg-icon menu-icon svg-icon-2x">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <rect x="0" y="0" width="24" height="24"/>
                                                <path d="M11.1223185,15.9598583 C10.4856627,16.3135544 9.67421448,16.0884872 9.32051837,15.4518315 C8.96682226,14.8151757 9.19188947,14.0037275 9.82854522,13.6500314 L14,11.3323081 L14,7.5 C14,6.67157288 14.6715729,6 15.5,6 C16.3284271,6 17,6.67157288 17,7.5 L17,12.5 C17,13.0450532 16.7029584,13.5471465 16.223185,13.8136814 L11.1223185,15.9598583 Z" fill="#F64E60" opacity="0.3"/>
                                                <path d="M12,2 C6.4771525,2 2,6.4771525 2,12 C2,17.5228475 6.4771525,22 12,22 C17.5228475,22 22,17.5228475 22,12 C22,6.4771525 17.5228475,2 12,2 Z M12,20 C7.581722,20 4,16.418278 4,12 C4,7.581722 7.581722,4 12,4 C16.418278,4 20,7.581722 20,12 C20,16.418278 16.418278,20 12,20 Z" fill="#F64E60"/>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="menu-text font-weight-bolder" style="color: #F64E60 !important;">Sign Out</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </div>
                <!--end::Aside Menu-->
            </div>
            <!--end::Aside-->
