
// request ikr
// notifikasi untuk menambahkan rikr
function updateUnverifiedCount() {
    $.ajax({
        url: `${HOST_URL}api/get_unverified_register.php`,
        method: "GET",
        dataType: "json",
        success: function (result) {
            if (result.status === "success") {
                if (result.total > 0) {
                    $("#unverifiedCount")
                        .text(result.total)
                        .show();
                } else {
                    $("#unverifiedCount").hide();
                }
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
}

$(document).ready(function () {

    // load pertama kali
    updateUnverifiedCount();

    // ulangi tiap 10 detik
    setInterval(updateUnverifiedCount, 10000);

    $('#registrasi_id').on('change', function () {
        let id = $(this).val();
        if (id) {
            $.ajax({
                url: `${HOST_URL}api/get_register.php`,
                type: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function (data) {
                    if (Object.keys(data).length > 0) {
                        $('#name').val(data.name);
                        $('#registrasi_id2').val(data.registrasi_id);
                        $('#phone').val(data.phone);
                        $('#paket_internet').val(data.paket_internet).selectpicker('refresh');;
                        $('#is_verified').val(data.is_verified).selectpicker('refresh');;
                        $('#perumahan').val(data.perumahan);
                        $('#location').val(data.location);
                        $('#time').val(data.time);
                        $('#date').val(data.date);
                        $('#date_pemasangan').val(data.date);
                        $('#time_pemasangan').val(data.time).selectpicker('refresh');
                    } else {
                        // reset kalau tidak ada
                        $('#name, #phone, #paket_internet, #location, #date, #time, #date_pemasangan, #time_pemasangan').val('');
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                }
            });
        } else {
            $('#name, #phone, #paket_internet, #location, #date, #time, #date_pemasangan, #time_pemasangan').val('');
        }
    });
    // $('#jadwal_pemasangan, #request_schedule').on('change', function () {
    //     let date = $(this).val();
    //     $('#request_schedule').val(date);
    //     $('#jadwal_pemasangan').val(date);
    // })
});
// modal detail rikr
$(document).on("click", ".btn-detail-rikr", function () {
    $("#detail_rikrId").text($(this).data("rikr-id"));
    $("#detail_netpayId").text($(this).data("netpay-id"));
    $("#detail_registrasiId").text($(this).data("registrasi-id"));
    $("#detail_status").text($(this).data("status")).addClass(`font-weight-bold text-${$(this).data("state")}`);
    const datetime = $(this).data("jadwal");
    const date = new Date(datetime.replace(" ", "T"));
    // Tanggal → format Indonesia
    const tanggal = date.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
    });

    // Jam → format 24 jam dengan pemisah :
    const waktu = date.toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
    });

    $("#detail_jadwal").text(tanggal);
    $("#detail_jam").text(`${waktu} WIB`);
    $("#detail_catatan").text($(this).data("catatan"));
    $("#detail_requestBy").text($(this).data("request-by"));

    $("#detailModalRIKR").modal("show");
});




