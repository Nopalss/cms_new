<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
require_once __DIR__ . '/../../helper/checkRowExist.php';
$_SESSION['menu'] = 'ikr';

try {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    if (!$id) {
        $_SESSION['alert'] = [
            'icon' => 'warning',
            'title' => 'Oops!',
            'text' => 'Schedule ID tidak valid.',
            'button' => 'Oke',
            'style' => 'warning'
        ];
        redirect("pages/ikr/");
    }
    $sql = "SELECT s.schedule_id, c.* FROM schedules s 
                JOIN queue_scheduling q ON s.queue_id = q.queue_id
                JOIN customers c ON q.netpay_id = c.netpay_id
            WHERE s.schedule_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    checkRowExist($customer, "pages/ikr/");
    $ikr_id = "SI" . date("YmdHis");
    $paketInternet = [
        "5"   => "5 mbps - 150rb/bln",
        "10"  => "10 mbps - 300rb/bln",
        "30"  => "30 mbps - 650rb/bln",
        "50"  => "50 mbps - 850rb/bln",
        "100" => "100 mbps - 1jt/bln"
    ];
    $sql = "SELECT
                UPPER(TRIM(kab)) AS kabupaten
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(kab) <> ''
            GROUP BY UPPER(TRIM(kab))
            HAVING COUNT(*) > 100
            ORDER BY kabupaten ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kab = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sql = "SELECT
                UPPER(TRIM(kec)) AS kecamatan
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(kec) <> ''
            GROUP BY UPPER(TRIM(kec))
            HAVING COUNT(*) > 5
            ORDER BY kecamatan ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $kec = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sql = "SELECT
                UPPER(TRIM(desa)) AS desa
            FROM ikr_report
            WHERE kab IS NOT NULL
            AND TRIM(desa) <> ''
            GROUP BY UPPER(TRIM(desa))
            HAVING COUNT(*) > 40
            ORDER BY desa ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $desa = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'icon' => 'error',
        'title' => 'Oops! Ada yang Salah',
        'text' => 'Gagal mendapatkan data, silakan coba lagi',
        'button' => "Coba Lagi",
        'style' => "danger"
    ];
    redirect("pages/ikr/");
}
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>
<div class="content  d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Entry-->
    <div class=" d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class="container">
            <form method="post" class="form row" action="<?= BASE_URL ?>controllers/report/ikr/create.php">
                <!-- card create request IKR -->
                <div class="col-md-6 mb-10">
                    <div class="card card-custom shadow-sm">
                        <div class="card-header pt-5">
                            <div class="card-title">
                                <h3 class="card-label">
                                    Create IKR Report
                                </h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="text-right">Report IKR ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= $ikr_id ?>" disabled="disabled" />
                                    <input type="hidden" class="form-control" name="ikr_id" value="<?= $ikr_id ?>" />
                                    <input type="hidden" class="form-control" name="schedule_key" value="<?= $id ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Netpay ID</label>
                                <input type="text" class="form-control" value="<?= $customer['netpay_id'] ?>" disabled="disabled" />
                                <input type="hidden" class="form-control" name="netpay_id" value="<?= $customer['netpay_id'] ?>" />
                                <input type="hidden" class="form-control" name="netpay_key" value="<?= $customer['netpay_key'] ?>" />
                            </div>
                            <!-- Group IKR -->
                            <div class="form-group">
                                <label for="group_ikr">Group IKR</label>
                                <input type="text" class="form-control" id="group_ikr" name="group_ikr" required>
                            </div>

                            <!-- IKR AN -->
                            <div class="form-group">
                                <label for="ikr_an">Nama</label>
                                <input type="text" class="form-control" id="ikr_an" name="ikr_an" value="<?= $customer['name'] ?>" required>
                            </div>

                            <!-- Alamat -->
                            <div class="form-group">
                                <label for="alamat">Alamat</label>
                                <input type="text" class="form-control" id="alamat" name="alamat" value="<?= $customer['location'] ?>" required>
                            </div>

                            <!-- RT & RW dalam row -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="rt">RT</label>
                                    <input type="text" class="form-control" id="rt" name="rt" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="rw">RW</label>
                                    <input type="text" class="form-control" id="rw" name="rw" required>
                                </div>
                            </div>

                            <!-- Desa, Kecamatan, Kabupaten -->
                            <div class="form-group">
                                <label class="font-weight-bold text-dark-50 font-size-sm">Desa</label>
                                <select class="form-control selectpicker" id="desa" name="desa" data-size="7" data-live-search="true">
                                    <option value="">— Pilih desa —</option>
                                    <option value="LAINNYA">Lainnya</option>
                                    <?php foreach ($desa as $p): ?>
                                        <option value="<?= $p['desa'] ?>"><?= $p['desa'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-group mt-3" id="desa_lainnya_group" style="display:none;">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">
                                        Desa
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="desa_lainnya"
                                        placeholder="Masukkan nama desa">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold text-dark-50 font-size-sm">Kecamatan</label>
                                <select class="form-control selectpicker" id="kec" name="kec" data-size="7" data-live-search="true">
                                    <option value="">— Pilih Kecamatan —</option>
                                    <option value="LAINNYA">Lainnya</option>
                                    <?php foreach ($kec as $p): ?>
                                        <option value="<?= $p['kec'] ?>"><?= $p['kec'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-group mt-3" id="kec_lainnya_group" style="display:none;">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">
                                        Kecamatan
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="kec_lainnya"
                                        placeholder="Masukkan nama kecamatan">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold text-dark-50 font-size-sm">Kabupaten</label>
                                <select class="form-control selectpicker" id="kab" name="kab" data-size="7" data-live-search="true">
                                    <option value="">— Pilih Kabupaten —</option>
                                    <option value="LAINNYA">Lainnya</option>
                                    <?php foreach ($kab as $p): ?>
                                        <option value="<?= $p['kab'] ?>"><?= $p['kab'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-group mt-3" id="kab_lainnya_group" style="display:none;">
                                    <label class="font-weight-bold text-dark-50 font-size-sm">
                                        Kabupaten
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="kab_lainnya"
                                        placeholder="Masukkan nama Kabupaten">
                                </div>
                            </div>


                            <!-- Telepon -->
                            <div class="form-group">
                                <label for="telp">Telepon</label>
                                <input type="text" class="form-control" id="telp" name="telp" pattern="^08[0-9]{8,11}$" value="<?= $customer['phone'] ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="type_ont">Type ONT</label>
                                <input type="text" class="form-control" id="type_ont" name="type_ont" required>
                            </div>
                            <!-- S/N -->
                            <div class="form-group">
                                <label for="sn">Nomer Seri</label>
                                <input type="text" class="form-control" id="sn" name="sn" required>
                            </div>

                            <!-- Paket & Type ONT -->
                            <div class="form-group">
                                <label for="paket_internet">Paket</label>
                                <select class="form-control selectpicker" id="paket_internet" required name="paket" data-size=" 7">
                                    <option value="">Select</option>
                                    <?php foreach ($paketInternet as $key => $value): ?>
                                        <?php $selected = ($key == $customer['paket_internet']) ? 'selected' : ''; ?>
                                        <option value='<?= $key ?>' <?= $selected ?>><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Redaman -->
                            <div class="form-group">
                                <label for="redaman">Redaman</label>
                                <input type="text" class="form-control" id="redaman" name="redaman" required>
                            </div>

                            <!-- ODP, ODC, JC No -->
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="odp_no">ODP No</label>
                                    <input type="text" class="form-control" id="odp_no" name="odp_no" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="odc_no">ODC No</label>
                                    <input type="text" class="form-control" id="odc_no" name="odc_no" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="jc_no">JC No</label>
                                    <input type="text" class="form-control" id="jc_no" name="jc_no" required>
                                </div>
                            </div>

                            <!-- MAC sebelum & sesudah -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="mac_sebelum">MAC Sebelum</label>
                                    <input type="text" class="form-control" id="mac_sebelum" name="mac_sebelum">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mac_sesudah">MAC Sesudah</label>
                                    <input type="text" class="form-control" id="mac_sesudah" name="mac_sesudah">
                                </div>
                            </div>

                            <!-- ODP, ODC, Enclosure, Paket No -->
                            <div class="form-group">
                                <label for="odp">ODP</label>
                                <input type="text" class="form-control" id="odp" name="odp" required>
                            </div>
                            <div class="form-group">
                                <label for="odc">ODC</label>
                                <input type="text" class="form-control" id="odc" name="odc" required>
                            </div>
                            <div class="form-group">
                                <label for="enclosure">Enclosure</label>
                                <input type="text" class="form-control" id="enclosure" name="enclosure" required>
                            </div>


                        </div>
                        <div class="card-footer text-right">
                            <a href="<?= BASE_URL ?>pages/ikr/" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancel</a>
                            <button type="submit" name="submit" class="btn btn-primary font-weight-bold">Create</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-10">
                    <div class="card card-custom mb-5" data-card="true">
                        <div class="card-header">
                            <div class="card-title">
                                <h3 class="card-label">Data Customer</h3>
                            </div>
                            <div class="card-toolbar">
                                <a href="#" class="btn btn-icon btn-sm btn-hover-light-primary mr-1" data-card-tool="toggle" data-toggle="tooltip" data-placement="top">
                                    <i class="ki ki-arrow-down icon-nm"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Netpay ID</th>
                                        <td id="data-netpay"><?= $customer['netpay_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nama</th>
                                        <td id="data-name"><?= $customer['name'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>No Hp</th>
                                        <td id="data-phone"><?= $customer['phone'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Paket</th>
                                        <td id="data-paket"><?= $customer['paket_internet'] ?> Mbps</td>
                                    </tr>
                                    <tr>
                                        <th>Is Active?</th>
                                        <td id="data-active"><?= $customer['is_active'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Alamat</th>
                                        <td id="data-location" class="text-wrap"><?= $customer['location'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!--end::Container-->
    </div>
    <!--end::Entry-->
</div>
<?php
require __DIR__ . '/../../includes/footer.php';
?>
<script>
    $(function() {

        $('#desa').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#desa_lainnya_group').slideDown();

                $('#desa_lainnya')
                    .attr('name', 'desa')
                    .prop('required', true);

                $('#desa').removeAttr('name');

            } else {

                $('#desa_lainnya_group').slideUp();

                $('#desa_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');

                $('#desa').attr('name', 'desa');
            }

        });
        $('#kec').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#kec_lainnya_group').slideDown();

                $('#kec_lainnya')
                    .attr('name', 'kec')
                    .prop('required', true);

                $('#kec').removeAttr('name');

            } else {

                $('#kec_lainnya_group').slideUp();

                $('#kec_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');

                $('#kec').attr('name', 'kec');
            }

        });
        $('#kab').on('change', function() {

            if ($(this).val() === 'LAINNYA') {

                $('#kab_lainnya_group').slideDown();

                $('#kab_lainnya')
                    .attr('name', 'kab')
                    .prop('required', true);

                $('#kab').removeAttr('name');

            } else {

                $('#kab_lainnya_group').slideUp();

                $('#kab_lainnya')
                    .removeAttr('name')
                    .prop('required', false)
                    .val('');

                $('#kab').attr('name', 'kab');
            }

        });

    });
</script>