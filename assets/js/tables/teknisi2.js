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
                        url: HOST_URL + 'api/teknisi.php',
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
                field: 'ranking',
                title: 'Rank',
                width: 60,
                textAlign: 'center',
                template: function (row) {
                    var badgeColor = 'secondary';
                    var ranking = parseInt(row.ranking);
                    if (ranking === 1) badgeColor = 'warning'; // Gold
                    else if (ranking === 2) badgeColor = 'dark'; // Silver
                    else if (ranking === 3) badgeColor = 'primary'; // Bronze
                    
                    return '<span class="label label-lg label-light-' + badgeColor + ' label-inline font-weight-bold">#' + row.ranking + '</span>';
                }
            }, {
                field: 'tech_id',
                title: 'NIK',
                width: 120,
            }, {
                field: 'name',
                title: 'Nama Teknisi',
            }, {
                field: 'phone',
                title: 'No. Telp',
                width: 120,
            }, {
                field: 'skor',
                title: 'Skor Kinerja',
                textAlign: 'center',
                width: 150,
                template: function (row) {
                    var percentage = Math.round(row.skor * 100);
                    var state = 'danger';
                    if (row.skor >= 0.8) state = 'success';
                    else if (row.skor >= 0.6) state = 'warning';
                    else if (row.skor >= 0.4) state = 'info';
                    
                    return `
                        <div class="d-flex align-items-center justify-content-center flex-column">
                            <span class="font-weight-bolder text-${state} mb-1">${percentage}%</span>
                            <div class="progress progress-xs w-100" style="height: 5px; width: 100px !important;">
                                <div class="progress-bar bg-${state}" role="progressbar" style="width: ${percentage}%;" aria-valuenow="${percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    `;
                }
            }, {
                field: 'Actions',
                title: 'Actions',
                sortable: false,
                width: 80,
                textAlign: 'center',
                template: function (row) {
                    return `
                      <a href="${HOST_URL + 'pages/teknisi/detail_teknisi.php?id=' + row.tech_id}" class="btn btn-sm btn-info btn-icon" title="Detail Kinerja">
                            <i class="flaticon-eye"></i>
                      </a>
                    `;
                },
            }],
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