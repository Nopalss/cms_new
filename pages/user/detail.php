<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'user';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['alert'] = [
        'icon' => 'warning',
        'title' => 'Oops!',
        'text' => 'Username tidak valid.',
        'button' => "Kembali",
        'style' => "warning"
    ];
    header("Location: " . BASE_URL . "pages/user/");
    exit;
}

try {
    $sql = "SELECT 
                u.username,
                u.role,
                u.avatar,
                a.jabatan,
                CASE 
                    WHEN u.role = 'admin' THEN a.admin_id
                    WHEN u.role = 'teknisi' THEN t.tech_id
                END AS person_id,
                CASE 
                    WHEN u.role = 'admin' THEN a.name
                    WHEN u.role = 'teknisi' THEN t.name
                END AS name,
                CASE 
                    WHEN u.role = 'admin' THEN a.phone
                    WHEN u.role = 'teknisi' THEN t.phone
                END AS phone
            FROM users u
            LEFT JOIN admin a ON u.username = a.username
            LEFT JOIN technician t ON u.username = t.username
            WHERE u.username = :username";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $id, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'User tidak ditemukan.',
            'button' => "Kembali",
            'style' => "warning"
        ];
        header("Location: " . BASE_URL . "pages/user/");
        exit;
    }

    // Avatar path fallback
    $avatarFile = !empty($row['avatar']) ? $row['avatar'] : 'blank.png';
    $avatarPath = BASE_URL . 'assets/media/users/' . $avatarFile;

    // Display Role / Jabatan
    $displayRole = ($row['role'] === 'teknisi') ? 'Teknisi' : (!empty($row['jabatan']) ? $row['jabatan'] : 'Admin');

    // Phone WhatsApp normalization
    $waPhone = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
    if (strpos($waPhone, '0') === 0) {
        $waPhone = '62' . substr($waPhone, 1);
    }

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Silakan coba lagi nanti. Error: ' . $e->getMessage(),
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    header("Location: " . BASE_URL . "pages/user/");
    exit;
}
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container pt-6 pb-8">
        
        <!-- Header Banner & Action Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-6 gap-3">
            <div>
                <h3 class="text-dark font-weight-bolder mb-1">
                    <i class="flaticon2-user text-info mr-2 font-size-h2"></i> Detail Profil User
                </h3>
                <div class="text-muted font-size-sm">Informasi akun pengguna dan otoritas akses di sistem jTracks</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>pages/user/update.php?id=<?= urlencode($row['username']) ?>" class="btn btn-warning font-weight-bolder px-5 py-3 mr-2">
                    <i class="la la-edit"></i> Edit User
                </a>
                <a href="<?= BASE_URL ?>pages/user/" class="btn btn-light-primary font-weight-bolder px-5 py-3">
                    <i class="la la-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                
                <!-- Main Profile Header Card -->
                <div class="card card-custom shadow-sm border-0 rounded-lg overflow-hidden bg-white mb-6">
                    <div class="card-body p-6 p-lg-8">
                        <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start text-center text-sm-left">
                            
                            <!-- Foto Avatar User -->
                            <div class="symbol symbol-100 symbol-circle symbol-xl-120 mr-sm-8 mb-4 mb-sm-0 border border-3 border-white shadow-sm overflow-hidden" style="flex-shrink: 0;">
                                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar User" style="object-fit: cover; width: 120px; height: 120px;" onerror="this.src='<?= BASE_URL ?>assets/media/users/blank.png'">
                            </div>

                            <!-- User Info Ringkas -->
                            <div class="flex-grow-1">
                                <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-center justify-content-between mb-2">
                                    <h2 class="text-dark font-weight-boldest mb-1 mb-sm-0">
                                        <?= htmlspecialchars($row['name'] ?? $row['username']) ?>
                                    </h2>
                                    <span class="label label-light-success label-inline font-weight-bold py-2 px-3">
                                        <i class="fa fa-circle font-size-xs text-success mr-1"></i> Akun Aktif
                                    </span>
                                </div>

                                <div class="text-primary font-weight-bolder font-size-h6 mb-3">
                                    @<?= htmlspecialchars($row['username']) ?>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2 justify-content-center justify-content-sm-start">
                                    <span class="label label-light-primary label-inline font-weight-bold py-2 px-4 font-size-sm mr-2">
                                        <i class="flaticon2-shield text-primary mr-1"></i> Role: <?= htmlspecialchars(strtoupper($row['role'])) ?>
                                    </span>
                                    <span class="label label-light-info label-inline font-weight-bold py-2 px-4 font-size-sm">
                                        <i class="flaticon2-layers text-info mr-1"></i> Jabatan: <?= htmlspecialchars($displayRole) ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Detail Information Card -->
                <div class="card card-custom shadow-sm border-0 rounded-lg overflow-hidden bg-white">
                    <div class="card-header border-bottom bg-white py-4 px-6">
                        <h4 class="card-title font-weight-bolder text-dark m-0">
                            <i class="flaticon2-document text-primary mr-2"></i> Informasi Detail Pengguna
                        </h4>
                    </div>

                    <div class="card-body p-6 p-lg-8">
                        <div class="table-responsive">
                            <table class="table table-head-custom table-vertical-center table-borderless m-0">
                                <tbody>
                                    <tr class="border-bottom">
                                        <td class="font-weight-bold text-muted w-200px py-4">
                                            <i class="flaticon2-badge text-muted mr-2"></i> No Induk Pekerja (NIP)
                                        </td>
                                        <td class="font-weight-bolder text-dark py-4 font-size-h6">
                                            <?= !empty($row['person_id']) ? htmlspecialchars($row['person_id']) : '<span class="text-muted font-weight-normal">-</span>' ?>
                                        </td>
                                    </tr>

                                    <tr class="border-bottom">
                                        <td class="font-weight-bold text-muted py-4">
                                            <i class="flaticon2-user text-muted mr-2"></i> Nama Lengkap
                                        </td>
                                        <td class="font-weight-bolder text-dark py-4 font-size-h6">
                                            <?= !empty($row['name']) ? htmlspecialchars($row['name']) : htmlspecialchars($row['username']) ?>
                                        </td>
                                    </tr>

                                    <tr class="border-bottom">
                                        <td class="font-weight-bold text-muted py-4">
                                            <i class="flaticon2-phone text-muted mr-2"></i> No Telepon / WhatsApp
                                        </td>
                                        <td class="py-4">
                                            <?php if (!empty($row['phone'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="font-weight-bolder text-dark font-size-h6 mr-3">
                                                        <?= htmlspecialchars($row['phone']) ?>
                                                    </span>
                                                    <a href="https://wa.me/<?= $waPhone ?>" target="_blank" class="btn btn-sm btn-light-success font-weight-bold px-3 py-1">
                                                        <i class="la la-whatsapp"></i> Chat WhatsApp
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <tr class="border-bottom">
                                        <td class="font-weight-bold text-muted py-4">
                                            <i class="flaticon2-user-outline-symbol text-muted mr-2"></i> Username Sistem
                                        </td>
                                        <td class="font-weight-bolder text-primary py-4 font-size-h6">
                                            @<?= htmlspecialchars($row['username']) ?>
                                        </td>
                                    </tr>

                                    <tr class="border-bottom">
                                        <td class="font-weight-bold text-muted py-4">
                                            <i class="flaticon2-shield text-muted mr-2"></i> Sistem Role (Database)
                                        </td>
                                        <td class="py-4">
                                            <span class="label label-light-primary label-inline font-weight-bold py-2 px-3">
                                                <?= htmlspecialchars($row['role']) ?>
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold text-muted py-4">
                                            <i class="flaticon2-layers text-muted mr-2"></i> Jabatan / Posisi
                                        </td>
                                        <td class="py-4">
                                            <span class="label label-light-info label-inline font-weight-bold py-2 px-3">
                                                <?= htmlspecialchars($displayRole) ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>