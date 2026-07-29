"use strict";

var KTDatatableLocalSortDemo = function () {

    var state = {
        period: 'month',
        from:   '',
        to:     '',
        status: '',
        type:   '',
    };

    var datatable = null;

    // Encode semua filter ke SATU JSON string → satu reload, no race condition
    function pushFiltersToDatatable() {
        if (!datatable) return;
        var filterState = JSON.stringify({
            period: state.period,
            from:   state.from   || '',
            to:     state.to     || '',
            status: state.status || '',
            type:   state.type   || '',
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
                        url: HOST_URL + 'api/queue.php',
                        params: {
                            query: {
                                filterState: JSON.stringify({
                                    period: state.period,
                                    from:   state.from   || '',
                                    to:     state.to     || '',
                                    status: state.status || '',
                                    type:   state.type   || '',
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
                field: 'queue_id',
                title: 'Queue Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.queue_id}</span>`
            }, {
                field: 'netpay_id',
                title: 'Netpay Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.netpay_id}</span>`
            }, {
                field: 'type_queue',
                title: 'Type',
                template: function (row) {
                    var typeColor = {
                        'Install':     'primary',
                        'Maintenance': 'warning',
                        'Dismantle':   'danger',
                        'Service':     'info',
                    };
                    var color = typeColor[row.type_queue] || 'secondary';
                    return `<span class="label label-light-${color} label-inline font-weight-bold">${row.type_queue}</span>`;
                }
            }, {
                field: 'request_id',
                title: 'Request Id',
                template: row => `<span style="font-size:0.885rem">${row.request_id || '-'}</span>`
            }, {
                field: 'status',
                title: 'Status',
                template: function (row) {
                    var statusMap = {
                        'Accepted': { title: 'Accepted', state: 'success' },
                        'Rejected': { title: 'Rejected', state: 'danger'  },
                        'Pending':  { title: 'Pending',  state: 'info'    },
                    };
                    var current = statusMap[row.status] ? row.status : 'Pending';
                    return '<span class="label label-' + statusMap[current].state + ' label-dot mr-2"></span>' +
                        '<span class="font-weight-bold text-' + statusMap[current].state + '">' +
                        statusMap[current].title + '</span>';
                },
            }, {
                field: 'created_at',
                title: 'Created At',
                template: row => `<span style="font-size:0.885rem">${row.created_at}</span>`
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 125,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    var scheduleBtn = '';
                    if (row.status === 'Pending') {
                        scheduleBtn = `
                        <form action="${HOST_URL}pages/schedule/create.php" method="post" style="display:inline;">
                            <input type="hidden" name="type_queue" value="${row.type_queue}">
                            <button type="submit" name="id" value="${row.queue_id}"
                                    class="btn btn-sm btn-warning btn-icon mr-2">
                                <i class="flaticon-calendar-with-a-clock-time-tools"></i>
                            </button>
                        </form>`;
                    }

                    return `
                    <form action="${HOST_URL}pages/queue/detail.php" method="post" style="display:inline;">
                        <input type="hidden" name="type_queue" value="${row.type_queue}">
                        <button type="submit" name="id" value="${row.queue_id}"
                                class="btn btn-sm btn-info btn-icon mr-2">
                            <i class="flaticon-eye"></i>
                        </button>
                    </form>
                    ${scheduleBtn}
                    <a onclick="confirmDeleteTemplate('${row.queue_key}', 'controllers/queue/delete.php')"
                       class="btn btn-sm btn-danger btn-icon">
                        <i class="flaticon-delete"></i>
                    </a>`;
                },
            }],
        });

        datatable.on('datatable-on-ajax-done', function (e, dataSet) {
            var res = datatable.lastResponse;
            if (res && res.summary) {
                var s = res.summary;
                $('#kpi-value-total').text(s.total !== null && s.total !== undefined ? s.total : 0);
                $('#kpi-value-pending').text(s.pending !== null && s.pending !== undefined ? s.pending : 0);
                $('#kpi-value-accepted').text(s.accepted !== null && s.accepted !== undefined ? s.accepted : 0);
                $('#kpi-value-rejected').text(s.rejected !== null && s.rejected !== undefined ? s.rejected : 0);
                
                // type breakdown
                $('#kpi-value-install').text(s.type_install !== null && s.type_install !== undefined ? s.type_install : 0);
                $('#kpi-value-maintenance').text(s.type_maintenance !== null && s.type_maintenance !== undefined ? s.type_maintenance : 0);
                $('#kpi-value-dismantle').text(s.type_dismantle !== null && s.type_dismantle !== undefined ? s.type_dismantle : 0);
                $('#kpi-value-service').text(s.type_service !== null && s.type_service !== undefined ? s.type_service : 0);
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

        // ---------------- Type dropdown ----------------
        $('#kt_datatable_search_type').on('change', function () {
            state.type = $(this).val();
            applyAllFilters();
        });



        $('#kt_datatable_search_status, #kt_datatable_search_type').selectpicker();
    };

    return {
        init: function () { demo(); }
    };
}();

$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});