<?php

/**
 * api/registrasi_dashboard.php
 *
 * READ-ONLY endpoint khusus untuk Dashboard Registrasi.
 * Tidak mengubah, menimpa, atau menggantikan api/registrasi.php.
 * Tidak ada INSERT/UPDATE/DELETE di file ini — murni SELECT.
 *
 * Menerima (via $_POST['query'][...] , mengikuti konvensi KTDatatable
 * yang sudah dipakai di table.js untuk field generalSearch/status):
 *   - generalSearch  : string, sama seperti pencarian umum sebelumnya
 *   - status         : 'Verified' | 'Unverified' | ''
 *   - paket          : filter tambahan by paket_internet (untuk klik KPI "Paket Terlaris")
 *   - period         : 'today' | 'week' | 'month' | 'custom'  (default: 'today')
 *   - from, to       : dipakai hanya kalau period = 'custom', format Y-m-d
 *
 * Mengembalikan JSON:
 *   - data     : array baris register yang cocok dengan period + search + status + paket
 *                (dipakai untuk mengisi datatable & hitungan "X Data ditemukan")
 *   - summary  : total/verified/unverified/top_paket UNTUK PERIODE TERPILIH SAJA
 *                (sengaja tidak ikut ke-filter oleh search/status/paket, supaya
 *                angka di KPI card tetap representatif untuk periode tsb)
 *   - period   : { from, to } tanggal yang benar-benar dipakai (hasil resolve)
 */

require_once __DIR__ . '/../includes/config.php';
session_write_close();

header('Content-Type: application/json');

try {
    // ---------------- Input ----------------
    $search     = $_REQUEST['query']['generalSearch'] ?? '';
    $status     = $_REQUEST['query']['status'] ?? '';
    $paket      = $_REQUEST['query']['paket'] ?? '';
    $period     = $_REQUEST['query']['period'] ?? 'today';
    $customFrom = $_REQUEST['query']['from'] ?? '';
    $customTo   = $_REQUEST['query']['to'] ?? '';

    // ---------------- Resolve rentang tanggal dari periode ----------------
    $today = new DateTime('now');

    switch ($period) {
        case 'week':
            $from = (clone $today)->modify('monday this week')->format('Y-m-d');
            $to   = (clone $today)->modify('sunday this week')->format('Y-m-d');
            break;

        case 'month':
            $from = $today->format('Y-m-01');
            $to   = $today->format('Y-m-t');
            break;

        case 'custom':
            $fromObj = DateTime::createFromFormat('Y-m-d', $customFrom);
            $toObj   = DateTime::createFromFormat('Y-m-d', $customTo);
            $from    = $fromObj ? $fromObj->format('Y-m-d') : $today->format('Y-m-d');
            $to      = $toObj ? $toObj->format('Y-m-d') : $today->format('Y-m-d');
            break;

        case 'today':
        default:
            $from = $today->format('Y-m-d');
            $to   = $today->format('Y-m-d');
            break;
    }

    // Jaga-jaga kalau user pilih from > to, tukar posisinya
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    // ================= SUMMARY (KPI) — hanya difilter oleh periode =================
    $sumSql = "SELECT is_verified, paket_internet, COUNT(*) AS jumlah
               FROM register
               WHERE DATE(created_at) BETWEEN :from AND :to
               GROUP BY is_verified, paket_internet";
    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute([':from' => $from, ':to' => $to]);
    $sumRows = $sumStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRegistrasi = 0;
    $totalVerified   = 0;
    $totalUnverified = 0;
    $paketCount      = [];

    foreach ($sumRows as $r) {
        $jumlah = (int) $r['jumlah'];
        $totalRegistrasi += $jumlah;

        if ($r['is_verified'] === 'Verified') {
            $totalVerified += $jumlah;
        } else {
            $totalUnverified += $jumlah;
        }

        $p = $r['paket_internet'];
        $paketCount[$p] = ($paketCount[$p] ?? 0) + $jumlah;
    }

    $topPaket      = null;
    $topPaketCount = 0;
    foreach ($paketCount as $p => $jumlah) {
        if ($jumlah > $topPaketCount) {
            $topPaket      = $p;
            $topPaketCount = $jumlah;
        }
    }

    // ================= DATA TABEL — periode + search + status + paket =================
    $sql    = "SELECT * FROM register WHERE DATE(created_at) BETWEEN :from AND :to";
    $params = [':from' => $from, ':to' => $to];

    if (!empty($search)) {
        $sql .= " AND (
                    registrasi_id LIKE :search
                    OR name LIKE :search
                    OR location LIKE :search
                    OR phone LIKE :search
                    OR paket_internet LIKE :search
                    OR is_verified LIKE :search
                )";
        $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
        $sql .= " AND is_verified = :is_verified";
        $params[':is_verified'] = $status;
    }

    if (!empty($paket)) {
        $sql .= " AND paket_internet = :paket";
        $params[':paket'] = $paket;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data"    => $data,
        "summary" => [
            "total"           => $totalRegistrasi,
            "verified"        => $totalVerified,
            "unverified"      => $totalUnverified,
            "top_paket"       => $topPaket,
            "top_paket_count" => $topPaketCount,
        ],
        "period" => [
            "from" => $from,
            "to"   => $to,
        ],
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "error"   => true,
        "message" => $e->getMessage(),
    ]);
}
