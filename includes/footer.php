<?php

require_once __DIR__ . '/config.php';
?>


<!--begin::Footer-->
<div class="footer bg-white py-4 d-flex flex-lg-column " id="kt_footer">
    <!--begin::Container-->
    <div class=" container-fluid  d-flex flex-column flex-md-row align-items-center justify-content-end">
        <!--begin::Copyright-->
        <div class="text-dark order-2 order-md-1">
            <span class="text-muted font-weight-bold mr-2"><?= date('Y') ?>&copy;</span>
            <a href="" target="_blank" class="text-dark-75 text-hover-primary">Jabbar23</a>
        </div>
        <!--end::Copyright-->

        <!--begin::Nav-->

        <!--end::Nav-->
    </div>
    <!--end::Container-->
</div>
<!--end::Footer-->
</div>
<!--end::Wrapper-->
</div>
<!--end::Page-->
</div>
<!--end::Main-->
<!-- end::Content -->
<!-- begin::User Panel-->
<div id="kt_quick_user" class="offcanvas offcanvas-right p-10">
    <!--begin::Header-->
    <div class="offcanvas-header d-flex align-items-center justify-content-between pb-5">
        <h3 class="font-weight-bold m-0">
            User Profile
        </h3>
        <a href="#" class="btn btn-xs btn-icon btn-light btn-hover-primary" id="kt_quick_user_close">
            <i class="ki ki-close icon-xs text-muted"></i>
        </a>
    </div>
    <!--end::Header-->

    <!--begin::Content-->
    <div class="offcanvas-content pr-5 mr-n5">
        <!--begin::Header-->
        <div class="d-flex align-items-center mt-5">
            <div class="symbol symbol-100 mr-5">
                <?php
                $stmt = $pdo->prepare("SELECT avatar FROM users WHERE username = :username LIMIT 1");
                $stmt->execute([':username' => $_SESSION['username']]);
                $karyawan = $stmt->fetch(PDO::FETCH_ASSOC);

                ?>
                <div class="symbol-label" style="background-image:url('<?= BASE_URL ?>assets/media/users/<?= $karyawan['avatar'] ?>')"></div>
                <i class="symbol-badge bg-success"></i>
            </div>
            <div class="d-flex flex-column">
                <a href="<?= BASE_URL ?>pages/user/profile.php" class="font-weight-bold font-size-h5 text-dark-75 text-hover-primary">
                    <?= $_SESSION['name'] ?>
                </a>
                <div class="text-muted mt-1">
                    <?= $_SESSION['role'] ?>
                </div>
                <div class="navi mt-2">
                    <a onclick="logoutConfirm()" class="btn btn-sm btn-light-primary font-weight-bolder py-2 px-5">Sign Out</a>
                </div>
            </div>
        </div>
        <!--end::Header-->

        <!--begin::Separator-->
        <div class="separator separator-dashed mt-8 mb-5"></div>
        <!--end::Separator-->

        <!-- begin::Nav-->
        <div class="navi navi-spacer-x-0 p-0">
            <!--begin::Item-->

            <!--end:Item -->

            <!--begin::Item-->

            <!--end:Item-->
        </div>
        <!--end::Nav-->
    </div>
    <!--end::Content-->
</div>
<!-- end::User Panel-->
<!-- sweetalert -->
<script>
    var HOST_URL = "<?= BASE_URL ?>";
</script>
<!--begin::Global Theme Bundle(used by all pages)-->
<script src="<?= asset_ver('assets/plugins/global/plugins.bundle.js') ?>"></script>
<script src="<?= asset_ver('assets/js/scripts.bundle.js') ?>"></script>


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


