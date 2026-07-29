<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'profile';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $pdo->beginTransaction();

        // Ambil data user sekarang
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmtUser->execute([':username' => $_SESSION['username']]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            throw new Exception("User tidak ditemukan.");
        }

        $currentUsername = $userData['username'];
        $userRole = $userData['role'];

        /*
        ==========================================
        UPDATE USERNAME
        ==========================================
        */
        if (!empty($_POST['new_username']) && $_POST['new_username'] !== $currentUsername) {

            $newUsername = trim($_POST['new_username']);

            $check = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $check->execute([':username' => $newUsername]);

            if ($check->fetch()) {
                throw new Exception("Username sudah digunakan.");
            }

            // update users
            $updateUser = $pdo->prepare("UPDATE users SET username = :new WHERE username = :old");
            $updateUser->execute([
                ':new' => $newUsername,
                ':old' => $currentUsername
            ]);

            // update admin / technician
            $targetTable = ($userRole === 'admin') ? 'admin' : 'technician';
            $updateRole = $pdo->prepare("UPDATE $targetTable SET username = :new WHERE username = :old");
            $updateRole->execute([
                ':new' => $newUsername,
                ':old' => $currentUsername
            ]);

            $_SESSION['username'] = $newUsername;
            $currentUsername = $newUsername;
        }

        /*
        ==========================================
        UPDATE PASSWORD
        ==========================================
        */
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {

            if (!password_verify($_POST['current_password'], $userData['password'])) {
                throw new Exception("Password lama salah.");
            }

            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("Konfirmasi password tidak cocok.");
            }

            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            $updatePass = $pdo->prepare("UPDATE users SET password = :password WHERE username = :username");
            $updatePass->execute([
                ':password' => $hashed,
                ':username' => $currentUsername
            ]);
        }

        /*
        ==========================================
        UPDATE AVATAR (TETAP ADA)
        ==========================================
        */
        if (isset($_FILES['profile_avatar']) && $_FILES['profile_avatar']['error'] === UPLOAD_ERR_OK) {

            $uploadDir = dirname(__DIR__, 2) . '/assets/media/users/';
            $fileTmpPath = $_FILES['profile_avatar']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['profile_avatar']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExt, $allowedExts)) {

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = uniqid() . '.' . $fileExt;
                $destPath = $uploadDir . $fileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {

                    $updateAvatar = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE username = :username");
                    $updateAvatar->execute([
                        ':avatar' => $fileName,
                        ':username' => $currentUsername
                    ]);
                }
            }
        }

        /*
        ==========================================
        UPDATE NAME & PHONE
        ==========================================
        */
        if (!empty($_POST['name']) && !empty($_POST['phone'])) {

            $targetTable = ($userRole === 'admin') ? 'admin' : 'technician';

            $updateInfo = $pdo->prepare("UPDATE $targetTable SET name = :name, phone = :phone WHERE username = :username");
            $updateInfo->execute([
                ':name' => $_POST['name'],
                ':phone' => $_POST['phone'],
                ':username' => $currentUsername
            ]);
        }

        $pdo->commit();

        echo "<script>
            alert('Data berhasil diperbarui!');
            window.location.href='" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit;
    } catch (Exception $e) {

        $pdo->rollBack();

        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

/*
====================================================
AMBIL DATA USER
====================================================
*/
$sql = "SELECT 
            u.username,
            u.role,
            u.avatar,
            u.password,
            COALESCE(t.name, a.name) AS name,
            COALESCE(t.phone, a.phone) AS phone
        FROM users u
        LEFT JOIN technician t ON u.username = t.username
        LEFT JOIN admin a ON u.username = a.username
        WHERE u.username = :username";

$stmt = $pdo->prepare($sql);
$stmt->execute([":username" => $_SESSION['username']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$avatarPath = !empty($row['avatar']) ? "assets/media/users/" . htmlspecialchars($row['avatar']) : "assets/media/users/blank.png";
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="subheader py-2 py-lg-6 subheader-solid" id="kt_subheader">
        <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
            <div class="d-flex align-items-center flex-wrap mr-1">
                <div class="d-flex align-items-baseline flex-wrap mr-5">
                    <h5 class="text-dark font-weight-bold my-1 mr-5">User Profile</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-column-fluid">
        <div class="container">
            <div class="d-flex flex-row">
                <div class="flex-row-auto offcanvas-mobile w-250px w-xxl-350px" id="kt_profile_aside">
                    <div class="card card-custom">
                        <div class="card-body pt-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-60 symbol-xxl-100 mr-5 align-self-start align-self-xxl-center">
                                    <div class="symbol-label" style="background-image:url('<?= BASE_URL ?>assets/media/users/<?= $row['avatar'] ?>')"></div>
                                    <i class="symbol-badge bg-success"></i>
                                </div>
                                <div>
                                    <a href="#" class="font-weight-bolder font-size-h5 text-dark-75 text-hover-primary">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </a>
                                    <div class="text-muted"><?= htmlspecialchars($row['role']) ?></div>
                                </div>
                            </div>
                            <div class="py-9">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="font-weight-bold mr-2">Username:</span>
                                    <a href="#" class="text-muted text-hover-primary"><?= htmlspecialchars($row['username']) ?></a>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="font-weight-bold mr-2">Phone:</span>
                                    <span class="text-muted"><?= htmlspecialchars($row['phone']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-row-fluid ml-lg-8">
                    <div class="card card-custom card-stretch">
                        <div class="card-header py-3">
                            <div class="card-title align-items-start flex-column">
                                <h3 class="card-label font-weight-bolder text-dark">Personal Information</h3>
                                <span class="text-muted font-weight-bold font-size-sm mt-1">Update your personal information</span>
                            </div>
                        </div>
                        <form class="form" method="POST" action="" enctype="multipart/form-data">

                            <input type="hidden" name="role" value="<?= $row['role'] ?>">

                            <div class="card-body">
                                <div class="row">
                                    <label class="col-xl-3"></label>
                                    <div class="col-lg-9 col-xl-6">
                                        <h5 class="font-weight-bold mb-6">Customer Info</h5>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Avatar</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <div class="image-input image-input-outline" id="kt_profile_avatar">
                                            <div class="image-input-wrapper" id="previewBox"
                                                style="background-image: url('<?= BASE_URL . $avatarPath ?>')">
                                            </div>

                                            <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                                                data-action="change" data-toggle="tooltip" title="Change avatar">
                                                <i class="fa fa-pen icon-sm text-muted"></i>
                                                <input type="file" name="profile_avatar" id="inputGambar" accept=".png, .jpg, .jpeg" onchange="previewFile(this)">
                                                <input type="hidden" name="profile_avatar_remove" />
                                            </label>

                                            <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow"
                                                data-action="remove" title="Remove avatar" onclick="resetFile()">
                                                <i class="ki ki-bold-close icon-xs text-muted"></i>
                                            </span>
                                        </div>
                                        <span class="form-text text-muted">Allowed file types: png, jpg, jpeg.</span>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Name</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <input class="form-control form-control-lg form-control-solid" type="text" name="name" value="<?= $row['name'] ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-xl-3"></label>
                                    <div class="col-lg-9 col-xl-6">
                                        <h5 class="font-weight-bold mt-10 mb-6">Contact Info</h5>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Contact Phone</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <div class="input-group input-group-lg input-group-solid">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="la la-phone"></i></span></div>
                                            <input type="text" class="form-control form-control-lg form-control-solid" name="phone" value="<?= $row['phone'] ?>" placeholder="Phone">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <label class="col-xl-3"></label>
                                    <div class="col-lg-9 col-xl-6">
                                        <h5 class="font-weight-bold mt-10 mb-6">Account Settings</h5>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Username</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <input class="form-control form-control-lg form-control-solid"
                                            type="text"
                                            name="new_username"
                                            value="<?= htmlspecialchars($row['username']) ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Current Password</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <input class="form-control form-control-lg form-control-solid"
                                            type="password"
                                            name="current_password"
                                            placeholder="Password Lama">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">New Password</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <input class="form-control form-control-lg form-control-solid"
                                            type="password"
                                            name="new_password"
                                            placeholder="Password Baru">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-xl-3 col-lg-3 col-form-label">Confirm Password</label>
                                    <div class="col-lg-9 col-xl-6">
                                        <input class="form-control form-control-lg form-control-solid"
                                            type="password"
                                            name="confirm_password"
                                            placeholder="Konfirmasi Password">
                                    </div>
                                </div>
                            </div>


                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-lg-3"></div>
                                    <div class="col-lg-6">
                                        <button type="submit" class="btn btn-success mr-2">Save Changes</button>
                                        <button type="reset" class="btn btn-secondary">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const input = document.getElementById('inputGambar');
        if (!input) return;

        input.addEventListener('change', async function(e) {

            const file = e.target.files[0];
            if (!file) return;

            // Skip kalau file kecil
            if (file.size < 300 * 1024) return;

            const compressedBlob = await compressImage(file, 0.6);

            const newFile = new File([compressedBlob], file.name.replace(/\.\w+$/, '.jpg'), {
                type: 'image/jpeg',
                lastModified: Date.now()
            });

            const dt = new DataTransfer();
            dt.items.add(newFile);
            input.files = dt.files;

            console.log("Original:", (file.size / 1024).toFixed(1) + "KB");
            console.log("Compressed:", (newFile.size / 1024).toFixed(1) + "KB");
        });

        function compressImage(file, quality) {
            return new Promise((resolve) => {
                const img = new Image();
                const reader = new FileReader();

                reader.onload = function(e) {
                    img.src = e.target.result;
                };

                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const maxWidth = 800;
                    const scale = Math.min(maxWidth / img.width, 1);

                    canvas.width = img.width * scale;
                    canvas.height = img.height * scale;

                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(function(blob) {
                        resolve(blob);
                    }, 'image/jpeg', quality);
                };

                reader.readAsDataURL(file);
            });
        }

    });
</script>