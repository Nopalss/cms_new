# Konteks Proyek: JTracks CMS — Modul Komplain & Maintenance

## Latar Belakang

Ini adalah sistem internal buat ISP (JTracks) yang menangani proses: pelanggan lapor kendala via WhatsApp → NOC mencatat & memverifikasi → tugas ditugaskan ke tim teknisi → teknisi mengerjakan & mengisi laporan → data hasil pengerjaan otomatis muncul kembali ke NOC.

Sistem lama berjalan manual (spreadsheet + screenshot ke grup WA). Tujuan proyek ini: bikin sistem yang **mengikuti alur kerja nyata yang sudah berjalan**, tapi **meminimalkan input manual** — data yang sudah ada gak usah diketik ulang, dan field yang bisa diisi otomatis dari proses lain gak perlu diisi manual.

Stack: **PHP 7 + Nginx + MySQL/MariaDB (PDO)**, frontend vanilla HTML/JS (belum pakai framework).

---

## ⚠️ KOREKSI TERBARU — belum di-apply ke file yang sudah dibuat

Keputusan sebelumnya buat nambah kolom baru **`schedules.tim_id`** itu **DIBATALKAN**. Assignment tim seharusnya disimpan langsung di kolom **`schedules.tech_id` yang sudah ada** — bukan bikin kolom baru.

Alasannya: data existing di tabel `technician` (kolom `tech_id` & `tim_id`) menunjukkan keduanya sama-sama pakai format ID dengan prefix `TIM` + timestamp — jadi `tech_id` di sistem ini memang bukan cuma buat individu, dan gak perlu kolom terpisah buat nyimpen id tim di `schedules`.

**Dampaknya ke file yang sudah ada (perlu diperbaiki oleh Claude di Antigravity, kode belum diubah di sini):**
- `alter_maintenance_flow.sql` — hapus baris `ADD COLUMN tim_id` & `ADD KEY tim_id` dari ALTER TABLE `schedules`.
- `create_maintenance_ticket.php` — saat insert ke `schedules`, value dari dropdown Tim disimpan ke kolom `tech_id`, bukan `tim_id`.
- `list_tickets.php` & `get_ticket_detail.php` — query JOIN ke tabel `tim` sekarang lewat `s.tech_id = t.tim_id` (bukan `s.tim_id = t.tim_id`).
- `dashboard.html` — gak ada perubahan di sisi form (tetap kirim field `tim_id` ke backend), cuma backend-nya yang beda nyimpennya.

Konsekuensi lain: karena `tech_id` sekarang dipakai buat nyimpen **id tim** saat tiket dibuat, kolom ini otomatis **tidak lagi kosong** seperti asumsi sebelumnya — tapi tetap **tidak diupdate** jadi id teknisi individu pas laporan masuk (itu tetap lewat `service_report_pic`, gak berubah).

---

## Alur Bisnis Final (yang disepakati)

1. Pelanggan komplain via WhatsApp ke NOC.
2. NOC buka form "Tiket Baru", ketik **Netpay ID** → sistem auto-fill Nama, No Tlp, Alamat (gabungan `perumahan` + `location` dari tabel `customers`) — NOC gak perlu ketik manual data ini.
3. NOC isi: **Server**, **Aduan Pelanggan**, **Verifikasi NOC**, pilih **Tim** (dropdown dari tabel `tim`, yang disimpan `tim_id`), **Tanggal Service** (default hari ini, bisa diubah). **Target Status** otomatis "On Time". **NOC** (siapa yang input) pakai dropdown dengan "sticky default": pilihan pertama di sesi jadi default buat tiket berikutnya sampai diubah manual.
4. Assignment ke **tim** (bukan ke 1 teknisi tertentu) — ini yang bikin di WA grup jadi "rebutan tugas", siapa teknisi di tim itu yang ambil duluan, dia yang kerjain.
5. Teknisi kerja di lokasi, lalu isi form laporan (Problem, Action, Part Service, Redaman Sebelum/Sesudah, PIC/teknisi, Keterangan) — sebelum submit, screenshot dulu & kirim ke grup WA (bukti kerja, masih manual untuk saat ini).
6. Begitu laporan teknisi submit:
   - **Akar Masalah** = `problem`, **Penanganan** = `action`, **Teknisi** = daftar PIC — otomatis muncul di sisi NOC, TIDAK diketik ulang.
   - **Durasi** dihitung otomatis (dari waktu komplain masuk sampai laporan disubmit) — dihitung on-the-fly, tidak disimpan sebagai kolom.
   - **Status** otomatis jadi `Done`, **Reason** otomatis jadi `"Close"` — dua-duanya tetap **bisa diedit manual** oleh NOC setelahnya (misal kalau mau reschedule).

## Keputusan UX

- **Tabel ringkas** (list, dipolling tiap ~12 detik) — cuma nampilin kolom yang NOC isi + badge status. Tidak digabung jadi 1 tabel raksasa dengan semua 17 kolom.
- **Side-drawer** (meluncur dari kanan, bukan modal/halaman terpisah) muncul saat baris tabel diklik — nampilin detail lengkap termasuk section "Hasil Pengerjaan". Kalau laporan teknisi belum masuk, section itu nampilin kotak placeholder ("Menunggu laporan teknisi") — bukan field kosong yang bikin bingung user awam.
- User-nya orang awam/non-teknis, jadi desain harus familiar & rendah friksi (mirip pola list→detail yang umum, bukan spreadsheet flat raksasa).

