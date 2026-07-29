<?php
require_once __DIR__ . '/../../includes/config.php';

$_SESSION['menu'] = 'team';
$_SESSION['halaman'] = 'team';

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$teams = $pdo->query("SELECT * FROM tim ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$techs = $pdo->query("SELECT * FROM technician ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content d-flex flex-column-fluid">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-7">
            <div>
                <h3 class="font-weight-bolder mb-1">Master Team Teknisi</h3>
                <small class="text-muted">Kelola team & anggota teknisi</small>
            </div>

            <button class="btn btn-primary font-weight-bold" id="addTeamBtn">
                + Tambah Team
            </button>
        </div>

        <div class="row">

            <?php foreach ($teams as $t): ?>

                <?php
                $members = $pdo->prepare("SELECT * FROM technician WHERE tim_id=? ORDER BY name ASC");
                $members->execute([$t['tim_id']]);
                $members = $members->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="col-md-4">

                    <div class="card card-custom shadow-sm mb-6">

                        <div class="card-header py-4">
                            <div class="card-title">
                                <h4 class="mb-0"><?= htmlspecialchars($t['nama']) ?></h4>
                            </div>
                        </div>

                        <div class="card-body">

                            <?php if (!$members): ?>
                                <div class="text-muted text-center py-5">
                                    Belum ada teknisi
                                </div>
                            <?php endif; ?>

                            <?php foreach ($members as $m): ?>

                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">

                                    <span class="badge badge-light-primary px-3 py-2">
                                        <?= htmlspecialchars($m['name']) ?>
                                    </span>

                                    <button
                                        class="btn btn-sm btn-light-danger removeMemberBtn"
                                        data-tech="<?= $m['tech_id'] ?>"
                                        title="Keluarkan teknisi">
                                        ✕
                                    </button>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="card-footer text-right">

                            <button class="btn btn-sm btn-light-primary editTeamBtn"
                                data-id="<?= $t['id'] ?>"
                                data-timid="<?= $t['tim_id'] ?>"
                                data-name="<?= htmlspecialchars($t['nama']) ?>"
                                title="Edit Team">
                                ✏
                            </button>

                            <button class="btn btn-sm btn-light-danger deleteTeamBtn"
                                data-id="<?= $t['id'] ?>"
                                title="Hapus Team">
                                🗑
                            </button>

                            <button class="btn btn-sm btn-light-success assignBtn"
                                data-timid="<?= $t['tim_id'] ?>"
                                title="Assign Teknisi">
                                👥
                            </button>

                        </div>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>
</div>

<script>
    const TECHS = <?= json_encode($techs) ?>;
</script>


<?php require __DIR__ . '/../../includes/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/team.js"></script>