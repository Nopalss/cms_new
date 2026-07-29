<?php
/**
 * Migration Script for ALL 18,483 rows in jabbar23 (7).sql -> cms_database
 * Strict Mode: Matches ONLY official 15 technicians. Unmapped/legacy names are ignored.
 */

set_time_limit(0);
ini_set('memory_limit', '1024M');

$sqlPath = 'd:/laragon/www/cms/jabbar23 (7).sql';
$techPath = 'd:/laragon/www/cms/technician.sql';
$outputPath = 'd:/laragon/www/cms/migrated_jabbar23_data.sql';

function esc($str) {
    if ($str === null) return '';
    return addslashes((string)$str);
}

echo "Reading technician.sql...\n";
$techContent = file_get_contents($techPath);
preg_match_all("/\((\d+),\s*'([^']+)',\s*'([^']+)',\s*'([^']+)',\s*'([^']+)'(?:,\s*(?:NULL|'([^']*)'))?\)/", $techContent, $rows, PREG_SET_ORDER);

$officialTechs = [];
foreach ($rows as $r) {
    $techKey = (string)$r[1];
    $techId = $r[2];
    $name = strtoupper(trim($r[3]));
    $phone = $r[4];
    $username = strtolower(trim($r[5]));
    $timId = $r[6] ?? null;

    $officialTechs[$techKey] = [
        'tech_key' => $techKey,
        'tech_id' => $techId,
        'name' => $name,
        'phone' => $phone,
        'username' => $username,
        'tim_id' => $timId
    ];
}

$aliasMap = [
    'rendy' => ['FJI-020120009'],
    'rendi' => ['FJI-020120009'],
    'anggoro' => ['FJI-020122010'],
    'ahmad' => ['FJI-020120011'],
    'sunandar' => ['FJI-020120011'],
    'jujun' => ['FJI-020222017'],
    'hildan' => ['FJI-020422001'],
    'bahrul' => ['FJI-020119007'],
    'badru' => ['FJI-020119007'],
    'budi' => ['FJI-020119005'],
    'lili' => ['FJI-020123017'],
    'rafli' => ['FJI-020123018'],
    'ubaidillah' => ['FJI-020123019'],
    'sinta' => ['FJI-020124020'],
    'shintia' => ['FJI-020125021'],
    'danri' => ['FJI-020125022']
];

$techKeyToIdMap = [];
foreach ($officialTechs as $k => $t) {
    $techKeyToIdMap[$k] = $t['tech_id'];
}

function parseAndMatchPicStrict($rawPic, $officialTechs, $aliasMap, $techKeyToIdMap) {
    $rawPic = trim($rawPic);
    
    if ($rawPic === '' || $rawPic === '-' || strtolower($rawPic) === 'los' || preg_match('/^\d+.*dbm$/i', $rawPic)) {
        return [];
    }

    if (is_numeric($rawPic)) {
        $numKey = (string)intval($rawPic);
        if (isset($techKeyToIdMap[$numKey])) {
            return [$techKeyToIdMap[$numKey]];
        }
        return [];
    }

    $cleanText = strtolower($rawPic);
    $cleanText = preg_replace('/\([^)]*\)/', '', $cleanText);

    $parts = preg_split('/[\&\+\,\-\/\.\;]|\bdan\b|\bwith\b/i', $cleanText);
    $matchedTechIds = [];

    foreach ($parts as $p) {
        $p = trim($p);
        if (empty($p)) continue;
        
        $p = preg_replace('/^\d+\.\s*/', '', $p);
        $words = explode(' ', $p);

        if (in_array('rizki', $words) || in_array('riski', $words)) {
            $hasTeam1 = (bool)array_intersect(['anggoro', 'rendy', 'rendi', 'ahmad', 'sunandar'], $words);
            $hasTeam2 = (bool)array_intersect(['jujun', 'hildan', 'bahrul', 'badru'], $words);

            if ($hasTeam2 && !$hasTeam1) {
                $matchedTechIds[] = 'FJI-020222015'; // RIZKI NOVIANA RAMDANI
            } else {
                $matchedTechIds[] = 'FJI-020120013'; // RIZKI ALFIAN
            }
        }

        foreach ($words as $w) {
            $w = trim($w);
            if (empty($w) || $w === 'rizki' || $w === 'riski') continue;

            if (isset($aliasMap[$w])) {
                foreach ($aliasMap[$w] as $tid) {
                    $matchedTechIds[] = $tid;
                }
            }
        }
    }

    return array_values(array_unique($matchedTechIds));
}

echo "Reading jabbar23 (7).sql...\n";
$sqlContent = file_get_contents($sqlPath);