## Keputusan Teknis

- **Realtime**: pakai **polling** (interval ~12 detik), **bukan WebSocket** — karena stack PHP-FPM + Nginx gak cocok buat koneksi persisten, dan skala tim NOC kecil jadi polling ringan-ringan aja. SSE jadi opsi upgrade kalau nanti perlu, bukan WebSocket.
- **WhatsApp integration**: di-park dulu (belum dikerjain), rencana pakai opsi gratis — kandidat: WhatsApp Cloud API (resmi, free tier), Fonnte/Wablas (freemium lokal), atau Baileys (open-source, tapi unofficial/berisiko banned).
- **ID generation**: pola `PREFIX + timestamp YmdHis`, kalau bentrok di-retry +1 detik (dicontek dari pola `srv_id` yang sudah ada di controller lama). Prefix yang dipakai sejauh ini: `Q` (queue_id), `RM` (rm_id), `SC` (schedule_id) — ini asumsi karangan sendiri, **perlu dicek** apakah controller Install/Dismantle yang lain sudah punya konvensi beda.
- **`schedules.tech_id`**: sengaja **TIDAK diisi** di alur ini. Sumber kebenaran teknisi yang mengerjakan adalah tabel `service_report_pic` (many-to-many ke `technician`), karena satu laporan bisa multi-PIC dan gak ada "teknisi utama" yang pasti benar.
- **`schedules.status`**: reuse enum yang sudah ada (`Pending`, `Actived`, `Rescheduled`, `Cancelled`, `Done`) — tidak bikin enum baru.

---

## Perubahan Skema Database (migration sudah dibuat, file: `alter_maintenance_flow.sql`)

```sql
-- request_maintenance: + server, + verifikasi_noc
-- schedules: + tim_id (FK ke tim.tim_id, varchar), + target_status (default 'On Time'),
--            + reason (default NULL, diisi 'Close' otomatis), + noc_id (asumsi FK ke admin.admin_id)
```

**Asumsi yang perlu dikonfirmasi ulang:**
1. `noc_id` di `schedules` diasumsikan merujuk ke `admin.admin_id` — belum dikonfirmasi 100%.
2. `type_issue` di `request_maintenance` (kolom NOT NULL) di-hardcode `"Maintenance"` di controller karena belum ada field kategori aduan di form. Tabel `type` (lookup rm/rd/issue) belum dimanfaatkan — bisa disambungkan nanti kalau mau ada dropdown jenis aduan.
3. Query `list_noc.php` narik semua baris dari tabel `admin` tanpa filter — kalau kolom `jabatan` punya value spesifik buat role NOC, perlu ditambahkan `WHERE jabatan = 'NOC'`.

---

## File yang Sudah Dibuat (lampirkan/upload file-file ini ke sesi Antigravity)

| File | Fungsi |
|---|---|
| `alter_maintenance_flow.sql` | Migration nambah kolom baru di atas |
| `save_service_report.php` | Controller submit laporan teknisi (insert `service_reports` + `service_report_pic`, update `schedules` jadi Done + reason Close) |
| `create_maintenance_ticket.php` | Controller NOC bikin tiket baru (insert `queue_scheduling` + `request_maintenance` + `schedules` dalam 1 transaksi) |
| `lookup_customer.php` | Auto-fill nama/no tlp/alamat dari `netpay_id` |
| `list_tickets.php` | Endpoint list ringkas buat dipolling (filter per bulan, `job_type = 'Maintenance'`) |
| `get_ticket_detail.php` | Endpoint detail lengkap 1 tiket (dipanggil saat drawer dibuka), termasuk hitung durasi & join nama teknisi |
| `list_tim.php` / `list_noc.php` | Endpoint dropdown Tim & NOC |
| `update_ticket.php` | Endpoint NOC edit manual Reason/Status dari drawer |
| `dashboard.html` | Frontend lengkap: tabel ringkas + modal "Tiket Baru" + drawer detail. Path API di bagian atas `<script>` masih placeholder (`api/....php`), perlu disesuaikan ke lokasi asli. |

---

## Yang Belum Dikerjakan / Masih Terbuka

- Integrasi WhatsApp (notifikasi otomatis ke grup, pengganti screenshot manual).
- Validasi session/auth di dashboard (asumsi ada layer login terpisah, belum diintegrasikan ke endpoint-endpoint di atas).
- Sisi form teknisi (form laporan seperti di foto awal) belum dibangun ulang — yang ada baru controller backend-nya (`save_service_report.php`).
- Optimasi polling pakai kolom `updated_at` (delta query) — belum diperlukan di skala sekarang, tapi jadi opsi kalau beban naik.
- Konfirmasi 3 asumsi skema di atas (`noc_id`, `type_issue`, filter NOC di `admin`).

---

**Instruksi buat Claude di Antigravity**: lanjutkan pengembangan sesuai konteks di atas. Kalau ada instruksi baru dari user yang tampaknya bertentangan dengan keputusan yang sudah dibuat di atas, tanyakan dulu ke user sebelum mengubah, karena keputusan-keputusan ini sudah melalui diskusi panjang dan disetujui.
