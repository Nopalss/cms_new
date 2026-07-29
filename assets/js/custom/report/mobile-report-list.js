"use strict";

/**
 * MobileReportList
 * Engine generic buat nampilin data laporan (IKR / Service / Dismantle)
 * jadi list kartu ala aplikasi mobile, pake API yang SAMA dengan datatable desktop.
 *
 * Cara pakai (contoh di halaman IKR):
 *   MobileReportList({
 *       wrapper: '#kt_mobile_list',
 *       searchInput: '#kt_datatable_search_query',
 *       dateInput: '#kt_datepicker_3',
 *       url: HOST_URL + 'api/ikr.php',
 *       renderItem: function (row) { return '<div>...</div>'; }
 *   });
 */
function MobileReportList(opts) {
    var $wrap = $(opts.wrapper);
    var $search = $(opts.searchInput);
    var $date = opts.dateInput ? $(opts.dateInput) : null;
    var timer = null;

    function load() {
        // cuma fetch kalau kartunya lagi keliatan (hemat request pas di desktop)
        if (!$wrap.is(':visible')) return;

        $wrap.html('<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>');

        $.post(opts.url, {
            query: {
                generalSearch: $search.val() || '',
                date: $date && $date.val() ? $date.val() : ''
            }
        }, function (res) {
            render(res.data || []);
        }, 'json').fail(function () {
            $wrap.html('<div class="text-center py-10 text-muted">Gagal memuat data. Coba lagi.</div>');
        });
    }

    function render(rows) {
        if (!rows.length) {
            $wrap.html('<div class="text-center py-10 text-muted"><i class="fa fa-inbox fa-2x mb-3 d-block"></i>Belum ada data</div>');
            return;
        }
        $wrap.html(rows.map(opts.renderItem).join(''));
    }

    // search: debounce biar gak nembak request tiap ketikan
    $search.on('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(load, 400);
    });

    if ($date && $date.length) {
        $date.on('changeDate change', load);
    }

    // muat ulang otomatis kalau layar di-resize/rotate lintas breakpoint mobile<->desktop
    $(window).on('resize', function () {
        clearTimeout(timer);
        timer = setTimeout(load, 300);
    });

    load();
    return { reload: load };
}