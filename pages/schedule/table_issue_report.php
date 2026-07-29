<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'issue report';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
$statusIssueClasses = [
    'Pending' => "info",
    'Approved' => "success",
    'Rejected' => "danger",
];

try {
    $sql = "SELECT * FROM technician";
    $stmt = $pdo->query($sql);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


<div class="content  d-flex flex-column flex-column-fluid" id="kt_content">
    <!--begin::Subheader-->
    <div class="subheader py-2 py-lg-6  subheader-solid " id="kt_subheader">
        <div class=" container-fluid  d-flex align-items-center justify-conten bt-between flex-wrap flex-sm-nowrap">
            <!--begin::Info-->
            <div class="d-flex align-items-center flex-wrap mr-1">

                <!--begin::Page Heading-->
                <div class="d-flex align-items-baseline flex-wrap mr-5">
                    <!--begin::Page Title-->
                    <h5 class="text-dark font-weight-bold my-1 mr-5">
                        Schedules
                    </h5>

                    <!--end::Page Title-->

                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                        <li class="breadcrumb-item">
                            <a href="" class="text-muted">
                                Task Issues Report </a>
                        </li>
                    </ul>
                    <!-- end::Breadcrumb -->
                </div>
                <!--end::Page Heading-->
            </div>
            <!--end::Info-->
        </div>
    </div>
    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class=" container ">

            <!--begin::Card-->
            <div class="card card-custom">
                <div class="card-header flex-wrap border-0 pt-6 pb-0">
                    <div class="card-title">
                        <h3 class="card-label">
                            Data Task Issues Report
                        </h3>
                    </div>
                </div>
                <div class="card-body">
                    <!--begin: Search Form-->
                    <!--begin::Search Form-->
                    <div class="mb-7">
                        <div class="row align-items-center">
                            <div class="col-lg-12 col-xl-12">
                                <div class="row align-items-center">
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="input-icon">
                                            <input type="text" class="form-control" placeholder="Search..." id="kt_datatable_search_query" />
                                            <span><i class="flaticon2-search-1 text-muted"></i></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Issue:</label>
                                            <select class="form-control" id="kt_datatable_search_issue">
                                                <option value="">All</option>
                                                <option value="Absence">Absence</option>
                                                <option value="Equipment">Equipment</option>
                                                <option value="Customer not available">Customer not available</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class="d-flex align-items-center">
                                            <label class="mr-3 mb-0 d-none d-md-block">Teknisi:</label>
                                            <select class="form-control" id="kt_datatable_search_tech">
                                                <option value="">All</option>
                                                <?php foreach ($technicians as $t): ?>
                                                    <option value="<?= $t['tech_id'] ?>"><?= $t['name'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 my-2 my-md-0">
                                        <div class=" d-flex align-items-center">
                                            <div class="input-group date">
                                                <input type="text" class="form-control" name="date" readonly placeholder="mm/dd/yyyy" id="kt_datepicker_3" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <i class="la la-calendar"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Search Form-->
                    <!--end: Search Form-->

                    <!--begin: Datatable-->
                    <div class="datatable datatable-bordered datatable-head-custom" id="kt_datatable"></div>
                    <!--end: Datatable-->
                </div>
            </div>
            <!--end::Card-->



            <!-- modal detail -->
            <div class="modal fade" id="detailModalIssue" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                    <div class="modal-content shadow-lg border-0 rounded-lg">
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="la la-info-circle text-info"></i> Detail Issue Report</h4>
                            <button type="button" class="close text-danger" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Issue ID</div>
                                <div class="col-8" id="detail_id_issue"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Schedule ID</div>
                                <div class="col-8" id="detail_schedule"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Reported</div>
                                <div class="col-8" id="detail_reported"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Issue Type</div>
                                <div class="col-8" id="detail_issue"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Created At</div>
                                <div class="col-8">
                                    <div id="detail_created_at"></div>
                                </div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Status Report</div>
                                <div class="col-8">
                                    <div id="detail_issue_status"></div>
                                </div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Description</div>
                                <div class="col-8" id="detail_desc"></div>
                            </div>

                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Tanggal Scheduele</div>
                                <div class="col-8" id="detail_date"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Job Type</div>
                                <div class="col-8" id="detail_job_type"></div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Status Schedule</div>
                                <div class="col-8">
                                    <div id="detail_schedule_status"></div>
                                </div>
                            </div>
                            <div class="row mb-2 pl-2">
                                <div class="col-4 font-weight-bold">Location</div>
                                <div class="col-8" id="detail_location"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary" data-dismiss="modal">
                                <i class="la la-times"></i> Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end::Container -->
    </div>
</div>
<!-- end::entry -->
<!-- modal detail issue report-->
<div class=" modal fade" id="detailModalIssue" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content shadow-lg border-0 rounded-lg">
            <div class="modal-header">
                <h4 class="modal-title"><i class="la la-info-circle text-info"></i> Detail issue report</h4>
                <button type="button" class="close text-danger" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Issue ID</div>
                    <div class="col-8" id="detail_idIssue"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Schedule ID</div>
                    <div class="col-8" id="detail_schedule"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Reported</div>
                    <div class="col-8" id="detail_reported"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Issue Type</div>
                    <div class="col-8" id="detail_issue"></div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Created At</div>
                    <div class="col-8">
                        <div id="detail_dateIssue"></div>
                    </div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Status Report</div>
                    <div class="col-8">
                        <div id="detail_stat"></div>
                    </div>
                </div>
                <div class="row mb-2 pl-2">
                    <div class="col-4 font-weight-bold">Description</div>
                    <div class="col-8" id="detail_desc"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" data-dismiss="modal">
                    <i class="la la-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>