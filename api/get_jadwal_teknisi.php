<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/config.php";

// Ambil input POST
$date    = isset($_POST['date']) ? $_POST['date'] : null;
$tech_id = isset($_POST['tech_id']) ? $_POST['tech_id'] : null;

// Kalau data tidak lengkap → return array kosong
if (!$date || !$tech_id) {
    echo json_encode([]);
    exit;
}

// Daftar jam kerja (default)
$jamKerja = array(
    "08:00",
    "09:00",
    "10:00",
    "11:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00"
);

try {
    // Ambil semua schedule untuk tech_id + date
    $sql = "SELECT * FROM schedules WHERE tech_id = ? AND date = ? ORDER BY time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($tech_id, $date));

    $jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil hanya kolom `time` untuk perhitungan jam kosong
    $jamTerpakai = array();
    foreach ($jadwal as $row) {
        $jamTerpakai[] = substr($row['time'], 0, 5);
    }

    // Hitung jam kosong = jam kerja - jam terpakai
    $jamKosong = array_values(array_diff($jamKerja, $jamTerpakai));

    // Kirim hasil dalam format JSON
    echo json_encode(array(
        "jadwal"    => $jadwal,   // semua data schedule
        "jamKosong" => $jamKosong // slot kosong
    ));
} catch (Exception $e) {
    echo json_encode(array(
        "error" => true,
        "message" => "Terjadi kesalahan: " . $e->getMessage()
    ));
}
?>
