"use strict";

var KTDatatableLocalSortDemo = function () {

    var state = {
        period: 'month',
        from:   '',
        to:     '',
    };

    var datatable = null;

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

    function applyAllFilters() {
        pushFiltersToDatatable();
    }

    var demo = function () {
        datatable = $('#kt_datatable').KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/customers.php',
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
                saveState: {
                    cookie: false,
                    webstorage: false,
                },
            },

            layout: {
                scroll: true,
                footer: false,
            },

            sortable: true,
            pagination: true,

            search: {
                input: $('#kt_datatable_search_query'),
                key: 'generalSearch'
            },

            columns: [{
                field: 'netpay_id',
                title: 'Netpay Id',
                autoHide: false,
            }, {
                field: 'name',
                title: 'Name',
            }, {
                field: 'phone',
                title: 'Phone',
            }, {
                field: 'paket_internet',
                title: 'Paket',
                template: function (row) {
                    return `${row.paket_internet} Mbps`
                }
            }, {
                field: 'is_active',
                title: 'Is Active',
                autoHide: false,
                template: function (row) {
                    var statusMap = {
                        'active':    { title: 'Active',    state: 'success' },
                        'inactive':  { title: 'Inactive',  state: 'secondary' },
                        'dismantle': { title: 'Dismantle', state: 'danger' }
                    };
                    var current = (row.is_active || 'active').toLowerCase();
                    var matched = statusMap[current] || statusMap['active'];
                    return '<span class="label label-' + matched.state + ' label-dot mr-2"></span>' +
                        '<span class="font-weight-bold text-' + matched.state + '">' +
                        matched.title + '</span>';
                },
            }, {
                field: 'location',
                title: 'Alamat',
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 125,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    return `
                      <a href="${HOST_URL + 'pages/customers/detail.php?id=' + row.netpay_key}"
                               class="btn btn-sm btn-info btn-icon mr-2">
                                <i class="flaticon-eye"></i>
                            </a>
                              <a href='${HOST_URL + 'pages/customers/update.php?id=' + row.netpay_key}' 
                               class="btn btn-sm btn-warning btn-icon mr-2">
                                <i class="flaticon-edit"></i>
                            </a>
                            <a onclick="confirmDeleteTemplate('${row.netpay_key}', 'controllers/customer/delete.php')"
                               class="btn btn-sm btn-danger btn-icon">
                                <i class="flaticon-delete"></i>
                            </a>
                    `;
                },
            }],
        });

        // Done event handler to update KPI counts
        datatable.on('datatable-on-ajax-done', function (e, dataSet) {
            var res = datatable.lastResponse;
            if (res && res.summary) {
                var s = res.summary;
                $('#kpi-value-new').text(s.new_count !== null && s.new_count !== undefined ? s.new_count : 0);
                $('#kpi-value-active').text(s.total_active !== null && s.total_active !== undefined ? s.total_active : 0);
                $('#kpi-value-package').text(s.popular_package || '-');
                $('#kpi-subtext-package').text((s.popular_package_count !== null && s.popular_package_count !== undefined ? s.popular_package_count : 0) + ' pelanggan');
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

        // ---------------- Dropdown & input filters ----------------
        $('#kt_datatable_search_status').on('change', function () {
            datatable.search($(this).val().toLowerCase(), 'status');
        });

        $('#kt_datatable_search_paket').on('change', function () {
            datatable.search($(this).val().toLowerCase(), 'paket_internet');
        });

        $('#kt_datatable_search_status, #kt_datatable_search_paket').selectpicker();
    };

    return {
        init: function () {
            demo();
        },
    };
}();

$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});