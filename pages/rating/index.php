<?php

/**
 * rating.php — Halaman publik untuk customer ngisi rating
 * Taruh di root project (setara index.php / config.php)
 * Akses: BASE_URL/rating.php?token=xxx
 * Tidak butuh login
 */
require_once __DIR__ . '/../../includes/config.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// ── Ambil data rating berdasarkan token ──────────────────────────
$data = null;
if ($token !== '') {
    $stmt = $pdo->prepare("
        SELECT
            tr.*,
            c.name  AS customer_name,
            s.job_type,
            s.date  AS jadwal_date
        FROM technician_ratings tr
        JOIN customers  c ON tr.netpay_id   = c.netpay_id
        JOIN schedules  s ON tr.schedule_id = s.schedule_id
        WHERE tr.token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

$invalid      = ($data === null || $data === false);
$already_rated = (!$invalid && $data['status'] === 'Rated');
$error_msg    = '';
$success      = false;

// ── Handle POST (submit rating) ──────────────────────────────────
// ── Handle POST (submit rating) ──────────────────────────────────
if (!$invalid && !$already_rated && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating   = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';

    if ($rating < 1 || $rating > 5) {

        $error_msg = 'Pilih bintang rating terlebih dahulu (1-5).';
    } else {

        try {

            $upd = $pdo->prepare("
                UPDATE technician_ratings
                SET
                    rating   = :rating,
                    komentar = :komentar,
                    status   = 'Rated'
                WHERE token = :token
            ");

            $upd->execute([
                ':rating'   => $rating,
                ':komentar' => $komentar,
                ':token'    => $token
            ]);

            $already_rated = true;
            $success = true;
        } catch (Exception $e) {

            $error_msg = $e->getMessage();
        }
    }
}

// ── Helpers ──────────────────────────────────────────────────────
$job_label = [
    'Instalasi'   => '📡 Instalasi',
    'Service'     => '🔧 Service',
    'Dismantle'   => '🔌 Dismantle',
    'Maintenance' => '🛠️ Maintenance',
];

function fmt_date_id($date)
{
    $bulan = [
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $parts = explode('-', $date);
    if (count($parts) !== 3) return $date;
    return (int)$parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Penilaian Layanan – JABBAR23</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F0F4FA;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px 14px 40px;
        }

        .rating-wrap {
            width: 100%;
            max-width: 440px;
        }

        /* Header */
        .rating-header {
            background: linear-gradient(135deg, #1E3A8A, #2563EB);
            border-radius: 16px;
            padding: 22px 20px;
            color: #fff;
            text-align: center;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }

        .rating-header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -30px;
            width: 130px;
            height: 130px;
            background: rgba(255, 255, 255, .07);
            border-radius: 50%;
        }

        .brand-tag {
            display: inline-block;
            background: rgba(255, 255, 255, .15);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }

        .rating-header h1 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -.4px;
            margin: 0;
        }

        .rating-header p {
            font-size: 12px;
            opacity: .8;
            margin: 5px 0 0;
        }

        /* Info card */
        .info-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
        }

        .info-stripe {
            height: 4px;
            display: block;
            background: linear-gradient(90deg, #F59E0B, #FBBF24);
        }

        .info-body {
            padding: 16px;
        }

        .info-tech {
            font-size: 18px;
            font-weight: 900;
            color: #0F172A;
            margin-bottom: 6px;
        }

        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #F1F5F9;
            border-radius: 20px;
            padding: 4px 11px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin: 2px 2px 2px 0;
        }

        /* Star rating */
        .star-group {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 16px 0 8px;
        }

        .star-group input[type="radio"] {
            display: none;
        }

        .star-group label {
            font-size: 40px;
            cursor: pointer;
            color: #CBD5E1;
            transition: color .15s, transform .1s;
            line-height: 1;
        }

        .star-group label:hover,
        .star-group label:hover~label {
            color: #F59E0B;
        }

        /* reverse trick untuk CSS-only highlight */
        .star-group {
            flex-direction: row-reverse;
            justify-content: center;
        }

        .star-group input:checked~label,
        .star-group input:checked~label~label {
            color: #F59E0B;
        }

        .star-group label:hover,
        .star-group input:checked~label:hover,
        .star-group input:checked~label:hover~label,
        .star-group label:hover~input:checked~label {
            color: #FBBF24;
        }

        .star-group label:active {
            transform: scale(.9);
        }

        .star-hint {
            text-align: center;
            font-size: 12px;
            color: #94A3B8;
            font-weight: 600;
            min-height: 18px;
        }

        /* Form card */
        .form-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
            margin-bottom: 14px;
        }

        .form-card h6 {
            font-size: 13px;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 14px;
        }

        .sr-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: .5px;
            display: block;
            margin-bottom: 6px;
        }

        .sr-control {
            border-radius: 10px;
            border: 1.5px solid #E2E8F0;
            background: #F8FAFF;
            font-size: 14px;
            width: 100%;
            transition: border-color .15s, box-shadow .15s;
        }

        .sr-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
            background: #fff;
            outline: none;
        }

        .sr-control.is-invalid {
            border-color: #EF4444 !important;
            background: #FFF5F5 !important;
        }

        textarea.sr-control {
            min-height: 90px;
            padding-top: 12px;
            resize: none;
        }

        /* Submit btn */
        .btn-submit-rating {
            background: linear-gradient(135deg, #2563EB, #3B82F6);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            padding: 15px;
            width: 100%;
            box-shadow: 0 4px 14px rgba(37, 99, 235, .3);
            transition: opacity .15s;
        }

        .btn-submit-rating:hover {
            opacity: .9;
            color: #fff;
        }

        .btn-submit-rating:disabled {
            opacity: .7;
            cursor: not-allowed;
        }

        /* Success state */
        .success-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
        }

        .success-ico {
            font-size: 56px;
            margin-bottom: 14px;
        }

        .success-title {
            font-size: 22px;
            font-weight: 900;
            color: #0F172A;
            margin-bottom: 8px;
        }

        .success-sub {
            font-size: 14px;
            color: #64748B;
        }

        /* Invalid / error page */
        .error-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
        }
    </style>
