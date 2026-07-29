-- =====================================================================
-- SEED DATA SAW - STRUKTUR TIM + SOLO (SESUAI SKEMA ASLI)
-- PT FAKTA JABAR INDUSTRI - JTRACKS
-- =====================================================================
-- Menggunakan tabel yang BENAR: technician_ratings + detail_ratings
-- (bukan rating_details). Durasi dalam DETIK sesuai kode terbaru.
--
-- STRUKTUR DATA:
--   4 job TIM  -> dikerjakan bersama oleh Rendy+Anggoro+Ahmad+Rizki,
--                 sehingga C1/C2/C3 dari job ini IDENTIK untuk keempatnya
--                 (1 dari 4 job tim sengaja tanpa rating).
--   6 job SOLO per teknisi (24 total) -> di sinilah pembeda kinerja
--                 individual muncul.
--   Total per teknisi = 4 + 6 = 10 job.
--
-- AMAN dijalankan berapa kali pun ke database MANAPUN, karena TIDAK ADA
-- primary key auto-increment yang di-hardcode - semua ID unik yang
-- dipakai untuk relasi (schedule_id, srv_id, dismantle_id, ikr_id,
-- rating_id) adalah string yang kita tentukan sendiri, bukan angka
-- auto-increment dari MySQL. Auto-increment (schedule_key, srv_key,
-- dr_id, rating_key, dst) dibiarkan MySQL yang isi otomatis karena
-- TIDAK dipakai untuk join di kode report.php versi ini.
--
-- PERIODE DATA: 26 Juni - 3 Juli 2026
-- =====================================================================

START TRANSACTION;

-- ============================================================
-- TEAM JOBS (4) - dikerjakan oleh Rendy+Anggoro+Ahmad+Rizki bersama
-- ============================================================
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S20260726090000','TIM20260225115126','2026-06-26','09:00:00','2026-06-26 09:00:00','2026-06-26 09:50:00','Instalasi','Done',NULL,'Job tim - Instalasi');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI20260726090000','201000174','Perumahan Asri Blok T09','3','10','Meranti','Cikarang','Bekasi','ZTE090000','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:09:00:00','ODP-05','ODC-02','EN-01','2026-06-26 09:50:00','2026-06-26 09:50:00','S20260726090000');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260726090000','FJI-020120009');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260726090000','FJI-020122010');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260726090000','FJI-020120011');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260726090000','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTGTEAM0626090000','S20260726090000','201000174','rtgteam0626090000token',4,'','Y','2026-06-26 09:50:00','2026-06-26 09:50:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTGTEAM0626090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTGTEAM0626090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTGTEAM0626090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTGTEAM0626090000');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S20260728090000','TIM20260225115126','2026-06-28','09:00:00','2026-06-28 09:00:00','2026-06-28 09:40:00','Dismantle','Done',NULL,'Job tim - Dismantle');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR20260728090000','S20260728090000','201000139','2026-06-28','09:40:00','Pindah Alamat','Penanganan bersama tim','ONT','Baik','Selesai','2026-06-28 09:40:00','2026-06-28 09:40:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR20260728090000','FJI-020120009');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR20260728090000','FJI-020122010');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR20260728090000','FJI-020120011');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR20260728090000','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTGTEAM0628090000','S20260728090000','201000139','rtgteam0628090000token',5,'','Y','2026-06-28 09:40:00','2026-06-28 09:40:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTGTEAM0628090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTGTEAM0628090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTGTEAM0628090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTGTEAM0628090000');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S20260730090000','TIM20260225115126','2026-06-30','09:00:00','2026-06-30 09:00:00','2026-06-30 09:35:00','Service','Done',NULL,'Job tim - Service');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR20260730090000','2026-06-30','09:35:00','201000140','Pekerjaan tim','Penanganan bersama tim','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-30 09:35:00','2026-06-30 09:35:00','S20260730090000');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR20260730090000','FJI-020120009');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR20260730090000','FJI-020122010');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR20260730090000','FJI-020120011');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR20260730090000','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTGTEAM0630090000','S20260730090000','201000140','rtgteam0630090000token',4,'','Y','2026-06-30 09:35:00','2026-06-30 09:35:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTGTEAM0630090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTGTEAM0630090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTGTEAM0630090000');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTGTEAM0630090000');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S20260702090000','TIM20260225115126','2026-07-02','09:00:00','2026-07-02 09:00:00','2026-07-02 09:55:00','Instalasi','Done',NULL,'Job tim - Instalasi');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI20260702090000','201000142','Perumahan Asri Blok T09','3','10','Meranti','Cikarang','Bekasi','ZTE090000','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:09:00:00','ODP-05','ODC-02','EN-01','2026-07-02 09:55:00','2026-07-02 09:55:00','S20260702090000');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260702090000','FJI-020120009');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260702090000','FJI-020122010');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260702090000','FJI-020120011');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI20260702090000','FJI-020120013');
-- (job tim S20260702090000 sengaja TANPA rating - pelanggan belum menilai)

