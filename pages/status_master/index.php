<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'status_master';
$_SESSION['halaman'] = 'status master';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$stmt = $pdo->query("SELECT * FROM type ORDER BY type, id ASC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [
    'rm' => [],
    'rd' => [],
    'issue' => []
];

foreach ($data as $row) {
    $grouped[$row['type']][] = $row;
}
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center mb-7">
                <div>
                    <h3 class="font-weight-bolder">Status Master Management</h3>
                    <span class="text-muted">Kelola semua catatan berdasarkan kategori</span>
                </div>
                <button class="btn btn-primary font-weight-bolder" id="addTypeBtn">
                    + Tambah Status
                </button>
            </div>

            <div class="row">

                <?php
                $colors = [
                    'rm' => 'primary',
                    'rd' => 'danger',
                    'issue' => 'warning'
                ];
                $type2 = [
                    'rm' => "Request Maintenance",
                    'rd' => "Request Dismantle",
                    'issue' => "Issue Report",
                ];


                foreach ($grouped as $type => $items):
                ?>

                    <div class="col-md-4">
                        <div class="card card-custom gutter-b shadow-sm">
                            <div class="card-header bg-light-<?= $colors[$type] ?>">
                                <h3 class="card-title text-<?= $colors[$type] ?> font-weight-bolder text-uppercase">
                                    <?= strtoupper($type2[$type]) ?>
                                </h3>
                            </div>

                            <div class="card-body p-5">

                                <?php if (count($items) === 0): ?>
                                    <div class="text-muted text-center">
                                        Belum ada data
                                    </div>
                                <?php else: ?>

                                    <?php foreach ($items as $row): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 border rounded bg-light">

                                            <div>
                                                <div class="font-weight-bold">
                                                    <?= htmlspecialchars($row['catatan']) ?>
                                                </div>

                                            </div>

                                            <div class="d-flex">

                                                <button
                                                    class="btn btn-sm btn-icon btn-light-primary mr-2 editTypeBtn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-catatan="<?= htmlspecialchars($row['catatan']) ?>"
                                                    data-type="<?= $row['type'] ?>">
                                                    ✏
                                                </button>

                                                <button
                                                    class="btn btn-sm btn-icon btn-light-danger deleteTypeBtn"
                                                    data-id="<?= $row['id'] ?>">
                                                    🗑
                                                </button>

                                            </div>


                                        </div>
                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>