<!--end::Global Theme Bundle-->
<?php if ($_SESSION['menu'] == "registrasi"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/registrasi.js"></script>
    <script src="<?= BASE_URL ?>assets/js/registrasi/index.js"></script>
    <script src="<?= BASE_URL ?>assets/js/pages/features/cards/tools.js"></script>

<?php endif; ?>
<?php if ($_SESSION['menu'] == "request ikr"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/request_ikr.js"></script>
    <script src="<?= BASE_URL ?>assets/js/request/ikr.js"></script>
<?php endif; ?>
<?php if ($_SESSION['menu'] == "status_master"): ?>
    <script src="<?= BASE_URL ?>assets/js/status_master/script.js"></script>
<?php endif; ?>
<?php if (isset($_SESSION['alert'])): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/features/miscellaneous/sweetalert2.js"></script>
    <script>
        Swal.fire({
            icon: "<?= $_SESSION['alert']['icon'] ?>",
            title: "<?= $_SESSION['alert']['title'] ?>",
            text: "<?= $_SESSION['alert']['text'] ?>",
            confirmButtonText: "<?= $_SESSION['alert']['button'] ?>",
            heightAuto: false,
            customClass: {
                confirmButton: "btn font-weight-bold btn-<?= $_SESSION['alert']['style'] ?>",
                icon: "m-auto"
            }
        });
    </script>
    <?php unset($_SESSION['alert']); ?>
<?php endif; ?>
<?php if ($_SESSION['menu'] == "queue"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/queue.js"></script>
    <script>
        // Schedule
        // count scheduleNow
        function scheduleNowCount() {
            $.ajax({
                url: "<?= BASE_URL ?>api/get_scheduleNow_count.php",
                method: "GET",
                dataType: "json",
                success: function(result) {
                    if (result.status === "success") {
                        // update badge total
                        if (result.total > 0) {
                            $("#scheduleNow").text(result.total).show();
                        } else {
                            $("#scheduleNow").hide();
                        }

                        // list kategori yang mau ditampilkan
                        const categories = {
                            data: "#table-scheduleNowAll",
                            install: "#table-scheduleNowInstall",
                            service: "#table-scheduleNowMaintenance",
                            dismantle: "#table-scheduleNowDismantle"
                        };

                        // looping setiap kategori
                        Object.keys(categories).forEach((key) => {
                            const target = $(categories[key]); // definisikan dulu di luar if
                            target.empty();

                            if (result[key] && result[key].length > 0) {
                                result[key].forEach((item) => {
                                    target.append(`
                                    <tr>
                                        <td class="align-middle text-center">${item.queue_id}</td>
                                        <td class="align-middle text-center">${item.type_queue}</td>
                                        <td class="align-middle text-center">${item.request_id}</td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-pill badge-info">${item.status}</span>
                                        </td>
                                        <td class="align-middle text-center">${item.created_at}</td>
                                        <td class="align-middle text-center">
                                            <form action="${HOST_URL}pages/schedule/create.php" method="post">
                                                <span>
                                                    <input type="hidden" name="type_queue" value="${item.type_queue}">
                                                    <button type="submit" name="id" class="btn btn-primary border-0 btn-detail-rikr" value="${item.queue_id}">
                                                        <span class="navi-icon"><i class="flaticon-calendar-with-a-clock-time-tools"></i></span>
                                                        <span class="navi-text">Schedule Now</span>
                                                    </button>
                                                </span>
                                            </form>
                                        </td>
                                    </tr>
                                `);
                                });
                            } else {
                                target.append(`
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <p class="text-muted font-weight-bold mb-0">Tidak ada antrian</p>
                                        </td>
                                    </tr>
                                `);
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                }
            });
        }
        // load pertama kali
        scheduleNowCount();
        setInterval(scheduleNowCount, 10000);
    </script>
<?php endif; ?>

<?php if ($_SESSION['menu'] == "schedule"): ?>
    <?php if (($_SESSION['role'] ?? '') == 'admin'): ?>
        <script src="<?= BASE_URL ?>assets/js/tables/schedules.js"></script>
        <script src="<?= BASE_URL ?>assets/js/pages/features/cards/tools.js"></script>
    <?php endif; ?>
    <script>
        function issueCount() {
            $.ajax({
                url: "<?= BASE_URL ?>api/get_issue_count.php",
                method: "GET",
                dataType: "json",
                success: function(result) {
                    if (result.status === "success") {
                        // update badge total
                        if (result.total > 0) {
                            $("#issueNow").text(result.total).show();
                        } else {
                            $("#issueNow").hide();
                        }
                    }
                }
            })
        }
        issueCount();
        setInterval(issueCount, 10000);
    </script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "ikr"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/ikr.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "request maintenance"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/request_maintenance.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "request dismantle"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/request_dismantle.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "service"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/service.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "dismantle"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/dismantle.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "customer"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/customer.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "user"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/user.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "teknisi"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/teknisi2.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "team_saw"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/team_saw.js"></script>
<?php endif; ?>

<?php if ($_SESSION['table'] == "issue report"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/issues-report.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] == "dashboard"): ?>
    <script src="<?= BASE_URL ?>assets/js/tables/teknisi.js"></script>
    <script src="<?= BASE_URL ?>assets/js/pages/widgets.js"></script>
    <!-- <script src="<?= BASE_URL ?>assets/js/pages/features/charts/apexcharts.js"></script> -->
<?php endif; ?>

<?php if ($_SESSION['table'] != "dashboard"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/crud/forms/widgets/bootstrap-datepicker.js"></script>
<?php endif; ?>
<?php if ($_SESSION['table'] != "profile"): ?>
    <script src="<?= BASE_URL ?>assets/js/pages/custom/profile/profile.js"></script>
<?php endif; ?>
<?php

// unset($_SESSION['table']);
$_SESSION['table'] = "";
?>

<script>
    function logoutConfirm() {
        Swal.fire({
            title: 'Logout?',
            text: 'Anda yakin ingin keluar dari aplikasi?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= BASE_URL . "includes/signout.php" ?>";
            }
        });
    }
    //  delete template
    function confirmDeleteTemplate(id, url, title = "Yakin mau hapus?", text = "Data akan dihapus permanen!") {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonColor: '#d63030',
            cancelButtonColor: 'rgba(0, 158, 202, 0.76)',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = `${HOST_URL}${url}`;

                const inputId = document.createElement("input");
                inputId.type = "hidden";
                inputId.name = "id";
                inputId.value = id;

                form.appendChild(inputId);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function confirmActiveTask(id, url, title = "Yakin mau mengerjakan task ini?", text = "") {
        Swal.fire({
            title: title,
            text: text,
            icon: 'info',
            showCancelButton: true,
            reverseButtons: true,
            cancelButtonColor: '#d33',
            confirmButtonColor: '#30d63b',
            confirmButtonText: 'Lanjut',
            cancelButtonText: 'Batal'
        }).then((result) => {

            if (result.isConfirmed) {

                const form = document.createElement("form");
                form.method = "POST";
                form.action = `${HOST_URL}${url}`;

                const inputId = document.createElement("input");
                inputId.type = "hidden";
                inputId.name = "id";
                inputId.value = id;

                form.appendChild(inputId);

                document.body.appendChild(form);
                form.submit();
            }

        });
    }



    // modal detail rm
    $(document).on("click", ".btn-detail-rm", function() {
        $("#detail_RmId").text($(this).data("rm-id"));
        $("#detail_netpayId").text($(this).data("netpay-id"));
        $("#detail_type").text($(this).data("type"));
        $("#detail_status").text($(this).data("status")).addClass(`font-weight-bold text-${$(this).data("state")}`);
        $("#detail_catatan").text($(this).data("deskripsi"));
        $("#detail_requestBy").text($(this).data("request-by"));

        $("#detailModalRm").modal("show");
    });
    // RD
    // modal detail rd
    $(document).on("click", ".btn-detail-rd", function() {
        $("#detail_RdId").text($(this).data("rd-id"));
        $("#detail_netpayId").text($(this).data("netpay-id"));
        $("#detail_type").text($(this).data("type"));
        $("#detail_status").text($(this).data("status")).addClass(`font-weight-bold text-${$(this).data("state")}`);
        $("#detail_catatan").text($(this).data("deskripsi"));
        $("#detail_requestBy").text($(this).data("request-by"));

        $("#detailModalRd").modal("show");
    });

    // get tanggal 
    $("#date, #tech_id").on("change", getJadwalTeknisi);

    function getJadwalTeknisi() {
        let date = $("#date").val();
        let tech_id = $("#tech_id").val();

        if (date && tech_id) {
            $.ajax({
                url: "<?= BASE_URL ?>api/get_jadwal_teknisi.php",
                method: "POST",
                data: {
                    date: date,
                    tech_id: tech_id
                },
                dataType: "json",
                success: function(res) {
                    // kosongkan time & timeline setiap kali request
                    $("#time").empty().append('<option value="">-- pilih Jam --</option>');
                    $("#timeline").empty();
                    let jam = <?= isset($row['time']) ? json_encode(substr($row['time'], 0, 5)) : 'null' ?>;
                    let jamPemasangan = <?= isset($jamPemasangan) ? json_encode($jamPemasangan) : 'null' ?>
                    // === isi select jam ===
                    if (res.jamKosong && res.jamKosong.length > 0) {
                        if (jam) {
                            $("#time").append(`<option value = "${jam}" selected>${jam}</option>`);
                            res.jamKosong.forEach(function(time) {
                                $("#time").append(`<option value="${time}">${time}</option>`);
                            });
                        } else {
                            let jamKosong = res.jamKosong;
                            if (jamPemasangan && !jamKosong.includes(jamPemasangan)) {
                                $("#time").append(`<option selected disabled data-content="<span class='text-danger font-weight-bold'>${jamPemasangan} <small class='text-danger font-weight-bold'>Teknisi Sudah Ada Jadwal</small></span>">${jamPemasangan}</option>`);
                                res.jamKosong.forEach(function(time) {
                                    $("#time").append(`<option value="${time}">${time}</option>`);
                                });
                                $('#time').selectpicker('refresh');
                            } else if (jamPemasangan && jamKosong.includes(jamPemasangan)) {
                                res.jamKosong.forEach(function(time) {
                                    let selected = time == jamPemasangan ? 'selected' : '';
                                    $("#time").append(`<option value="${time}" ${selected} >${time}</option>`);
                                });
                            } else {
                                res.jamKosong.forEach(function(time) {
                                    $("#time").append(`<option value="${time}">${time}</option>`);
                                });

                            }
                        }
                    } else {
                        $("#time").append('<option value="">Tidak ada jam kosong</option>');
                    }

                    // === isi timeline ===
                    if (res.jadwal && res.jadwal.length > 0) {
                        const badgeClasses = {
                            'Instalasi': 'success',
                            'Service': 'warning',
                            'Dismantle': 'danger'
                        };
                        const statusClasses = {
                            'Pending': "info",
                            'On Progress': "primary",
                            'Rescheduled': "warning",
                            'Cancelled': "danger",
                            'Done': "success"
                        };

                        res.jadwal.forEach((jadwal) => {
                            const badgeClass = badgeClasses[jadwal['job_type']] || "secondary";
                            const statusClass = statusClasses[jadwal['status']] || "dark";

                            $("#timeline").append(`
                            <div class="timeline-item align-items-start">
                                <div class="timeline-label font-weight-bolder text-dark-75 font-size-lg">
                                    ${jadwal.time.substr(0, 5)}
                                </div>
                                <!--begin::Badge-->
                                <div class="timeline-badge">
                                    <i class="fa fa-genderless text-${badgeClass} icon-xl"></i>
                                </div>
                                <!--end::Badge-->
                                <!--begin::Text-->
                                <div class="font-weight-normal font-size-lg timeline-content pl-3">
                                    <p class="mb-0 btn-detail2 cursor-pointer"
                                        data-id="${jadwal['schedule_id']}"
                                        data-tech="${jadwal['tech_id']}"
                                        data-netpay="${jadwal['netpay_id']}"
                                        data-date="${jadwal['date']}"
                                        data-time="${jadwal['time'].substr(0, 5)}"
                                        data-job="${jadwal['job_type']}"
                                        data-state="${statusClass}"
                                        data-status="${jadwal['status']}"
                                        data-location="${jadwal['location']}">
                                        ${jadwal['job_type']}
                                        <a class="text-muted btn-detail font-size-sm cursor-pointer">
                                            #${jadwal['schedule_id']}
                                        </a>
                                    </p>
                                </div>
                                <!--end::Text-->
                            </div>
                        `);
                        });

                    } else {
                        $("#timeline").append(
                            `<p class="text-center text-muted font-weight-bold">
                            Tidak ada schedule yang terdaftar untuk hari ini
                        </p>`
                        );
                    }

                    $('#time').selectpicker('refresh');
                }
            });
        }
    }

    // modal detail schedule
    $(document).on("click", ".btn-detail, .btn-detail2", function() {
        $("#detail_id").text($(this).data("id"));
        $("#detail_tech").text($(this).data("tech"));

        const date = new Date($(this).data("date"));
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        $("#detail_date").text(date.toLocaleDateString('id-ID', options));

        $("#detail_job").text($(this).data("job"));
        $("#detail_status")
            .removeClass()
            .addClass(`badge badge-pill badge-${$(this).data("state")}`)
            .text($(this).data("status"));

        $("#detail_location").text($(this).data("location"));
        $("#detail_netpayId").text($(this).data("netpay"));
        $("#detail_phone").text($(this).data("phone"));

        // hanya untuk btn-detail2 → kasih link report

        if ($(this).hasClass("btn-detail2")) {
            $("#detail_btn_report").attr(
                "href", `${HOST_URL}pages/schedule/issue_report.php?id=${$(this).data("id")}`
            )
            if ($(this).data("status") == "Cancelled" || $(this).data("status") == "Done") {
                $("#detail_btn_report").addClass('d-none');
                $("#detail_btn_done").addClass('d-none');
            }
            if ($(this).data("status") == "Pending" || $(this).data("status") == "Rescheduled") {
                $("#detail_btn_report").removeClass('d-none');
                $("#detail_btn_done").removeClass('d-none');
            }
            $("#detail_btn_done").attr(
                "href", `${HOST_URL}pages/ikr/create.php?id=${$(this).data("id")}`
            )
        }
        $("#detailModal").modal("show");
    });
    $(document).ready(function() {
        $('#name').on('input', function() {
            let nama = $(this).val().trim();

            // ambil maksimal 8 karakter pertama nama
            let base = nama.toLowerCase()
                .replace(/[^a-z0-9\s]/g, '') // buang karakter aneh
                .replace(/\s+/g, '') // hapus spasi
                .slice(0, 8); // <-- batasi 8 huruf

            // random 2 digit (lebih singkat)
            let rand = Math.floor(Math.random() * 90) + 10;

            // gabungkan
            let username = base ? base + rand : '';

            $('#username-disabled').val(username);
            $('#username').val(username);
        });
    });


    function confirmDelete(scheduleId) {
        Swal.fire({
            title: 'Yakin mau hapus?',
            text: "Data schedule akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = HOST_URL + "controllers/schedules/delete.php?id=" + scheduleId;
            }
        });
    }


    async function confirmApproved(issueId, scheduleId, jobType) {
        const result = await Swal.fire({
            title: 'Reschedule Jadwal?',
            text: "Apakah Anda yakin ingin menjadwalkan ulang kendala ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Reschedule',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
        });

        if (result.isConfirmed) {
            submitForm(`${HOST_URL}pages/schedule/update.php`, [{
                    name: "id",
                    value: scheduleId
                },
                {
                    name: "job_type",
                    value: jobType
                },
                {
                    name: "issue_id",
                    value: issueId
                }
            ]);
        }
    }

    async function confirmRejected(issueId, scheduleId) {
        const result = await Swal.fire({
            title: 'Yakin menolak kendala?',
            text: "Status kendala akan diubah menjadi Rejected",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tolak',
            confirmButtonColor: '#d33',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            $("#exampleModalScrollable").modal("hide");
            submitForm(`${HOST_URL}controllers/schedules/reject_report.php`, [
                { name: "id", value: issueId },
                { name: "scheduleId", value: scheduleId }
            ]);
        }
    }

    // Helper untuk bikin & submit form
    function submitForm(action, fields) {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = action;

        fields.forEach(({
            name,
            value
        }) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }


    $(".btn-detail3").on("click", function() {
        $("#detail_idIssue").text($(this).data("id"));
        $("#detail_schedule").text($(this).data("schedule"));
        $("#detail_reported").text($(this).data("reported"));
        $("#detail_issue").text($(this).data("type"));
        const datetime = $(this).data("date");
        const date = new Date(datetime.replace(" ", "T"));

        // Bagian tanggal → pakai locale Indonesia
        const tanggal = date.toLocaleDateString("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric"
        });

        // Bagian jam → pakai locale Inggris (pemisah :)
        const waktu = date.toLocaleTimeString("en-GB", {
            hour: "2-digit",
            minute: "2-digit",
        });
        $("#detail_dateIssue").text(`Jam ${waktu}.${tanggal}`);
        $("#detail_stat").attr("class", "label label-inline label-light-" + $(this).data("state") + " font-weight-bolder py-3 px-4").text($(this).data("status"));
        $("#detail_desc").text($(this).data("desc"));
        $("#detailModalIssue").modal("show");
    });
    $(".btn-detail4").on("click", function() {
        $("#detail_id_issue").text($(this).data("issue-id"));
        $("#detail_schedule").text($(this).data("schedule-id"));
        $("#detail_reported").text($(this).data("reported-by"));
        $("#detail_issue").text($(this).data("issue-type"));
        $("#detail_created_at").text($(this).data("created-at"));
        $("#detail_issue_status").text($(this).data("issue-status"));
        $("#detail_desc").text($(this).data("description"));
        $("#detail_date").text($(this).data("date"));
        $("#detail_job_type").text($(this).data("job-type"));
        $("#detail_schedule_status").text($(this).data("schedule-status"));
        $("#detail_location").text($(this).data("location"));

        $("#detailModalIssue").modal("show");
    });


    // Fungsi untuk menampilkan Preview saat pilih foto
    function previewFile(input) {
        var file = input.files[0];

        if (file) {
            var reader = new FileReader();

            reader.onload = function() {
                var previewBox = document.getElementById("previewBox");
                previewBox.style.backgroundImage = "url(" + reader.result + ")";
            }

            reader.readAsDataURL(file);
        }
    }

    // Fungsi untuk tombol silang (Reset ke default)
    function resetFile() {
        var previewBox = document.getElementById("previewBox");
        var inputGambar = document.getElementById("inputGambar");

        // Ganti url gambar di bawah ini sesuai lokasi file blank.png kamu
        previewBox.style.backgroundImage = "url('assets/media/users/blank.png')";

        // Kosongkan input file agar tidak ikut terkirim
        inputGambar.value = "";
    }


</script>
<!--end::Page Scripts-->
</body>
<!--end::Body-->

</html>