-- ============================================================
-- SOLO JOBS - RENDY (FJI-020120009)
-- ============================================================
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260626100009','FJI-020120009','2026-06-26','10:00:00','2026-06-26 10:00:00','2026-06-26 10:25:00','Service','Done',NULL,'Solo job rendy #1');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260626100009','2026-06-26','10:25:00','201000143','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-26 10:25:00','2026-06-26 10:25:00','S260626100009');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260626100009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260626100009','S260626100009','201000143','rtg260626100009token',5,'','Y','2026-06-26 10:25:00','2026-06-26 10:25:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260626100009');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260627090009','FJI-020120009','2026-06-27','09:00:00','2026-06-27 09:00:00','2026-06-27 09:28:00','Instalasi','Done',NULL,'Solo job rendy #2');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260627090009','201000144','Perumahan Asri Blok R2','3','10','Meranti','Cikarang','Bekasi','ZTE000009','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:09','ODP-05','ODC-02','EN-01','2026-06-27 09:28:00','2026-06-27 09:28:00','S260627090009');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260627090009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260627090009','S260627090009','201000144','rtg260627090009token',5,'','Y','2026-06-27 09:28:00','2026-06-27 09:28:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260627090009');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260628130009','FJI-020120009','2026-06-28','13:00:00','2026-06-28 13:00:00','2026-06-28 13:22:00','Dismantle','Done',NULL,'Solo job rendy #3');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260628130009','S260628130009','201000149','2026-06-28','13:22:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-06-28 13:22:00','2026-06-28 13:22:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260628130009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260628130009','S260628130009','201000149','rtg260628130009token',4,'','Y','2026-06-28 13:22:00','2026-06-28 13:22:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260628130009');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260629080009','FJI-020120009','2026-06-29','08:00:00','2026-06-29 08:00:00','2026-06-29 08:30:00','Service','Done',NULL,'Solo job rendy #4');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260629080009','2026-06-29','08:30:00','201000164','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-29 08:30:00','2026-06-29 08:30:00','S260629080009');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260629080009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260629080009','S260629080009','201000164','rtg260629080009token',5,'','Y','2026-06-29 08:30:00','2026-06-29 08:30:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260629080009');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260630100009','FJI-020120009','2026-06-30','10:00:00','2026-06-30 10:00:00','2026-06-30 10:26:00','Instalasi','Done',NULL,'Solo job rendy #5');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260630100009','201000165','Perumahan Asri Blok R5','3','10','Meranti','Cikarang','Bekasi','ZTE000009','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:09','ODP-05','ODC-02','EN-01','2026-06-30 10:26:00','2026-06-30 10:26:00','S260630100009');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260630100009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260630100009','S260630100009','201000165','rtg260630100009token',5,'','Y','2026-06-30 10:26:00','2026-06-30 10:26:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260630100009');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260701090009','FJI-020120009','2026-07-01','09:00:00','2026-07-01 09:00:00','2026-07-01 09:24:00','Dismantle','Done',NULL,'Solo job rendy #6');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260701090009','S260701090009','201000166','2026-07-01','09:24:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-07-01 09:24:00','2026-07-01 09:24:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260701090009','FJI-020120009');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260701090009','S260701090009','201000166','rtg260701090009token',4,'','Y','2026-07-01 09:24:00','2026-07-01 09:24:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120009','RTG260701090009');