// Extract ALL ikrrecord INSERT blocks
preg_match_all('/INSERT INTO `ikrrecord` [^V]*VALUES\s*(.*?);/s', $sqlContent, $ikrMatches);
$ikrRows = [];
foreach ($ikrMatches[1] as $block) {
    preg_match_all('/\((?:[^()]+|\([^()]*\))*\)/s', $block, $tuples);
    foreach ($tuples[0] as $t) {
        $ikrRows[] = str_getcsv(trim($t, "() \r\n"));
    }
}

// Extract ALL service_record INSERT blocks
preg_match_all('/INSERT INTO `service_record` [^V]*VALUES\s*(.*?);/s', $sqlContent, $srvMatches);
$srvRows = [];
foreach ($srvMatches[1] as $block) {
    preg_match_all('/\((?:[^()]+|\([^()]*\))*\)/s', $block, $tuples);
    foreach ($tuples[0] as $t) {
        $srvRows[] = str_getcsv(trim($t, "() \r\n"));
    }
}

echo "Total IKR Rows found: " . count($ikrRows) . "\n";
echo "Total Service Rows found: " . count($srvRows) . "\n";

$outHandle = fopen($outputPath, "w");
fwrite($outHandle, "-- ========================================================\n");
fwrite($outHandle, "-- MIGRATED DATA FROM JABBAR23 TO CMS_DATABASE (STRICT OFFICIAL TECHS)\n");
fwrite($outHandle, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
fwrite($outHandle, "-- ========================================================\n\n");
fwrite($outHandle, "START TRANSACTION;\n\n");

// Clean up fake legacy technicians from technician table
fwrite($outHandle, "DELETE FROM `technician` WHERE `tech_id` LIKE 'FJI-LEGACY-%';\n\n");

$ikrInsertCount = 0;
$ikrPicInsertCount = 0;

fwrite($outHandle, "-- --------------------------------------------------------\n");
fwrite($outHandle, "-- Data for ikr_report and ikr_report_pic\n");
fwrite($outHandle, "-- --------------------------------------------------------\n");

foreach ($ikrRows as $row) {
    $ikrId = !empty($row[2]) ? trim($row[2], " '\"") : ("IKR" . sprintf("%07d", $row[0]));
    $netpayId = (!empty($row[1]) && $row[1] !== '-') ? trim($row[1], " '\"") : NULL;
    $date = !empty($row[3]) ? trim($row[3], " '\"") : date('Y-m-d H:i:s');
    $alamat = !empty($row[6]) ? trim($row[6], " '\"") : '';
    $rt = !empty($row[7]) ? trim($row[7], " '\"") : NULL;
    $rw = !empty($row[8]) ? trim($row[8], " '\"") : NULL;
    $desa = !empty($row[9]) ? trim($row[9], " '\"") : NULL;
    $kec = !empty($row[10]) ? trim($row[10], " '\"") : NULL;
    $kab = !empty($row[11]) ? trim($row[11], " '\"") : NULL;
    $sn = !empty($row[13]) ? trim($row[13], " '\"") : NULL;
    $typeOnt = !empty($row[15]) ? trim($row[15], " '\"") : NULL;
    $redaman = !empty($row[16]) ? trim($row[16], " '\"") : NULL;
    $odpNo = !empty($row[17]) ? trim($row[17], " '\"") : NULL;
    $odcNo = !empty($row[18]) ? trim($row[18], " '\"") : NULL;
    $jcNo = !empty($row[19]) ? trim($row[19], " '\"") : NULL;
    $macBef = !empty($row[20]) ? trim($row[20], " '\"") : NULL;
    $macAft = !empty($row[21]) ? trim($row[21], " '\"") : NULL;
    $odp = !empty($row[22]) ? trim($row[22], " '\"") : NULL;
    $odc = !empty($row[23]) ? trim($row[23], " '\"") : NULL;
    $enclosure = !empty($row[24]) ? trim($row[24], " '\"") : NULL;

    $netpayVal = $netpayId ? "'" . esc($netpayId) . "'" : "NULL";

    $sql = sprintf(
        "INSERT IGNORE INTO `ikr_report` (`ikr_id`, `netpay_id`, `alamat`, `rt`, `rw`, `desa`, `kec`, `kab`, `sn`, `type_ont`, `redaman`, `odp_no`, `odc_no`, `jc_no`, `mac_sebelum`, `mac_sesudah`, `odp`, `odc`, `enclosure`, `created_at`, `updated_at`) VALUES ('%s', %s, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\n",
        esc($ikrId), $netpayVal, esc($alamat), esc($rt), esc($rw), esc($desa), esc($kec), esc($kab), esc($sn), esc($typeOnt), esc($redaman), esc($odpNo), esc($odcNo), esc($jcNo), esc($macBef), esc($macAft), esc($odp), esc($odc), esc($enclosure), esc($date), esc($date)
    );
    fwrite($outHandle, $sql);
    $ikrInsertCount++;

    $groupIkr = !empty($row[4]) ? trim($row[4], " '\"") : '';
    $matchedTechs = parseAndMatchPicStrict($groupIkr, $officialTechs, $aliasMap, $techKeyToIdMap);

    foreach ($matchedTechs as $tid) {
        $picSql = sprintf(
            "INSERT IGNORE INTO `ikr_report_pic` (`ikr_id`, `tech_id`) VALUES ('%s', '%s');\n",
            esc($ikrId), esc($tid)
        );
        fwrite($outHandle, $picSql);
        $ikrPicInsertCount++;
    }
}

$srvInsertCount = 0;
$srvPicInsertCount = 0;

fwrite($outHandle, "\n-- --------------------------------------------------------\n");
fwrite($outHandle, "-- Data for service_reports and service_report_pic\n");
fwrite($outHandle, "-- --------------------------------------------------------\n");

foreach ($srvRows as $row) {
    $srvId = !empty($row[1]) ? trim($row[1], " '\"") : ("SRV" . sprintf("%07d", $row[0]));
    $netpayId = (!empty($row[5]) && $row[5] !== '-') ? trim($row[5], " '\"") : NULL;
    $timestamp = !empty($row[2]) ? trim($row[2], " '\"") : date('Y-m-d H:i:s');
    
    $rawDate = !empty($row[3]) ? trim($row[3], " '\"") : '';
    $parsedDate = date('Y-m-d', strtotime($rawDate));
    if ($parsedDate === '1970-01-01' || !$parsedDate) {
        $parsedDate = date('Y-m-d', strtotime($timestamp));
    }
    
    $jam = !empty($row[4]) ? trim($row[4], " '\"") : '00:00:00';
    $problem = !empty($row[8]) ? trim($row[8], " '\"") : '';
    $action = !empty($row[9]) ? trim($row[9], " '\"") : '';
    $part = !empty($row[10]) ? trim($row[10], " '\"") : '';
    $redBef = !empty($row[11]) ? trim($row[11], " '\"") : '';
    $redAft = !empty($row[12]) ? trim($row[12], " '\"") : '';
    $keterangan = !empty($row[14]) ? trim($row[14], " '\"") : '';

    $netpayVal = $netpayId ? "'" . esc($netpayId) . "'" : "NULL";

    $sql = sprintf(
        "INSERT IGNORE INTO `service_reports` (`srv_id`, `tanggal`, `jam`, `netpay_id`, `problem`, `action`, `part`, `ont_bef`, `ont_aft`, `red_bef`, `red_aft`, `keterangan`, `created_at`, `updated_at`) VALUES ('%s', '%s', '%s', %s, '%s', '%s', '%s', '', '', '%s', '%s', '%s', '%s', '%s');\n",
        esc($srvId), esc($parsedDate), esc($jam), $netpayVal, esc($problem), esc($action), esc($part), esc($redBef), esc($redAft), esc($keterangan), esc($timestamp), esc($timestamp)
    );
    fwrite($outHandle, $sql);
    $srvInsertCount++;

    $rawPic = !empty($row[13]) ? trim($row[13], " '\"") : '';
    $matchedTechs = parseAndMatchPicStrict($rawPic, $officialTechs, $aliasMap, $techKeyToIdMap);

    foreach ($matchedTechs as $tid) {
        $picSql = sprintf(
            "INSERT IGNORE INTO `service_report_pic` (`srv_id`, `tech_id`) VALUES ('%s', '%s');\n",
            esc($srvId), esc($tid)
        );
        fwrite($outHandle, $picSql);
        $srvPicInsertCount++;
    }
}

fwrite($outHandle, "\nCOMMIT;\n");
fclose($outHandle);

echo "=======================================================\n";
echo "           STRICT MIGRATION COMPLETED                 \n";
echo "=======================================================\n";
echo "Output SQL File                 : $outputPath\n";
echo "Official Technicians Preserved  : " . count($officialTechs) . "\n";
echo "IKR Reports Migrated            : $ikrInsertCount / " . count($ikrRows) . "\n";
echo "IKR Report PIC Links            : $ikrPicInsertCount\n";
echo "Service Reports Migrated        : $srvInsertCount / " . count($srvRows) . "\n";
echo "Service Report PIC Links        : $srvPicInsertCount\n";
echo "=======================================================\n";
