"use strict";

var KTDatatableLocalSortDemo = function () {

    var state = {
        period:   'month',
        from:     '',
        to:       '',
        status:   '',
        job_type: '',
        tech_id:  '',
    };

    var datatable = null;

    // Encode semua filter ke SATU JSON string → satu reload, no race condition
    function pushFiltersToDatatable() {
        if (!datatable) return;
        var filterState = JSON.stringify({
            period:   state.period,
            from:     state.from     || '',
            to:       state.to       || '',
            status:   state.status   || '',
            job_type: state.job_type || '',
            tech_id:  state.tech_id  || '',
        });
        datatable.search(filterState, 'filterState');
    }

    function applyAllFilters() {
        pushFiltersToDatatable();
    }

    var statusMap = {
        'Pending':     { title: 'Pending',     state: 'info'    },
        'Actived':     { title: 'Actived',     state: 'primary' },
        'Rescheduled': { title: 'Rescheduled', state: 'warning' },
        'Cancelled':   { title: 'Cancelled',   state: 'danger'  },
        'Done':        { title: 'Done',        state: 'success' },
    };

    var demo = function () {

        datatable = $('#kt_datatable').KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/schedules.php',
                        params: {
                            query: {
                                filterState: JSON.stringify({
                                    period:   state.period,
                                    from:     state.from     || '',
                                    to:       state.to       || '',
                                    status:   state.status   || '',
                                    job_type: state.job_type || '',
                                    tech_id:  state.tech_id  || '',
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
                field: 'schedule_id',
                title: 'Schedule Id',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.schedule_id}</span>`
            }, {
                field: 'netpay_id',
                title: 'Netpay ID',
                autoHide: false,
                template: row => `<span style="font-size:0.885rem">${row.netpay_id}</span>`
            }, {
                field: 'technician_name',
                title: 'Teknisi',
                template: row => `<span style="font-size:0.885rem">${row.technician_name || '-'}</span>`
            }, {
                field: 'date',
                title: 'Tanggal',
                template: function (row) {
                    const date = new Date(row.date);
                    const formatted = date.toLocaleDateString('id-ID', { year: 'numeric', month: 'numeric', day: 'numeric' });
                    return `<span style="font-size:0.885rem">${formatted}</span>`;
                }
            }, {
                field: 'time',
                title: 'Jam',
                template: row => `<span style="font-size:0.885rem">${row.time}</span>`
            }, {
                field: 'location',
                title: 'Lokasi',
                template: row => `<span style="font-size:0.885rem">${row.location || '-'}</span>`
            }, {
                field: 'job_type',
                title: 'Job Type',
                autoHide: false,
                template: function (row) {
                    var typeColor = {
                        'Instalasi':   'primary',
                        'Maintenance': 'warning',
                        'Dismantle':   'danger',
                        'Service':     'info',
                    };
                    var color = typeColor[row.job_type] || 'secondary';
                    return `<span class="label label-light-${color} label-inline font-weight-bold">${row.job_type}</span>`;
                }
            }, {
                field: 'status',
                title: 'Status',
                autoHide: false,
                template: function (row) {
                    var current = statusMap[row.status] ? row.status : 'Pending';
                    return '<span class="label label-' + statusMap[current].state + ' label-dot mr-2"></span>' +
                        '<span class="font-weight-bold text-' + statusMap[current].state + '">' +
                        statusMap[current].title + '</span>';
                },
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 125,
                autoHide: false,
                overflow: 'visible',
                template: function (row) {
                    return `
                    <form action="${HOST_URL}pages/schedule/detail.php" method="post" style="display:inline;">
                        <input type="hidden" name="job_type" value="${row.job_type}">
                        <button type="submit" name="id" value="${row.schedule_id}"
                                class="btn btn-sm btn-info btn-icon mr-2">
                            <i class="flaticon-eye"></i>
                        </button>
                    </form>
                    <form action="${HOST_URL}pages/schedule/update.php" method="post" style="display:inline;">
                        <input type="hidden" name="job_type" value="${row.job_type}">
                        <button type="submit" name="id" value="${row.schedule_id}"
                                class="btn btn-sm btn-warning btn-icon mr-2">
                            <i class="flaticon-edit"></i>
                        </button>
                    </form>
                    <a onclick="confirmDeleteTemplate('${row.schedule_key}', 'controllers/schedules/delete.php')"
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
                $('#kpi-value-actived').text(s.actived !== null && s.actived !== undefined ? s.actived : 0);
                $('#kpi-value-done').text(s.done !== null && s.done !== undefined ? s.done : 0);
                $('#kpi-value-rescheduled').text(s.rescheduled !== null && s.rescheduled !== undefined ? s.rescheduled : 0);
                $('#kpi-value-cancelled').text(s.cancelled !== null && s.cancelled !== undefined ? s.cancelled : 0);
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

        // ---------------- Filter Status ----------------
        $('#kt_datatable_search_status').on('change', function () {
            state.status = $(this).val();
            applyAllFilters();
        });

        // ---------------- Filter Job Type ----------------
        $('#kt_datatable_search_type').on('change', function () {
            state.job_type = $(this).val();
            applyAllFilters();
        });

        // ---------------- Filter Teknisi ----------------
        $('#kt_datatable_search_tech').on('change', function () {
            state.tech_id = $(this).val();
            applyAllFilters();
        });



        $('#kt_datatable_search_status, #kt_datatable_search_type, #kt_datatable_search_tech').selectpicker();
    };

    return {
        init: function () { demo(); }
    };
}();

$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});