-- ============================================================
-- SOLO JOBS - ANGGORO (FJI-020122010)
-- ============================================================
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26062708002010','FJI-020122010','2026-06-27','08:00:00','2026-06-27 08:00:00','2026-06-27 08:35:00','Instalasi','Done',NULL,'Solo job anggoro #1');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI26062708002010','201000167','Perumahan Asri Blok A1','3','10','Meranti','Cikarang','Bekasi','ZTE002010','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:20:10','ODP-05','ODC-02','EN-01','2026-06-27 08:35:00','2026-06-27 08:35:00','S26062708002010');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI26062708002010','FJI-020122010');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG26062708002010','S26062708002010','201000167','rtg26062708002010token',5,'','Y','2026-06-27 08:35:00','2026-06-27 08:35:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTG26062708002010');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26062810002010','FJI-020122010','2026-06-28','10:00:00','2026-06-28 10:00:00','2026-06-28 10:38:00','Service','Done',NULL,'Solo job anggoro #2');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR26062810002010','2026-06-28','10:38:00','201000169','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-28 10:38:00','2026-06-28 10:38:00','S26062810002010');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR26062810002010','FJI-020122010');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG26062810002010','S26062810002010','201000169','rtg26062810002010token',4,'','Y','2026-06-28 10:38:00','2026-06-28 10:38:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTG26062810002010');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26062909002010','FJI-020122010','2026-06-29','09:00:00','2026-06-29 09:00:00','2026-06-29 09:33:00','Dismantle','Done',NULL,'Solo job anggoro #3');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR26062909002010','S26062909002010','201000171','2026-06-29','09:33:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-06-29 09:33:00','2026-06-29 09:33:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR26062909002010','FJI-020122010');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG26062909002010','S26062909002010','201000171','rtg26062909002010token',5,'','Y','2026-06-29 09:33:00','2026-06-29 09:33:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTG26062909002010');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26063013002010','FJI-020122010','2026-06-30','13:00:00','2026-06-30 13:00:00','2026-06-30 13:40:00','Instalasi','Done',NULL,'Solo job anggoro #4');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI26063013002010','201000172','Perumahan Asri Blok A4','3','10','Meranti','Cikarang','Bekasi','ZTE002010','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:20:10','ODP-05','ODC-02','EN-01','2026-06-30 13:40:00','2026-06-30 13:40:00','S26063013002010');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI26063013002010','FJI-020122010');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG26063013002010','S26063013002010','201000172','rtg26063013002010token',4,'','Y','2026-06-30 13:40:00','2026-06-30 13:40:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTG26063013002010');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26070108002010','FJI-020122010','2026-07-01','08:00:00','2026-07-01 08:00:00','2026-07-01 08:36:00','Service','Done',NULL,'Solo job anggoro #5');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR26070108002010','2026-07-01','08:36:00','201000173','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-07-01 08:36:00','2026-07-01 08:36:00','S26070108002010');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR26070108002010','FJI-020122010');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG26070108002010','S26070108002010','201000173','rtg26070108002010token',5,'','Y','2026-07-01 08:36:00','2026-07-01 08:36:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020122010','RTG26070108002010');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S26070210002010','FJI-020122010','2026-07-02','10:00:00','2026-07-02 10:00:00','2026-07-02 10:34:00','Dismantle','Done',NULL,'Solo job anggoro #6');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR26070210002010','S26070210002010','201000123','2026-07-02','10:34:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-07-02 10:34:00','2026-07-02 10:34:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR26070210002010','FJI-020122010');
-- (job solo S26070210002010 sengaja TANPA rating - pelanggan belum menilai)

