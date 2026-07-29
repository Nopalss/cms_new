// registrasi
$(document).on("click", ".btn-detail-registrasi", function () {
    $("#detail_registrasiId").text($(this).data("id"));
    $("#detail_name").text($(this).data("name"));
    $("#detail_location").text($(this).data("location"));
    $("#detail_phone").text($(this).data("phone"));
    $("#detail_paketInternet").text($(this).data("paket") + ' mbps');
    const color = {
        "Verified": 'success',
        "Unverified": 'danger'
    }
    $("#detail_isVerified").text($(this).data("verified")).addClass(`badge badge-pill text-weight-bold badge-${color[$(this).data("verified")]}`);
    const datetime = $(this).data("date");
    $("#detail_requestSchedule").text($(this).data("date"));
    const date = new Date(datetime);

    // Bagian tanggal → pakai locale Indonesia
    const tanggal = date.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
    });

    $("#detail_requestSchedule").text(`${tanggal}`);
    $("#detail_requestJam").text($(this).data("time"));
    $("#detailModalRegistrasi").modal("show");
});