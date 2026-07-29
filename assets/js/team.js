$(document).ready(function () {

    // =====================
    // ADD TEAM
    // =====================
    $('#addTeamBtn').on('click', function () {

        Swal.fire({
            title: 'Tambah Team',
            html: `<input id="name" class="swal2-input" placeholder="Nama Team">`,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const name = $('#name').val();
                if (!name) {
                    Swal.showValidationMessage('Nama team wajib diisi');
                    return false;
                }
                return { name };
            }
        }).then(result => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/team/add.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value)
                })
                    .then(r => r.json())
                    .then(() => location.reload());

            }

        });

    });

    // =====================
    // EDIT TEAM
    // =====================
    $(document).on('click', '.editTeamBtn', function () {

        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Edit Team',
            html: `<input id="name" class="swal2-input" value="${name}">`,
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const newName = $('#name').val();
                if (!newName) {
                    Swal.showValidationMessage('Nama team wajib diisi');
                    return false;
                }

                return {
                    id: id,
                    name: newName
                };
            }

        }).then(result => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/team/edit.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value)
                })
                    .then(r => r.json())
                    .then(() => location.reload());

            }

        });

    });

    // =====================
    // DELETE TEAM
    // =====================
    $(document).on('click', '.deleteTeamBtn', function () {

        const id = $(this).data('id');

        Swal.fire({
            title: 'Hapus team ini?',
            text: 'Semua member akan dilepas dari team.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(result => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/team/delete.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                    .then(r => r.json())
                    .then(() => location.reload());

            }

        });

    });

    // =====================
    // ASSIGN TEKNISI KE TEAM
    // =====================
    $(document).on('click', '.assignBtn', function () {

        const tim = $(this).data('timid');

        let html = TECHS.map(t => `
            <label class="d-block mb-2">
                <input type="checkbox" value="${t.tech_id}">
                ${t.name}
            </label>
        `).join('');

        Swal.fire({
            title: 'Assign Teknisi',
            html: html,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                let tech = [];
                $('input:checked').each(function () {
                    tech.push(this.value);
                });

                return { tim, tech };
            }

        }).then(result => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/team/assign.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value)
                })
                    .then(r => r.json())
                    .then(() => location.reload());

            }

        });

    });

    // =====================
    // REMOVE MEMBER DARI TEAM
    // =====================
    $(document).on('click', '.removeMemberBtn', function () {

        const tech_id = $(this).data('tech');

        Swal.fire({
            title: 'Keluarkan teknisi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, keluarkan',
            cancelButtonText: 'Batal'
        }).then(result => {

            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${HOST_URL}controllers/team/remove_member.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tech_id })
                })
                    .then(r => r.json())
                    .then(() => location.reload());

            }

        });

    });

});