-- ============================================================
-- SOLO JOBS - AHMAD (FJI-020120011)
-- ============================================================
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260626080011','FJI-020120011','2026-06-26','08:00:00','2026-06-26 08:00:00','2026-06-26 08:55:00','Dismantle','Done',NULL,'Solo job ahmad #1');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260626080011','S260626080011','201000125','2026-06-26','08:55:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-06-26 08:55:00','2026-06-26 08:55:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260626080011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260626080011','S260626080011','201000125','rtg260626080011token',4,'','Y','2026-06-26 08:55:00','2026-06-26 08:55:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260626080011');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260627130011','FJI-020120011','2026-06-27','13:00:00','2026-06-27 13:00:00','2026-06-27 14:00:00','Service','Done',NULL,'Solo job ahmad #2');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260627130011','2026-06-27','14:00:00','201000126','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-27 14:00:00','2026-06-27 14:00:00','S260627130011');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260627130011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260627130011','S260627130011','201000126','rtg260627130011token',3,'','Y','2026-06-27 14:00:00','2026-06-27 14:00:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260627130011');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260629080011','FJI-020120011','2026-06-29','08:00:00','2026-06-29 08:00:00','2026-06-29 08:58:00','Instalasi','Done',NULL,'Solo job ahmad #3');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260629080011','201000132','Perumahan Asri Blok A3','3','10','Meranti','Cikarang','Bekasi','ZTE000011','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:11','ODP-05','ODC-02','EN-01','2026-06-29 08:58:00','2026-06-29 08:58:00','S260629080011');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260629080011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260629080011','S260629080011','201000132','rtg260629080011token',4,'','Y','2026-06-29 08:58:00','2026-06-29 08:58:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260629080011');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260630080011','FJI-020120011','2026-06-30','08:00:00','2026-06-30 08:00:00','2026-06-30 09:02:00','Dismantle','Done',NULL,'Solo job ahmad #4');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260630080011','S260630080011','201000133','2026-06-30','09:02:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-06-30 09:02:00','2026-06-30 09:02:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260630080011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260630080011','S260630080011','201000133','rtg260630080011token',3,'','Y','2026-06-30 09:02:00','2026-06-30 09:02:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260630080011');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260701130011','FJI-020120011','2026-07-01','13:00:00','2026-07-01 13:00:00','2026-07-01 13:57:00','Service','Done',NULL,'Solo job ahmad #5');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260701130011','2026-07-01','13:57:00','201000130','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-07-01 13:57:00','2026-07-01 13:57:00','S260701130011');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260701130011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260701130011','S260701130011','201000130','rtg260701130011token',4,'','Y','2026-07-01 13:57:00','2026-07-01 13:57:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260701130011');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260703090011','FJI-020120011','2026-07-03','09:00:00','2026-07-03 09:00:00','2026-07-03 10:00:00','Instalasi','Done',NULL,'Solo job ahmad #6');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260703090011','201000162','Perumahan Asri Blok A6','3','10','Meranti','Cikarang','Bekasi','ZTE000011','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:11','ODP-05','ODC-02','EN-01','2026-07-03 10:00:00','2026-07-03 10:00:00','S260703090011');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260703090011','FJI-020120011');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260703090011','S260703090011','201000162','rtg260703090011token',3,'','Y','2026-07-03 10:00:00','2026-07-03 10:00:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120011','RTG260703090011');

