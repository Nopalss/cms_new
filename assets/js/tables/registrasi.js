"use strict";
// Class definition

var KTDatatableLocalSortDemo = function () {

    // ---------------------------------------------------------------
    // State filter yang sedang aktif (dipakai untuk kirim ke server
    // dan untuk sinkronisasi tampilan KPI / tombol periode / dropdown)
    // ---------------------------------------------------------------
    var state = {
        period: 'month',   // today | week | month | custom
        from: '',
        to: '',
        status: '',
        paket: '',
    };

    var datatable = null;
    var DASHBOARD_API_URL = HOST_URL + 'api/registrasi.php';

    // Private functions

    // simple debounce helper (biar ketikan di search box nggak nembak request tiap huruf)
    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    }

    // Kumpulkan query filter yang sedang aktif, dalam format yang
    // sama seperti yang dipakai KTDatatable (query[...])
    function currentQuery() {
        return {
            generalSearch: $('#kt_datatable_search_query').val() || '',
            status: state.status,
            paket: state.paket,
            period: state.period,
            from: state.from,
            to: state.to,
        };
    }

    // Dorong filter (status/period/from/to/paket) ke datatable supaya
    // request berikutnya dari KTDatatable ikut membawa filter ini.
    // generalSearch tidak perlu didorong manual karena sudah otomatis
    // terikat lewat konfigurasi `search: { input: ..., key: 'generalSearch' }`.
    function pushFiltersToDatatable() {
        if (!datatable) return;
        datatable.setDataSourceQuery(currentQuery());
        datatable.load();
    }

    // Ambil ringkasan KPI + jumlah data untuk filter yang sedang aktif,
    // lalu update tampilan KPI card, counter "Data ditemukan", dan empty state.
    function toggleEmptyState(isEmpty) {
        $('#registrasi-empty-state').toggleClass('show', isEmpty);
        $('#kt_datatable').toggle(!isEmpty);
    }

    // Highlight KPI card yang sedang jadi filter aktif (visual only)
    function syncActiveKpiCard() {
        $('.kpi-card').removeClass('active-filter');
        if (state.paket) {
            $('#kpi-card-paket').addClass('active-filter');
        } else if (state.status === 'Verified') {
            $('#kpi-card-verified').addClass('active-filter');
        } else if (state.status === 'Unverified') {
            $('#kpi-card-unverified').addClass('active-filter');
        } else {
            $('#kpi-card-total').addClass('active-filter');
        }
    }

    function applyAllFilters() {
        syncActiveKpiCard();
        pushFiltersToDatatable();
    }

    // basic demo
    var demo = function () {
        datatable = $('#kt_datatable').KTDatatable({
            // datasource definition
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: DASHBOARD_API_URL,
                        params: {
                            query: currentQuery()
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

            // layout definition
            layout: {
                scroll: true,   // biar tabel scrollable (horizontal/vertical)
                footer: false,
            },

            // column sorting
            sortable: true,

            pagination: true,

            search: {
                input: $('#kt_datatable_search_query'),
                key: 'generalSearch'
            },
            // columns definition
            columns: [{
                field: 'registrasi_id',
                title: 'Registrasi Id',
                template: row => `<span style="font-size:0.885rem">${row.registrasi_id}</span>`

            }, {
                field: 'name',
                title: 'Name',
                template: row => `<span style="font-size:0.885rem">${row.name}</span>`

            },
            {
                field: 'paket_internet',
                title: 'Paket',
                template: function (row) {
                    return `<span style="font-size:0.885rem"> ${row.paket_internet} mbps</span>`;
                },
            }, {
                field: 'is_verified',
                title: 'Status',
                autoHide: false,
                // callback function support for column rendering
                template: function (row) {
                    var status = {
                        'Verified': {
                            'title': 'Verified',
                            'state': 'success'
                        },
                        'Unverified': {
                            'title': 'Unverified',
                            'state': 'danger'
                        },

                    };
                    return '<span class="label label-' + status[row.is_verified].state + ' label-dot mr-2"></span><span class="font-weight-bold text-' + status[row.is_verified].state + '">' +
                        status[row.is_verified].title + '</span>';
                },
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 195,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    let verified = '';

                    if (row.is_verified === "Unverified") {
                        verified = `
             <a href="${HOST_URL + 'pages/request/ikr/create.php?id=' + row.registrasi_id}"
                            class="btn btn-sm btn-success btn-icon">
                            <i class="flaticon2-check-mark s"></i>
                        </a>
                        <a href='${HOST_URL + 'pages/registrasi/update.php?id=' + row.registrasi_id}'
                           class="btn btn-sm btn-warning btn-icon mr-2" >
                            <i class="flaticon-edit"></i>
                        </a >
        `;
                    }

                    return `
    
                    
                    
                    <a  data-id="${row.registrasi_id}" data-name="${row.name}" data-location="${row.location}" data-phone="${row.phone}" data-paket="${row.paket_internet}" data-verified="${row.is_verified}" data-date="${row.date}" data-time="${row.time}"
           class="btn btn-sm btn-info btn-icon btn-detail-registrasi mr-2">
            <i class="flaticon-eye"></i>
        </a>
        ${verified}
        <a onclick="confirmDeleteTemplate('${row.registrasi_key}', 'controllers/registrasi/delete.php')"
           class="btn btn-sm btn-danger btn-icon">
            <i class="flaticon-delete"></i>
            </a>
    `;
                },
            }],
        });

        // Update KPI and summary cards automatically when datatable receives data from AJAX.
        // This avoids making a separate redundant HTTP request for counts.
        datatable.on('datatable-on-ajax-done', function (e, dataSet) {
            var response = datatable.lastResponse;
            if (!response || response.error) return;

            var summary = response.summary || {};
            var rowCount = (dataSet || []).length;

            $('#kpi-value-total').text(summary.total || 0);
            $('#kpi-value-verified').text(summary.verified || 0);
            $('#kpi-value-unverified').text(summary.unverified || 0);

            if (summary.top_paket) {
                $('#kpi-value-paket').text(summary.top_paket + ' Mbps');
                $('#kpi-subtext-paket').text((summary.top_paket_count || 0) + ' Registrasi');
                $('#kpi-card-paket').attr('data-paket-value', summary.top_paket);
            } else {
                $('#kpi-value-paket').text('-');
                $('#kpi-subtext-paket').text('0 Registrasi');
                $('#kpi-card-paket').attr('data-paket-value', '');
            }

            $('#kt_data_found_count').text(rowCount);
            toggleEmptyState(rowCount === 0);
        });

        // ---------------- Status dropdown ----------------
        $('#kt_datatable_search_status').on('change', function () {
            state.status = $(this).val();
            state.paket = ''; // status manual override membatalkan filter paket dari KPI
            applyAllFilters();
        });



        // ---------------- Filter Periode ----------------
        $('#periode-btn-group .btn').on('click', function () {
            var period = $(this).data('period');

            $('#periode-btn-group .btn').removeClass('active');
            $(this).addClass('active');

            if (period === 'custom') {
                $('#periode-custom-range').addClass('show');
                return; // menunggu user klik "Terapkan"
            }

            $('#periode-custom-range').removeClass('show');
            state.period = period;
            state.from = '';
            state.to = '';
            applyAllFilters();
        });

        $('#periode-custom-apply').on('click', function () {
            var from = $('#periode-custom-from').val();
            var to = $('#periode-custom-to').val();

            if (!from || !to) {
                return; // biarkan browser validasi input date bawaan
            }

            state.period = 'custom';
            state.from = from;
            state.to = to;
            applyAllFilters();
        });

        $('#periode-custom-reset').on('click', function () {
            $('#periode-custom-from').val('');
            $('#periode-custom-to').val('');
            $('#periode-custom-range').removeClass('show');

            $('#periode-btn-group .btn').removeClass('active');
            $('#periode-btn-group .btn[data-period="today"]').addClass('active');

            state.period = 'today';
            state.from = '';
            state.to = '';
            applyAllFilters();
        });

        // ---------------- KPI card click → filter datatable ----------------
        $('#kpi-card-total').on('click', function () {
            state.status = '';
            state.paket = '';
            $('#kt_datatable_search_status').val('');
            applyAllFilters();
        });

        $('#kpi-card-verified').on('click', function () {
            state.status = 'Verified';
            state.paket = '';
            $('#kt_datatable_search_status').val('Verified');
            applyAllFilters();
        });

        $('#kpi-card-unverified').on('click', function () {
            state.status = 'Unverified';
            state.paket = '';
            $('#kt_datatable_search_status').val('Unverified');
            applyAllFilters();
        });

        $('#kpi-card-paket').on('click', function () {
            var paketValue = $(this).attr('data-paket-value');
            if (!paketValue) return; // belum ada paket terlaris untuk periode ini
            state.paket = paketValue;
            state.status = '';
            $('#kt_datatable_search_status').val('');
            applyAllFilters();
        });

        // Render awal: sync kartu aktif
        syncActiveKpiCard();
    };

    return {
        // public functions
        init: function () {
            demo();
        },
    };
}();




$(window).on('load', function () {
    KTDatatableLocalSortDemo.init();
});