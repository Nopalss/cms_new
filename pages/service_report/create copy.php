<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../helper/redirect.php';

$_SESSION['menu'] = 'service';

try {

    $id = $_POST['id'] ?? null;
    if (!$id) redirect("pages/service_report/");

    $stmt = $pdo->prepare("
        SELECT s.schedule_key,s.schedule_id,s.tech_id,s.netpay_key,c.*
        FROM schedules s
        JOIN customers c ON s.netpay_key=c.netpay_key
        WHERE s.schedule_key=:id
    ");
    $stmt->execute([':id' => $id]);

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception();

    $srv_id = "SR" . date("YmdHis");

    $isTim = strpos($customer['tech_id'], 'TIM') === 0;
    $teamMembers = [];

    if ($isTim) {
        $q = $pdo->prepare("SELECT tech_id,name FROM technician WHERE tim_id=:id");
        $q->execute([':id' => $customer['tech_id']]);
        $teamMembers = $q->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // 🔥 FIX SINGLE TECH
        $q = $pdo->prepare("SELECT tech_id,name FROM technician WHERE tech_id=:id");
        $q->execute([':id' => $customer['tech_id']]);
        $single = $q->fetch(PDO::FETCH_ASSOC);
        if ($single) {
            $teamMembers = [$single];
        }
    }
} catch (Throwable $e) {
    redirect("pages/service_report/");
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/../../includes/aside.php';
require __DIR__ . '/../../includes/navbar.php';
?>

<style>
    .mobile-card {
        border-radius: 14px
    }

    .section-title {
        font-weight: 600;
        margin: 20px 0 10px
    }

    input,
    textarea {
        min-height: 48px
    }

    textarea {
        padding-top: 12px
    }

    .pic-pill {
        display: block;
        width: 100%;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 8px;
        text-align: center;
    }

    .pic-box input:checked+label {
        background: #3699ff;
        color: white;
        border-color: #3699ff
    }

    details summary {
        font-weight: 600
    }

    @media(min-width:992px) {
        .pic-pill {
            width: auto;
            display: inline-block
        }
    }
</style>

<div class="content">
    <div class="container px-3">

        <form id="formReport" method="post" action="<?= BASE_URL ?>controllers/report/service/create.php">

            <input type="hidden" name="srv_id" value="<?= $srv_id ?>">
            <input type="hidden" name="schedule_key" value="<?= $customer['schedule_key'] ?>">
            <input type="hidden" name="netpay_key" value="<?= $customer['netpay_key'] ?>">

            <div class="card mobile-card shadow-sm" id="captureForm">
                <div class="card-body">

                    <h4 class="mb-3 text-center">Service Report</h4>

                    <input class="form-control mb-3" value="<?= $srv_id ?>" disabled>

                    <div class="row">
                        <div class="col-6">
                            <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required readonly>
                        </div>
                        <div class="col-6">
                            <input type="time" class="form-control" name="jam" value="<?= date('H:i') ?>" required readonly>
                        </div>
                    </div>

                    <label class="mt-3">Problem</label>
                    <textarea class="form-control" name="problem" required></textarea>

                    <label class="mt-3">Action</label>
                    <textarea class="form-control" name="action" required></textarea>

                    <label class="mt-3">Part</label>
                    <textarea class="form-control" name="part" required></textarea>

                    <div class="row mt-3">
                        <div class="col-6">
                            <label>ONT Lama</label>
                            <input class="form-control" name="ont_bef" required>
                        </div>
                        <div class="col-6">
                            <label>ONT Baru (Opsional)</label>
                            <input class="form-control" name="ont_aft">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-6">
                            <label>Red Sebelum</label>
                            <input class="form-control" name="red_bef" required>
                        </div>
                        <div class="col-6">
                            <label>Red Sesudah</label>
                            <input class="form-control" name="red_aft" required>
                        </div>
                    </div>

                    <div class="section-title">PIC</div>

                    <div class="pic-box">
                        <?php if ($isTim): ?>
                            <?php foreach ($teamMembers as $tm): ?>
                                <input hidden checked type="checkbox" name="pic[]" id="<?= $tm['tech_id'] ?>" value="<?= $tm['tech_id'] ?>">
                                <label class="pic-pill" for="<?= $tm['tech_id'] ?>"><?= $tm['name'] ?></label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input class="form-control" readonly name="pic[]" value="<?= $customer['tech_id'] ?>">
                        <?php endif; ?>
                    </div>

                    <label class="mt-3">Keterangan</label>
                    <textarea class="form-control" name="keterangan" required></textarea>

                    <details class="mt-3">
                        <summary>Customer Info</summary>
                        <div class="mt-2">
                            <b><?= $customer['name'] ?></b><br>
                            <?= $customer['phone'] ?><br>
                            <?= $customer['paket_internet'] ?> Mbps<br>
                            <?= $customer['location'] ?>
                        </div>
                    </details>

                    <button type="button" onclick="takeScreenshot()" class="btn btn-info btn-block mt-2">
                        <i class="fa fa-camera"></i> Screenshot Report
                    </button>

                    <button type="button" onclick="submitAndShare()" class="btn btn-primary btn-block mt-4">
                        Submit & Share
                    </button>

                </div>
            </div>
        </form>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    const TECHNICIAN_MAP = <?= json_encode(
                                array_reduce($teamMembers, function ($carry, $item) {
                                    $carry[$item['tech_id']] = $item['name'];
                                    return $carry;
                                }, [])
                            ); ?>;
</script>

<script>
    function takeScreenshot() {
        const element = document.getElementById("captureForm");

        html2canvas(element, {
            scale: 2
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'service-report.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    function formatTanggal(tgl) {
        const bulan = [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        const date = new Date(tgl);
        const hari = date.getDate();
        const namaBulan = bulan[date.getMonth()];
        const tahun = date.getFullYear();

        return `${hari} ${namaBulan} ${tahun}`;
    }
    async function submitAndShare() {

        const form = document.getElementById("formReport");
        const url = form.getAttribute("action");
        const formData = new FormData(form);

        // 🔥 ambil nama teknisi
        const picIds = formData.getAll('pic[]');
        const teknisiNames = picIds.map(id => TECHNICIAN_MAP[id] || id);
        const teknisiText = teknisiNames.join(', ');

        try {
            const res = await fetch(url, {
                method: "POST",
                body: formData
            });

            const result = await res.json();

            if (result.status) {


                let text = `📄 *SERVICE REPORT*
ID: ${formData.get('srv_id')}

━━━━━━━━━━━━━━━━━━
🕒 *Waktu*
${formatTanggal(formData.get('tanggal'))} • ${formData.get('jam')}

━━━━━━━━━━━━━━━━━━
⚙️ *Detail*
Problem  : ${formData.get('problem')}
Action   : ${formData.get('action')}
Part     : ${formData.get('part')}
ONT      : ${formData.get('ont_bef')} ${formData.get('ont_aft') ? '→ ' + formData.get('ont_aft') : ''}
Redaman  :
  Sebelum : ${formData.get('red_bef')}
  Sesudah : ${formData.get('red_aft')}

━━━━━━━━━━━━━━━━━━
📝 *Keterangan*
${formData.get('keterangan')}

━━━━━━━━━━━━━━━━━━
👤 *Customer*
Nama    : <?= $customer['name'] ?>
No HP   : <?= $customer['phone'] ?>
Netpay  : <?= $customer['netpay_id'] ?>
Alamat  : <?= $customer['perumahan'] ?> <?= $customer['location'] ?>

━━━━━━━━━━━━━━━━━━
🛠️ *Teknisi*
${teknisiText}
━━━━━━━━━━━━━━━━━━`;
                if (navigator.share) {
                    await navigator.share({
                        title: "Service Report",
                        text: text
                    });
                } else {
                    window.open("https://wa.me/?text=" + encodeURIComponent(text));
                }

                window.location.href = "<?= BASE_URL ?>pages/service_report/";

            } else {
                alert(result.message);
            }

        } catch (e) {
            console.error(e);
            alert("Error submit");
        }
    }
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>