-- ============================================================
-- SOLO JOBS - RIZKI (FJI-020120013)
-- ============================================================
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260627080013','FJI-020120013','2026-06-27','08:00:00','2026-06-27 08:00:00','2026-06-27 09:20:00','Service','Done',NULL,'Solo job rizki #1');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260627080013','2026-06-27','09:20:00','201000127','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-06-27 09:20:00','2026-06-27 09:20:00','S260627080013');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260627080013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260627080013','S260627080013','201000127','rtg260627080013token',2,'','Y','2026-06-27 09:20:00','2026-06-27 09:20:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260627080013');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260628080013','FJI-020120013','2026-06-28','08:00:00','2026-06-28 08:00:00','2026-06-28 09:25:00','Dismantle','Done',NULL,'Solo job rizki #2');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260628080013','S260628080013','201000128','2026-06-28','09:25:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-06-28 09:25:00','2026-06-28 09:25:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260628080013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260628080013','S260628080013','201000128','rtg260628080013token',3,'','Y','2026-06-28 09:25:00','2026-06-28 09:25:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260628080013');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260629130013','FJI-020120013','2026-06-29','13:00:00','2026-06-29 13:00:00','2026-06-29 14:18:00','Instalasi','Done',NULL,'Solo job rizki #3');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260629130013','201000135','Perumahan Asri Blok R3','3','10','Meranti','Cikarang','Bekasi','ZTE000013','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:13','ODP-05','ODC-02','EN-01','2026-06-29 14:18:00','2026-06-29 14:18:00','S260629130013');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260629130013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260629130013','S260629130013','201000135','rtg260629130013token',2,'','Y','2026-06-29 14:18:00','2026-06-29 14:18:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260629130013');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260701080013','FJI-020120013','2026-07-01','08:00:00','2026-07-01 08:00:00','2026-07-01 09:30:00','Service','Done',NULL,'Solo job rizki #4');
INSERT INTO `service_reports` (`srv_id`,`tanggal`,`jam`,`netpay_id`,`problem`,`action`,`part`,`ont_bef`,`ont_aft`,`red_bef`,`red_aft`,`keterangan`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SR260701080013','2026-07-01','09:30:00','201000137','Keluhan pelanggan','Penanganan teknisi','ONT','ZTE F609','ZTE F609','-18','-16','Selesai','2026-07-01 09:30:00','2026-07-01 09:30:00','S260701080013');
INSERT INTO `service_report_pic` (`srv_id`,`tech_id`) VALUES ('SR260701080013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260701080013','S260701080013','201000137','rtg260701080013token',3,'','Y','2026-07-01 09:30:00','2026-07-01 09:30:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260701080013');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260702130013','FJI-020120013','2026-07-02','13:00:00','2026-07-02 13:00:00','2026-07-02 14:22:00','Dismantle','Done',NULL,'Solo job rizki #5');
INSERT INTO `dismantle_reports` (`dismantle_id`,`schedule_id`,`netpay_id`,`tanggal`,`jam`,`alasan`,`action`,`part_removed`,`kondisi_perangkat`,`keterangan`,`created_at`,`updated_at`) VALUES
('DR260702130013','S260702130013','201000138','2026-07-02','14:22:00','Pindah Alamat','Penanganan teknisi','ONT','Baik','Selesai','2026-07-02 14:22:00','2026-07-02 14:22:00');
INSERT INTO `dismantle_report_pic` (`dismantle_id`,`tech_id`) VALUES ('DR260702130013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260702130013','S260702130013','201000138','rtg260702130013token',2,'','Y','2026-07-02 14:22:00','2026-07-02 14:22:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260702130013');
INSERT INTO `schedules` (`schedule_id`,`tech_id`,`date`,`time`,`start_time`,`end_time`,`job_type`,`status`,`queue_id`,`catatan`) VALUES
('S260703080013','FJI-020120013','2026-07-03','08:00:00','2026-07-03 08:00:00','2026-07-03 09:28:00','Instalasi','Done',NULL,'Solo job rizki #6');
INSERT INTO `ikr_report` (`ikr_id`,`netpay_id`,`alamat`,`rt`,`rw`,`desa`,`kec`,`kab`,`sn`,`type_ont`,`redaman`,`odp_no`,`odc_no`,`jc_no`,`mac_sebelum`,`mac_sesudah`,`odp`,`odc`,`enclosure`,`created_at`,`updated_at`,`schedule_id`) VALUES
('SI260703080013','201000161','Perumahan Asri Blok R6','3','10','Meranti','Cikarang','Bekasi','ZTE000013','ZTE','-18','ODP-05','ODC-02','JC-01','-','AA:BB:CC:00:00:13','ODP-05','ODC-02','EN-01','2026-07-03 09:28:00','2026-07-03 09:28:00','S260703080013');
INSERT INTO `ikr_report_pic` (`ikr_id`,`tech_id`) VALUES ('SI260703080013','FJI-020120013');
INSERT INTO `technician_ratings` (`rating_id`,`schedule_id`,`netpay_id`,`token`,`rating`,`komentar`,`is_sent`,`sent_at`,`created_at`,`status`) VALUES
('RTG260703080013','S260703080013','201000161','rtg260703080013token',3,'','Y','2026-07-03 09:28:00','2026-07-03 09:28:00','Rated');
INSERT INTO `detail_ratings` (`tech_id`,`rating_id`) VALUES ('FJI-020120013','RTG260703080013');
COMMIT;

-- =====================================================================
-- SELESAI. Estimasi skor SAW setelah data ini masuk (gabung dengan 3 job
-- tim yang sudah ada sebelumnya di database, jika belum dihapus manual):
--   1. M. Rendy Renaldi   - job tercepat & rating tertinggi
--   2. Anggoro Prassetio  - performa baik, sedikit di bawah Rendy
--   3. Ahmad Sunandar     - performa menengah
--   4. Rizki Alfian       - job paling lambat & rating terendah
--
-- CATATAN: Jika 3 job tim yang SUDAH ADA di database (schedule_key 1-3,
-- tanggal 6 Juli) ikut kehitung dalam rentang filter periode kamu,
-- angka C1/C2/C3 final akan sedikit berbeda dari estimasi di atas.
-- Refresh halaman Laporan Kerja Teknisi untuk melihat angka pastinya.
-- =====================================================================