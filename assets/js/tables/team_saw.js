"use strict";

var KTDatatableLocalSortDemo = function () {

    var demo = function () {
        var datatable = $('#kt_datatable').KTDatatable({
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/team_saw.php',
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
                field: 'tim_id',
                title: 'ID Tim',
                width: 140,
            }, {
                field: 'nama',
                title: 'Nama Tim',
            }, {
                field: 'jumlah_pekerjaan',
                title: 'Total Pekerjaan Selesai',
                textAlign: 'center',
                width: 150,
                template: function (row) {
                    return '<span class="font-weight-bold">' + row.jumlah_pekerjaan + ' Pekerjaan</span>';
                }
            }, {
                field: 'skor',
                title: 'Skor Kinerja (SAW)',
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
            }],
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
