<?php
require_once __DIR__ . '/../../includes/config.php';
$_SESSION['menu'] = 'schedule';
$_SESSION['table'] = 'schedule';
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

    <!--end::Subheader-->

    <!--begin::Entry-->
    <div class="d-flex flex-column-fluid">
        <!--begin::Container-->
        <div class=" container ">

            <?php
            if ($_SESSION['role'] == 'admin') {
                require __DIR__ . "/role/admin.php";
            }
            if ($_SESSION['role'] == 'teknisi') {
                require __DIR__ . "/role/teknisi.php";
            }
            ?>
        </div>
        <!-- end::Container -->
    </div>
</div>
<!-- end::entry -->
<!-- modal detail issue report-->
<div class="modal fade" id="detailModalIssue" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
            
            <!-- Modal Header -->
            <div class="modal-header border-bottom-0 py-5 px-6" style="background-color: #f3f4f6;">
                <h4 class="modal-title font-weight-bolder text-dark d-flex align-items-center" style="font-size: 1.2rem; letter-spacing: -0.01em;">
                    <i class="flaticon-warning-sign text-warning mr-3" style="font-size: 1.4rem;"></i> Detail Issue Report
                </h4>
                <button type="button" class="close text-dark-50 hover-text-danger" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none; opacity: 0.7;">
                    &times;
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body px-6 py-5">
                <div class="d-flex flex-column" style="gap: 16px;">
                    
                    <!-- ID & Status row -->
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light">
                        <div>
                            <span class="text-muted font-size-sm font-weight-bold">Issue ID</span>
                            <h6 class="text-dark font-weight-boldest mb-0 mt-1" id="detail_idIssue" style="font-size: 1.05rem;">-</h6>
                        </div>
                        <div>
                            <span id="detail_stat">Pending</span>
                        </div>
                    </div>

                    <!-- Details Box (Grid-like list) -->
                    <div class="p-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                        <div class="row align-items-center mb-3">
                            <div class="col-4 text-muted font-weight-bold font-size-sm">Schedule ID</div>
                            <div class="col-8 font-weight-bolder text-dark" id="detail_schedule">-</div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4 text-muted font-weight-bold font-size-sm">Dilaporkan</div>
                            <div class="col-8 font-weight-bolder text-dark" id="detail_reported">-</div>
                        </div>
                        <div class="row align-items-center mb-3">
                            <div class="col-4 text-muted font-weight-bold font-size-sm">Tipe Kendala</div>
                            <div class="col-8 font-weight-bolder text-dark" id="detail_issue">-</div>
                        </div>
                        <div class="row align-items-center mb-0">
                            <div class="col-4 text-muted font-weight-bold font-size-sm">Waktu Lapor</div>
                            <div class="col-8 font-weight-bold text-muted" id="detail_dateIssue">-</div>
                        </div>
                    </div>

                    <!-- Description Box -->
                    <div class="mt-2">
                        <label class="font-weight-bold text-dark mb-2 font-size-sm" style="letter-spacing: -0.01em;">Deskripsi Kendala Lapangan</label>
                        <div class="p-4 text-dark" id="detail_desc" 
                             style="background-color: #fef2f2; border: 1px dashed #fca5a5; border-radius: 12px; min-height: 80px; white-space: pre-wrap; font-size: 0.95rem; font-weight: 500; line-height: 1.5;">
                            -
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer border-top-0 py-4 px-6" style="background-color: #f9fafb;">
                <button class="btn btn-secondary font-weight-bold py-2 px-5" data-dismiss="modal" style="border-radius: 10px;">
                    Tutup
                </button>
            </div>
            
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../../includes/footer.php';
?>