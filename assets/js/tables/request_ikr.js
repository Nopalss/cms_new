"use strict";

var KTDatatableLocalSortDemo = function () {

    var state = {
        period: 'month',
        from:   '',
        to:     '',
        status: '',
    };

    var datatable = null;

    // Encode semua filter ke SATU JSON string → satu datatable.search() → satu reload
    function pushFiltersToDatatable() {
        if (!datatable) return;
        var filterState = JSON.stringify({
            period: state.period,
            from:   state.from   || '',
            to:     state.to     || '',
            status: state.status || '',
        });
        datatable.search(filterState, 'filterState');
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
                        url: HOST_URL + 'api/request_ikr.php',
                        params: {
                            query: {
                                filterState: JSON.stringify({
                                    period: state.period,
                                    from:   state.from   || '',
                                    to:     state.to     || '',
                                    status: state.status || '',
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
                field: 'rikr_id',
                title: 'RIKR Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.rikr_id}</span>`
            }, {
                field: 'netpay_id',
                title: 'Netpay Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.netpay_id || '-'}</span>`
            }, {
                field: 'registrasi_id',
                title: 'Registrasi Id',
                template: row => `<span style="font-size:0.885rem">${row.registrasi_id}</span>`
            }, {
                field: 'jadwal_pemasangan',
                title: 'Jadwal Pemasangan',
                template: row => `<span style="font-size:0.885rem">${row.jadwal_pemasangan}</span>`
            }, {
                field: 'status',
                title: 'Status',
                template: function (row) {
                    var statusMap = {
                        'Accepted':  { title: 'Accepted',  state: 'success' },
                        'Rejected':  { title: 'Rejected',  state: 'danger'  },
                        'Pending':   { title: 'Pending',   state: 'info'    },
                        'Not Queued':{ title: 'Not Queued',state: 'muted'   },
                    };
                    var current = statusMap[row.status] ? row.status : 'Not Queued';
                    return '<span class="label label-' + statusMap[current].state + ' label-dot mr-2"></span>' +
                        '<span class="font-weight-bold text-' + statusMap[current].state + '">' +
                        statusMap[current].title + '</span>';
                },
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 125,
                overflow: 'visible',
                template: function (row) {
                    var statusMap = {
                        'Accepted':  { title: 'Accepted',  state: 'success' },
                        'Rejected':  { title: 'Rejected',  state: 'danger'  },
                        'Pending':   { title: 'Pending',   state: 'info'    },
                        'Not Queued':{ title: 'Not Queued',state: 'muted'   },
                    };
                    var current = statusMap[row.status] ? row.status : 'Not Queued';

                    var editDelete = '';
                    if (row.status === 'Pending') {
                        editDelete = `
                        <a href='${HOST_URL + 'pages/request/ikr/update.php?id=' + row.rikr_id}'
                           class="btn btn-sm btn-warning btn-icon mr-2">
                            <i class="flaticon-edit"></i>
                        </a>
                        <a onclick="confirmDeleteTemplate('${row.rikr_key}', 'controllers/request/ikr/delete.php')"
                           class="btn btn-sm btn-danger btn-icon">
                            <i class="flaticon-delete"></i>
                        </a>`;
                    }

                    return `
                    <a data-rikr-id="${row.rikr_id}" data-status="${row.status}" data-state="${statusMap[current].state}"
                       data-registrasi-id="${row.registrasi_id}" data-netpay-id="${row.netpay_id}"
                       data-jadwal="${row.jadwal_pemasangan}" data-catatan="${row.catatan}" data-request-by="${row.request_by}"
                       class="btn btn-sm btn-info btn-icon btn-detail-rikr mr-2">
                        <i class="flaticon-eye"></i>
                    </a>
                    ${editDelete}`;
                },
            }],
        });

        datatable.on('datatable-on-ajax-done', function (e, dataSet) {
            var res = datatable.lastResponse;
            if (res && res.summary) {
                $('#kpi-value-total').text(res.summary.total !== null && res.summary.total !== undefined ? res.summary.total : 0);
                $('#kpi-value-accepted').text(res.summary.accepted !== null && res.summary.accepted !== undefined ? res.summary.accepted : 0);
                $('#kpi-value-pending').text(res.summary.pending !== null && res.summary.pending !== undefined ? res.summary.pending : 0);
                $('#kpi-value-rejected').text(res.summary.rejected !== null && res.summary.rejected !== undefined ? res.summary.rejected : 0);
            }
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

        // ---------------- Status dropdown ----------------
        $('#kt_datatable_search_status').on('change', function () {
            state.status = $(this).val();
            applyAllFilters();
        });


    };

    return {
        init: function () { demo(); }
    };
}();

$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});