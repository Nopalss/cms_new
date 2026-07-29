"use strict";

var KTDatatableLocalSortDemo = function () {

    var state = {
        period: 'month',
        from:   '',
        to:     '',
    };

    var datatable = null;

    // Encode semua filter ke SATU JSON string → satu datatable.search() → satu reload
    function pushFiltersToDatatable() {
        if (datatable) {
            var filterState = JSON.stringify({
                period: state.period,
                from:   state.from   || '',
                to:     state.to     || '',
            });
            datatable.search(filterState, 'filterState');
        }
    }

    function loadMobileList() {
        var $wrap = $('#kt_mobile_list');
        if (!$wrap.length || !$wrap.is(':visible')) return;
        if (datatable) {
            renderMobileList(datatable.dataSet || []);
        }
    }

    function renderMobileList(rows) {
        var $wrap = $('#kt_mobile_list');
        if (!rows.length) {
            $wrap.html('<div class="text-center py-10 text-muted"><i class="fa fa-inbox fa-2x mb-3 d-block"></i>Belum ada data</div>');
            return;
        }

        var html = rows.map(function(row) {
            var initial = (row.perumahan || row.netpay_id || '?').substring(0, 2).toUpperCase();
            var tgl = row.created_at ?
                new Date(row.created_at).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }) : '-';

            return `
            <div class="mrl-card">
                <div class="mrl-avatar">${initial}</div>
                <div class="mrl-body">
                    <div class="mrl-top">
                        <div class="mrl-title">${row.perumahan ?? '-'}</div>
                        <div class="mrl-date">${tgl}</div>
                    </div>
                    <div class="mrl-sub">${row.dismantle_id ?? '-'} &middot; ${row.netpay_id ?? '-'}</div>
                    <div class="mrl-desc"><i class="fa fa-clock mr-1"></i>Durasi: ${row.durasi ?? '-'}</div>
                    <div class="mrl-actions d-flex justify-content-end mt-3">
                        <a href="${HOST_URL}pages/dismantle/detail.php?id=${row.dismantle_id}" class="btn btn-sm btn-light-info btn-icon mr-2">
                            <i class="flaticon-eye"></i>
                        </a>
                        <a href="${HOST_URL}pages/dismantle/update.php?id=${row.dismantle_id}" class="btn btn-sm btn-light-warning btn-icon mr-2">
                            <i class="flaticon-edit"></i>
                        </a>
                        <a onclick="confirmDeleteTemplate('${row.dismantle_key}', 'controllers/report/dismantle/delete.php')" class="btn btn-sm btn-light-danger btn-icon">
                            <i class="flaticon-delete"></i>
                        </a>
                    </div>
                </div>
            </div>`;
        }).join('');

        $wrap.html(html);
    }

    function applyAllFilters() {
        pushFiltersToDatatable();
     }

    var demo = function () {

        datatable = $('#kt_datatable').KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/dismantle_report.php',
                        params: {
                            query: {
                                filterState: JSON.stringify({
                                    period: state.period,
                                    from:   state.from   || '',
                                    to:     state.to     || '',
                                })
                            }
                        }
                    },
                },
                pageSize: 10,
                serverPaging: false,
                serverFiltering: true,
                serverSorting: false,
                saveState: { cookie: false, webstorage: false },
            },
            layout: { scroll: true, footer: false },
            sortable: true,
            pagination: true,
            search: {
                input: $('#kt_datatable_search_query'),
                key: 'generalSearch'
            },
            columns: [{
                field: 'dismantle_id',
                title: 'Dismantle Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.dismantle_id}</span>`
            }, {
                field: 'netpay_id',
                title: 'Netpay Id',
                template: row => `<span style="font-size:0.885rem">${row.netpay_id}</span>`
            }, {
                field: 'durasi',
                title: 'Durasi',
                template: row => `<span style="font-size:0.885rem">${row.durasi || '-'}</span>`
            }, {
                field: 'perumahan',
                title: 'Perumahan',
                template: row => `<span style="font-size:0.885rem">${row.perumahan || '-'}</span>`
            }, {
                field: 'created_at',
                title: 'Tanggal',
                template: function (row) {
                    const date = new Date(row.created_at);
                    const formattedDate = date.toLocaleDateString('id-ID', { year: 'numeric', month: 'numeric', day: 'numeric' });
                    return `<span style="font-size:0.885rem">${formattedDate}</span>`;
                }
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 125,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    return `
                        <a href="${HOST_URL}pages/dismantle/detail.php?id=${row.dismantle_id}" class="btn btn-sm btn-info btn-icon mr-2">
                            <i class="flaticon-eye"></i>
                        </a>
                        <a href="${HOST_URL}pages/dismantle/update.php?id=${row.dismantle_id}" class="btn btn-sm btn-warning btn-icon mr-2">
                            <i class="flaticon-edit"></i>
                        </a>
                        <a onclick="confirmDeleteTemplate('${row.dismantle_key}', 'controllers/report/dismantle/delete.php')" class="btn btn-sm btn-danger btn-icon">
                            <i class="flaticon-delete"></i>
                        </a>
                    `;
                },
            }],
        });

        datatable.on('datatable-on-ajax-done', function (e, dataSet) {
            var res = datatable.lastResponse;
            if (res && res.summary) {
                $('#kpi-value-total').text(res.summary.total !== null && res.summary.total !== undefined ? res.summary.total : 0);
            }
            renderMobileList(dataSet || []);
        });

        // ---------------- Filter Periode ----------------
        $('#periode-btn-group').on('click', 'button', function () {
            var period = $(this).data('period');
            $('#periode-btn-group .btn').removeClass('active');
            $(this).addClass('active');

            if (period === 'custom') {
                $('#periode-custom-range').addClass('show');
                return;
            }

            $('#periode-custom-range').removeClass('show');
            state.period = period;
            state.from   = '';
            state.to     = '';
            applyAllFilters();
        });

        // ---------------- Custom Date range ----------------
        $('#periode-custom-apply').on('click', function () {
            var from = $('#periode-custom-from').val();
            var to   = $('#periode-custom-to').val();
            if (!from || !to) return;

            state.period = 'custom';
            state.from   = from;
            state.to     = to;
            applyAllFilters();
        });

        $('#periode-custom-reset').on('click', function () {
            $('#periode-custom-from').val('');
            $('#periode-custom-to').val('');
            $('#periode-custom-range').removeClass('show');
            $('#periode-btn-group .btn').removeClass('active');
            $('#periode-btn-group .btn[data-period="today"]').addClass('active');

            state.period = 'today';
            state.from   = '';
            state.to     = '';
            applyAllFilters();
        });



        // muat ulang otomatis kalau layar di-resize/rotate lintas breakpoint mobile<->desktop
        var resizeTimer = null;
        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(loadMobileList, 300);
        });
    };

    return {
        init: function () { demo(); }
    };
}();

$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});