<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';
$id = $_GET['id'];
$sql = "SELECT EXISTS(SELECT 1 FROM issues_report WHERE schedule_id = :schedule_id AND status = 'Pending')";
$stmt = $pdo->prepare($sql);
$stmt->execute([':schedule_id' => $id]);

$exists = $stmt->fetchColumn();

if ($exists) {
    $_SESSION['info'] = "Schedule sudah direport, aksi dibatalkan";
    redirect("pages/schedule/");
}

$_SESSION['menu'] = 'schedule';
require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';

$q = "SELECT * FROM type WHERE type = 'issue'";
$stmt = $pdo->prepare($q);
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    /* ── Premium Mobile-App Style for Issue Report ── */
    .issue-report-wrapper {
        max-width: 550px;
        margin: 2rem auto;
        width: 100%;
    }
    .app-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .app-card__header {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        padding: 2.25rem 1.75rem;
        color: #ffffff;
        position: relative;
    }
    .app-card__title {
        font-size: 1.45rem;
        font-weight: 800;
        margin: 0;
        letter-spacing: -0.020em;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .app-card__sub {
        font-size: 0.875rem;
        opacity: 0.85;
        margin: 0.4rem 0 0 0;
        font-weight: 500;
    }
    .app-card__body {
        padding: 2.25rem 1.75rem;
    }
    
    /* Info Info Badge */
    .info-badge {
        background: #f8fafc;
        border-radius: 14px;
        padding: 0.85rem 1.15rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 2rem;
        border: 1px solid #e2e8f0;
    }
    .info-badge__icon-wrap {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #e0e7ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
    }
    .info-badge__content {
        display: flex;
        flex-direction: column;
    }
    .info-badge__label {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
        line-height: 1.2;
    }
    .info-badge__value {
        font-size: 0.98rem;
        color: #1e293b;
        font-weight: 800;
        margin: 0;
        line-height: 1.3;
    }

    /* Form Fields */
    .app-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 0.55rem;
        display: block;
    }
    .app-input-wrap {
        position: relative;
        margin-bottom: 1.75rem;
    }
    .app-input {
        width: 100%;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1.1rem;
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 500;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none;
    }
    .app-input:focus {
        border-color: #6366f1;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    /* Native select styling */
    select.app-input {
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.1rem center;
        background-size: 1.15rem;
        padding-right: 2.75rem;
    }

    /* Dynamic slide-down input */
    .dynamic-field {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1), margin-bottom 0.3s ease, opacity 0.2s ease;
        opacity: 0;
    }
    .dynamic-field.show {
        max-height: 120px;
        margin-bottom: 1.75rem;
        opacity: 1;
    }

    /* Textarea */
    .app-textarea {
        resize: none;
        min-height: 110px;
        line-height: 1.5;
    }

    /* Footer buttons */
    .app-card__footer {
        padding: 1.5rem 1.75rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        align-items: center;
    }
    .btn-app-cancel {
        border-radius: 12px;
        padding: 0.8rem 1.5rem;
        font-weight: 700;
        font-size: 0.95rem;
        color: #64748b;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-app-cancel:hover {
        background: #f1f5f9;
        color: #334155;
        text-decoration: none;
    }
    .btn-app-submit {
        border-radius: 12px;
        padding: 0.8rem 1.75rem;
        font-weight: 700;
        font-size: 0.95rem;
        color: #ffffff;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-app-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
    }

    /* Premium centered modal */
    .app-modal .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .app-modal .modal-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 1.75rem;
    }
    .app-modal .modal-title {
        font-weight: 800;
        color: #1e293b;
        font-size: 1.2rem;
        letter-spacing: -0.01em;
    }
    .app-modal .modal-body {
        padding: 2.25rem 1.75rem;
    }
    .app-modal .modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1.25rem 1.75rem;
    }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="d-flex flex-column-fluid">
        <div class="container d-flex justify-content-center">
            
            <div class="issue-report-wrapper">
                <div class="app-card">
                    
                    <!-- Header -->
                    <div class="app-card__header">
                        <h3 class="app-card__title">
                            <i class="flaticon-warning-sign" style="font-size: 1.35rem;"></i>
                            Task Issue Report
                        </h3>
                        <p class="app-card__sub">Laporkan kendala atau masalah pekerjaan lapangan kepada NOC</p>
                    </div>

                    <!-- Form -->
                    <form method="post" id="issueForm" class="form" action="<?= BASE_URL ?>controllers/schedules/issue_report.php">
                        
                        <!-- Hidden system inputs -->
                        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($id) ?>" />
                        <input type="hidden" name="issue_type" id="issue_type" value="" />

                        <div class="app-card__body">
                            
                            <!-- Schedule ID display badge -->
                            <div class="info-badge">
                                <div class="info-badge__icon-wrap">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <div class="info-badge__content">
                                    <p class="info-badge__label">Schedule ID</p>
                                    <p class="info-badge__value"><?= htmlspecialchars($id) ?></p>
                                </div>
                            </div>

                            <!-- Issue Type dropdown selector -->
                            <div class="form-group mb-4">
                                <label class="app-label" for="issue_type_select">Jenis Masalah (Issue Type)</label>
                                <div class="app-input-wrap">
                                    <select class="app-input" id="issue_type_select" required>
                                        <option value="">Pilih jenis masalah...</option>
                                        <?php foreach ($issues as $i): ?>
                                            <option value="<?= htmlspecialchars($i['catatan']) ?>">
                                                <?= htmlspecialchars($i['catatan']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Conditional other issue input field -->
                            <div class="dynamic-field" id="other_issue_field">
                                <div class="form-group mb-0">
                                    <label class="app-label" for="issue_type_other">Sebutkan Masalah Lainnya</label>
                                    <div class="app-input-wrap mb-0">
                                        <input type="text" id="issue_type_other" class="app-input" placeholder="Masukkan jenis kendala lainnya..." />
                                    </div>
                                </div>
                            </div>

                            <!-- Description textarea -->
                            <div class="form-group mb-0">
                                <label class="app-label" for="description">Deskripsi Kendala</label>
                                <div class="app-input-wrap mb-0">
                                    <textarea class="app-input app-textarea" id="description" required name="description" rows="4" 
                                        placeholder="Berikan detail penjelasan kendala yang dihadapi di lapangan..."></textarea>
                                </div>
                            </div>

                        </div>

                        <!-- Footer -->
                        <div class="app-card__footer">
                            <a href="<?= BASE_URL ?>pages/schedule/" class="btn-app-cancel">
                                <i class="fas fa-times mr-2" style="font-size: 0.85rem;"></i> Batal
                            </a>
                            <button type="submit" name="submit" class="btn-app-submit">
                                <i class="fas fa-paper-plane mr-2" style="font-size: 0.85rem;"></i> Laporkan
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectIssue  = document.getElementById('issue_type_select');
    const otherField   = document.getElementById('other_issue_field');
    const inputOther   = document.getElementById('issue_type_other');
    const hiddenIssue  = document.getElementById('issue_type');
    const form         = document.getElementById('issueForm');

    // Toggle other input field on dropdown change
    selectIssue.addEventListener('change', function() {
        if (this.value === 'Lainnya') {
            otherField.classList.add('show');
            inputOther.setAttribute('required', 'required');
            setTimeout(() => inputOther.focus(), 150);
        } else {
            otherField.classList.remove('show');
            inputOther.removeAttribute('required');
            inputOther.value = '';
        }
    });

    // Populate hidden field and validate before submitting
    form.addEventListener('submit', function(e) {
        if (selectIssue.value === 'Lainnya') {
            hiddenIssue.value = inputOther.value.trim();
        } else {
            hiddenIssue.value = selectIssue.value;
        }

        if (!hiddenIssue.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Tipe Masalah Kosong',
                text: 'Harap tentukan jenis masalah sebelum mengirim laporan.',
                confirmButtonText: 'Oke',
                customClass: {
                    confirmButton: 'btn btn-primary font-weight-bold'
                }
            });
            return false;
        }
    });
});
</script>

<?php
require __DIR__ . '/../../includes/footer.php';
?>