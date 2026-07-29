<?php

require_once __DIR__ . "/../../../includes/config.php";

header('Content-Type: application/json');

try {
    // 1. Top Aduan Pelanggan (deskripsi_issue)
    $sqlAduan = "SELECT TRIM(deskripsi_issue) AS val, COUNT(*) AS cnt
                 FROM request_maintenance
                 WHERE deskripsi_issue IS NOT NULL AND TRIM(deskripsi_issue) <> ''
                 GROUP BY TRIM(deskripsi_issue)
                 ORDER BY cnt DESC, val ASC
                 LIMIT 20";
    $stmtAduan = $pdo->query($sqlAduan);
    $aduanList = $stmtAduan->fetchAll(PDO::FETCH_COLUMN);

    // 2. Top Verifikasi NOC (verifikasi_noc)
    $sqlNoc = "SELECT TRIM(verifikasi_noc) AS val, COUNT(*) AS cnt
               FROM request_maintenance
               WHERE verifikasi_noc IS NOT NULL AND TRIM(verifikasi_noc) <> ''
               GROUP BY TRIM(verifikasi_noc)
               ORDER BY cnt DESC, val ASC
               LIMIT 20";
    $stmtNoc = $pdo->query($sqlNoc);
    $nocList = $stmtNoc->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => true,
        'data' => [
            'aduan' => array_values(array_unique($aduanList)),
            'verifikasi' => array_values(array_unique($nocList))
        ]
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
