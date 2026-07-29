"use strict";
// Class definition

var KTDatatableLocalSortDemo = function () {
    // Private functions

    // basic demo
    var demo = function () {
        var datatable = $('#kt_datatable').KTDatatable({
            // datasource definition
            data: {
                type: 'remote',
                source: {
                    read: {
                        url: HOST_URL + 'api/users.php',
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
                field: 'username',
                title: 'Username',
            }, {
                field: 'name',
                title: 'Name',
            }, {
                field: 'phone',
                title: 'Phone',
            }, {
                field: 'role',
                title: 'Role / Jabatan',
                template: function (row) {
                    var roleBadge = '<span class="label label-light-primary label-inline font-weight-bold">' + row.role + '</span>';
                    if (row.role === 'admin' && row.jabatan) {
                        roleBadge += ' <span class="label label-light-info label-inline font-weight-bold ml-1">' + row.jabatan + '</span>';
                    }
                    return roleBadge;
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
                            <a href="${HOST_URL + 'pages/user/detail.php?id=' + row.username}"
                               class="btn btn-sm btn-info btn-icon mr-2">
                                <i class="flaticon-eye"></i>
                            </a>
                              <a  href='${HOST_URL + 'pages/user/update.php?id=' + row.username}'
                               class="btn btn-sm btn-warning btn-icon mr-2">
                                <i class="flaticon-edit"></i>
                            </a>
                            <a onclick="confirmDeleteTemplate('${row.id}', 'controllers/user/delete.php')"
                               class="btn btn-sm btn-danger btn-icon">
                                <i class="flaticon-delete"></i>
                            </a>
                        `;
                },
            }],
        });


        $('#kt_datatable_search_role').on('change', function () {
            datatable.search($(this).val().toLowerCase(), 'role');
        });


        $('#kt_datatable_search_role, #kt_datatable_search_paket, #kt_datatable_search_type, #kt_datatable_search_tech').selectpicker();
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