</head>

<body>
    <div class="rating-wrap">

        <!-- Header -->
        <div class="rating-header">
            <span class="brand-tag">⭐ JABBAR23</span>
            <h1>Penilaian Layanan</h1>
            <p>Bantu kami terus meningkatkan kualitas</p>
        </div>

        <?php if ($invalid): ?>
            <!-- ── TOKEN TIDAK VALID ── -->
            <div class="error-card">
                <div style="font-size:52px;margin-bottom:12px">😕</div>
                <h5 style="font-weight:900;color:#0F172A">Link Tidak Valid</h5>
                <p style="font-size:13px;color:#64748B">
                    Link penilaian ini tidak ditemukan atau sudah kadaluarsa.<br>
                    Hubungi teknisi jika ada kendala.
                </p>
            </div>

        <?php elseif ($already_rated): ?>
            <!-- ── SUDAH DINILAI ── -->
            <div class="success-card">
                <?php if ($success): ?>
                    <div class="success-ico">🎉</div>
                    <div class="success-title">Terima Kasih!</div>
                    <p class="success-sub">
                        Penilaian Anda telah berhasil dikirim.<br>
                        Kami akan terus meningkatkan pelayanan.
                    </p>
                <?php else: ?>
                    <div class="success-ico">✅</div>
                    <div class="success-title">Sudah Dinilai</div>
                    <p class="success-sub">
                        Anda sudah memberikan penilaian sebelumnya.<br>
                        Terima kasih atas kepercayaan Anda kepada JABBAR23.
                    </p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ── FORM RATING ── -->

            <!-- Info teknisi & pekerjaan -->
            <div class="info-card">
                <span class="info-stripe"></span>
                <div class="info-body">
                    <div class="info-tech">👨‍🔧 Penilaian Layanan Teknisi</div>
                    <div>
                        <span class="info-chip">
                            <?= isset($job_label[$data['job_type']]) ? $job_label[$data['job_type']] : $data['job_type'] ?>
                        </span>
                        <span class="info-chip">
                            📅 <?= fmt_date_id($data['jadwal_date']) ?>
                        </span>
                    </div>
                    <div class="mt-2" style="font-size:12px;color:#94A3B8;font-weight:600">
                        Halo, <?= htmlspecialchars($data['customer_name']) ?> 👋
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form method="post" id="ratingForm" novalidate>

                <div class="form-card">
                    <h6>⭐ Berikan Bintang Anda</h6>

                    <!-- Star rating — CSS only + JS untuk hint -->
                    <div class="star-group" id="starGroup">
                        <input type="radio" name="rating" id="s5" value="5">
                        <label for="s5" title="Sangat Puas">★</label>
                        <input type="radio" name="rating" id="s4" value="4">
                        <label for="s4" title="Puas">★</label>
                        <input type="radio" name="rating" id="s3" value="3">
                        <label for="s3" title="Cukup">★</label>
                        <input type="radio" name="rating" id="s2" value="2">
                        <label for="s2" title="Kurang">★</label>
                        <input type="radio" name="rating" id="s1" value="1">
                        <label for="s1" title="Sangat Kurang">★</label>
                    </div>
                    <div class="star-hint" id="starHint">Ketuk bintang untuk memberi nilai</div>

                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger mt-2 py-2" style="border-radius:10px;font-size:13px;font-weight:600">
                            ⚠️ <?= htmlspecialchars($error_msg) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-card">
                    <h6>💬 Komentar <span style="font-weight:500;color:#94A3B8;font-size:12px">(opsional)</span></h6>
                    <label class="sr-label">Ceritakan pengalaman Anda</label>
                    <textarea class="form-control sr-control w-100" name="komentar" rows="4"
                        placeholder="Teknisi ramah, pekerjaan cepat selesai..."><?= htmlspecialchars($_POST['komentar'] ?? '') ?></textarea>
                </div>

                <button type="submit" id="btnSubmitRating" class="btn-submit-rating mb-2">
                    Kirim Penilaian
                </button>

                <p style="text-align:center;font-size:11px;color:#94A3B8;margin-top:8px">
                    Penilaian bersifat anonim dan hanya digunakan untuk evaluasi internal.
                </p>

            </form>
        <?php endif; ?>

    </div>

    <script>
        // Star hint labels
        var hints = {
            '1': 'Sangat Kurang 😞',
            '2': 'Kurang 😕',
            '3': 'Cukup 😐',
            '4': 'Puas 😊',
            '5': 'Sangat Puas 🤩'
        };

        document.querySelectorAll('input[name="rating"]').forEach(function(el) {
            el.addEventListener('change', function() {
                document.getElementById('starHint').textContent = hints[this.value] || '';
            });
        });

        document.getElementById('ratingForm') && document.getElementById('ratingForm').addEventListener('submit', function(e) {
            var checked = document.querySelector('input[name="rating"]:checked');
            if (!checked) {
                e.preventDefault();
                document.getElementById('starHint').textContent = '⚠️ Pilih bintang dulu ya!';
                document.getElementById('starHint').style.color = '#EF4444';
                return;
            }
            var btn = document.getElementById('btnSubmitRating');
            btn.disabled = true;
            btn.textContent = 'Mengirim...';
        });
    </script>
</body>

</html>