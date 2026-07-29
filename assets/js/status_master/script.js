$(document).ready(function () {

    // =====================
    // ADD TYPE
    // =====================
    $('#addTypeBtn').on('click', function () {

        Swal.fire({
            title: 'Tambah Catatan',
            html: `
                <input id="swal-catatan" class="swal2-input" placeholder="Masukkan catatan">
                <select id="swal-type" class="swal2-select">
                    <option value="">-- Pilih Type --</option>
                    <option value="rm">RM</option>
                    <option value="rd">RD</option>
                    <option value="issue">ISSUE</option>
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            preConfirm: () => {

                const catatan = document.getElementById('swal-catatan').value;
                const type = document.getElementById('swal-type').value;

                if (!catatan) {
                    Swal.showValidationMessage('Catatan wajib diisi');
                    return false;
                }

                if (!type) {
                    Swal.showValidationMessage('Type wajib dipilih');
                    return false;
                }

                return { catatan, type };
            }
        }).then((result) => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/type/add_type.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    });

            }

        });

    });

    // =====================
    // EDIT TYPE
    // =====================
    $(document).on('click', '.editTypeBtn', function () {

        const id = $(this).data('id');
        const catatan = $(this).data('catatan');
        const type = $(this).data('type');

        Swal.fire({
            title: 'Edit Catatan',
            html: `
                <input id="swal-catatan" class="swal2-input" value="${catatan}">
                <select id="swal-type" class="swal2-select">
                    <option value="rm">RM</option>
                    <option value="rd">RD</option>
                    <option value="issue">ISSUE</option>
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Batal',
            didOpen: () => {
                document.getElementById('swal-type').value = type;
            },
            preConfirm: () => {

                const newCatatan = document.getElementById('swal-catatan').value;
                const newType = document.getElementById('swal-type').value;

                if (!newCatatan) {
                    Swal.showValidationMessage('Catatan wajib diisi');
                    return false;
                }

                return { id, catatan: newCatatan, type: newType };
            }

        }).then((result) => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/type/edit_type.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    });

            }

        });

    });

    // =====================
    // DELETE TYPE
    // =====================
    $(document).on('click', '.deleteTypeBtn', function () {

        const id = $(this).data('id');

        Swal.fire({
            title: 'Yakin mau hapus?',
            text: 'Data ini tidak bisa dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/type/delete_type.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                })
                    .then(r => r.json())
                    .then(data => {

                        if (data.success) {
                            Swal.fire('Terhapus!', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }

                    });

            }

        });

    });


});
