<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'packages';
$_SESSION['table'] = 'paket_internet';

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    
    <div class="container-fluid pt-5 pb-5">
        
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-5 gap-3">
            <div>
                <h3 class="text-dark font-weight-bolder mb-1">📶 Master Data Paket Internet</h3>
                <div class="text-muted font-size-sm">Kelola daftar paket internet, kecepatan (Mbps), dan harga berlangganan jTracks</div>
            </div>
            <div>
                <button type="button" class="btn btn-primary font-weight-bold" id="btnOpenAddPackage">
                    <i class="flaticon2-plus"></i> + Tambah Paket Baru
                </button>
            </div>
        </div>

        <div class="card card-custom shadow-sm">
            <div class="card-header border-0 py-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label font-weight-bolder text-dark">Daftar Paket Internet</span>
                    <span class="text-muted mt-1 font-weight-bold font-size-sm" id="packageSubInfo">Memuat data paket...</span>
                </h3>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive">
                    <table class="table table-head-custom table-vertical-center" id="packageTable">
                        <thead>
                            <tr class="text-uppercase text-muted">
                                <th style="min-width: 60px">ID</th>
                                <th style="min-width: 240px">Nama Paket</th>
                                <th style="min-width: 140px">Kecepatan</th>
                                <th style="min-width: 160px">Harga / Bulan</th>
                                <th class="text-right" style="min-width: 120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="packageTbody">
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                    Memuat data paket internet...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Add / Edit Package -->
<div class="modal fade" id="modalPackage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bolder" id="modalPackageTitle">Tambah Paket Internet</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="formPackage">
                <input type="hidden" name="paket_id" id="p_id">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nama Paket <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="p_name" placeholder="Contoh: 10 Mbps UNLIMITED (1-4 Gadget)" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Kecepatan (Mbps) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="paket" id="p_paket" placeholder="Contoh: 10 / 20 / 30" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Harga per Bulan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="harga" id="p_harga" placeholder="Contoh: 250000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold" id="btnSavePackage">Simpan Paket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = {
        list: '<?= BASE_URL ?>api/packages.php',
        save: '<?= BASE_URL ?>pages/packages/save_package.php',
        delete: '<?= BASE_URL ?>pages/packages/delete_package.php'
    };

    loadPackages();

    document.getElementById('btnOpenAddPackage').addEventListener('click', function() {
        document.getElementById('formPackage').reset();
        document.getElementById('p_id').value = '';
        document.getElementById('modalPackageTitle').textContent = 'Tambah Paket Internet Baru';
        $('#modalPackage').modal('show');
    });

    async function loadPackages() {
        const tbody = document.getElementById('packageTbody');
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary mr-2"></div>Memuat data...</td></tr>`;

        try {
            const res = await fetch(API.list);
            const result = await res.json();

            if (!result.status) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-5">Gagal memuat data: ${escapeHtml(result.message)}</td></tr>`;
                return;
            }

            const items = result.data || [];
            document.getElementById('packageSubInfo').textContent = `Total ${items.length} paket internet terdaftar`;

            if (!items.length) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted font-weight-bold">Belum ada paket internet.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(item => {
                html += `
                    <tr>
                        <td><strong>${item.paket_id}</strong></td>
                        <td><strong class="text-dark font-weight-bolder">${escapeHtml(item.name)}</strong></td>
                        <td><span class="badge badge-primary px-3 py-1 font-weight-bold">${escapeHtml(item.paket)} Mbps</span></td>
                        <td><strong class="text-success">Rp ${parseInt(item.harga).toLocaleString('id-ID')}</strong> <small class="text-muted">/bln</small></td>
                        <td class="text-right">
                            <button type="button" class="btn btn-xs btn-light-warning font-weight-bold mr-1 btn-edit" data-id="${item.paket_id}" data-name="${escapeHtml(item.name)}" data-paket="${escapeHtml(item.paket)}" data-harga="${item.harga}">✏️ Edit</button>
                            <button type="button" class="btn btn-xs btn-light-danger font-weight-bold btn-delete" data-id="${item.paket_id}" data-name="${escapeHtml(item.name)}">🗑️ Hapus</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            tbody.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('p_id').value = btn.dataset.id;
                    document.getElementById('p_name').value = btn.dataset.name;
                    document.getElementById('p_paket').value = btn.dataset.paket;
                    document.getElementById('p_harga').value = btn.dataset.harga;
                    document.getElementById('modalPackageTitle').textContent = 'Edit Paket Internet';
                    $('#modalPackage').modal('show');
                });
            });

            tbody.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = btn.dataset.id;
                    const name = btn.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Hapus Paket?',
                            text: `Apakah Anda yakin ingin menghapus paket "${name}"?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal'
                        }).then(async (result) => {
                            if (result.isConfirmed) {
                                deletePackage(id);
                            }
                        });
                    } else if (confirm(`Hapus paket "${name}"?`)) {
                        deletePackage(id);
                    }
                });
            });

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-5">Gagal menghubungi server: ${escapeHtml(err.message)}</td></tr>`;
        }
    }

    async function deletePackage(id) {
        try {
            const formData = new FormData();
            formData.append('paket_id', id);
            const res = await fetch(API.delete, { method: 'POST', body: formData });
            const result = await res.json();
            if (result.status) {
                if (typeof Swal !== 'undefined') Swal.fire('Berhasil', result.message, 'success');
                loadPackages();
            } else {
                if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
            }
        } catch (e) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', e.message, 'error');
        }
    }

    document.getElementById('formPackage').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSavePackage');
        btn.disabled = true;

        try {
            const formData = new FormData(this);
            const res = await fetch(API.save, { method: 'POST', body: formData });
            const result = await res.json();

            if (!result.status) {
                if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                return;
            }

            $('#modalPackage').modal('hide');
            if (typeof Swal !== 'undefined') Swal.fire('Berhasil!', result.message, 'success');
            loadPackages();

        } catch (err) {
            if (typeof Swal !== 'undefined') Swal.fire('Error', 'Gagal menyimpan: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
