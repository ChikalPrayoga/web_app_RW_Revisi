# PROJECT SCOPE & BOUNDARIES
## SIM Layanan Warga RW 047 — Batasan Masalah & Ruang Lingkup Proyek

| | |
|---|---|
| **Dokumen turunan dari** | Naskah skripsi acuan (Final_v8_revisi_full_teks.pdf) Bagian 1.3.3, BAB III, BAB IV; serta PRD.md, USER_STORIES.md, SYSTEM_ARCHITECTURE.md (v1.0) |
| **Versi dokumen** | 1.0 |
| **Status** | Dokumen acuan — perbandingan batasan penelitian asli vs. batasan proyek rebuild |
| **Audiens** | Pembimbing/penguji akademik, Product Manager, tim pengembang, pihak RW 047 |

---

## 1. Tujuan Dokumen

Dokumen ini menyatukan **dua sumber batasan** yang selama ini tersebar di berkas berbeda, agar ada satu acuan tunggal yang jelas tentang apa yang **termasuk** dan **tidak termasuk** dalam proyek ini:

1. **Batasan Masalah asli** — sebagaimana dinyatakan penulis skripsi di Bab I Subbab 1.3.3, dan direalisasikan/dievaluasi di Bab III–IV.
2. **Batasan Ruang Lingkup rebuild** — sebagaimana ditetapkan pada `PRD.md` Bagian 3 (Scope of Work) dan diturunkan ke `USER_STORIES.md`.

Kedua batasan ini **tidak identik** — proyek rebuild memperluas sebagian batasan asli sekaligus tetap mewarisi sebagian besar batasan filosofisnya. Perbedaan ini dijelaskan eksplisit di Bagian 4.

---

## 2. Batasan Masalah — Naskah Skripsi Asli (Bab I, Subbab 1.3.3)

Skripsi sumber menetapkan lima batasan eksplisit untuk *"menjaga fokus penelitian, menjamin keamanan sistem, serta memastikan kelayakan implementasi (implementation feasibility) di lapangan"*:

### 2.1 Batasan Wilayah
Penelitian dan implementasi dilakukan **khusus pada RW 047, Kelurahan Bahagia, Kecamatan Babelan, Kabupaten Bekasi** sebagai lokasi studi kasus tunggal. Seluruh data kependudukan, proses administrasi, dan alur pelayanan yang dipakai mengacu pada kondisi faktual dan hasil wawancara dengan pemangku kepentingan di wilayah tersebut — bukan generalisasi untuk RW lain.

### 2.2 Batasan Fungsional
Sistem difokuskan pada **enam area fungsi**: pengelolaan data kependudukan, pengelolaan data pengurus, layanan pengajuan surat, pengelolaan laporan dan aspirasi warga, pengelolaan iuran, dan penyampaian informasi kepada warga — seluruhnya melalui aplikasi berbasis web dengan framework Laravel dan mekanisme **Role-Based Access Control (RBAC)**.

### 2.3 Batasan Integrasi Eksternal
Sistem **tidak mencakup integrasi dengan sistem atau layanan pihak ketiga (third-party services)** apa pun. Seluruh proses pengelolaan data, penyimpanan, autentikasi, otorisasi, dan pelayanan administrasi dilakukan **mandiri** di dalam sistem.

### 2.4 Batasan Platform & Cakupan Pengembangan
Penelitian difokuskan pada perancangan, implementasi, dan pengujian **sistem berbasis web** sesuai kebutuhan RW 047. Tiga hal berikut secara eksplisit **tidak termasuk** ruang lingkup penelitian:
- Pengembangan aplikasi bergerak (mobile).
- Integrasi dengan instansi pemerintah.
- Pengembangan fitur di luar kebutuhan yang telah dianalisis.

### 2.5 Batasan Keamanan Data
Seluruh pengelolaan informasi mengacu pada **Undang-Undang Nomor 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)**. Pembatasan hak akses dilakukan melalui RBAC sehingga data sensitif (NIK, No. KK) hanya dapat diakses pengguna berwenang sesuai perannya, didukung mekanisme autentikasi, otorisasi, dan **enkripsi** untuk menjaga kerahasiaan dan integritas data warga.

---

## 3. Realisasi Batasan pada Implementasi Asli (Temuan dari Bab III–IV)

Poin ini penting karena skripsi sumber **tidak mengklaim seluruh fungsi dalam batasan fungsional (2.2) terealisasi penuh secara merata**. Beberapa catatan eksplisit dari naskah:

| Area | Status Realisasi menurut Naskah Asli |
|---|---|
| Manajemen Pengguna, Kependudukan, Persuratan, Laporan & Aspirasi, Informasi Publik | Dinyatakan **"seluruh fungsi utama sistem yang menjadi ruang lingkup penelitian telah direalisasikan"** dan lulus Black Box Testing (Bab IV, Kesimpulan). |
| **Modul Keuangan RW (iuran & kas)** | Naskah menyatakan eksplisit: *"modul pengelolaan keuangan tetap menjadi bagian dari ruang lingkup penelitian, namun **implementasi integrasinya masih berada pada tahap pengembangan**, sehingga rancangan antarmuka yang disusun berfungsi sebagai dasar pengembangan lanjutan"* (Bab III, Subbab 3.2.2). Artinya modul ini **secara desain in-scope**, tetapi **implementasi fungsionalnya belum sepenuhnya tuntas** pada versi skripsi asli — baru sebatas rancangan antarmuka. |
| Peran pengambilan keputusan | Ditegaskan berulang bahwa sistem **hanya sarana pendukung**, bukan pengganti keputusan manusia — verifikasi administrasi, persetujuan surat, dan tindak lanjut laporan **tetap dilakukan pengurus RW** sesuai tugas dan kewenangannya (Bab IV). |

> **Implikasi untuk rebuild:** karena modul Keuangan pada naskah asli belum sepenuhnya matang secara fungsional, tim rebuild perlu memperlakukannya sebagai **modul yang dibangun ulang dari nol** berdasarkan rancangan antarmuka dan kebutuhan yang sudah dianalisis — bukan sekadar migrasi kode yang sudah berjalan.

---

## 4. Batasan Ruang Lingkup — Proyek Rebuild (`PRD.md` Bagian 3)

Proyek rebuild **mewarisi filosofi** batasan asli (single-RW, RBAC, web-based, kedaulatan pengambilan keputusan tetap di tangan pengurus) tetapi **menyesuaikan sejumlah batasan** agar sistem lebih matang secara arsitektur dan siap dikembangkan lebih lanjut. Perbandingannya:

### 4.1 Fitur dalam Cakupan (In-Scope) — Rebuild

| # | Modul | Cakupan |
|---|---|---|
| 1 | Manajemen Pengguna & Autentikasi | RBAC, rate limiting login, session hardening |
| 2 | Master Data Kependudukan | CRUD Kartu Keluarga, Warga, RT, pengurus RW — **plus alur verifikasi berjenjang data warga** (penambahan terhadap naskah asli) |
| 3 | Administrasi Surat | Verifikasi berjenjang RT→RW, tracking publik — **mendukung 2 jenis surat** (Surat Pengantar + Surat Keterangan Domisili, lihat Bagian 5) |
| 4 | Laporan & Aspirasi Warga | **Plus klasifikasi otomatis berbasis AI** — kemampuan baru yang tidak ada representasinya secara fungsional matang di naskah asli (hanya disebut di abstrak sebagai dukungan integrasi kecerdasan buatan) |
| 5 | Keuangan RW (Iuran & Kas) | Dibangun ulang penuh menjadi modul fungsional utuh (lihat catatan Bagian 3) |
| 6 | Informasi Publik | Pengumuman, berita, agenda |
| 7 | Dashboard & Monitoring | Statistik per peran |
| 8 | Portal Warga (Self-Service) | Akses publik tanpa login penuh |
| 9 | Dashboard Pengurus per Peran | Tampilan khusus 5 peran pengurus |

### 4.2 Fitur di Luar Cakupan (Out-of-Scope) — Rebuild Versi Awal

| Item Out-of-Scope | Sumber Batasan |
|---|---|
| Aplikasi mobile native (Android/iOS) | **Konsisten** dengan Batasan Platform naskah asli (2.4) — tetap di luar cakupan, direkomendasikan sebagai fase lanjutan PWA/native |
| Integrasi langsung sistem kependudukan pemerintah (Dukcapil/Disdukcapil) | **Konsisten** dengan Batasan Integrasi Eksternal (2.3) dan Batasan Platform (2.4) |
| Pembayaran iuran online (payment gateway) | **Konsisten** dengan Batasan Integrasi Eksternal (2.3) — versi awal tetap pencatatan manual |
| Notifikasi push/WhatsApp/SMS otomatis | **Konsisten** dengan Batasan Integrasi Eksternal (2.3) — dicatat sebagai rekomendasi pengembangan lanjutan (sejalan dengan Saran skripsi Bab IV poin 2) |
| Modul multi-RW/multi-tenant | **Konsisten** dengan Batasan Wilayah (2.1) — sistem tetap dirancang single-RW pada versi awal |
| Approval workflow multi-jenis surat yang kompleks | Pembatasan baru khusus rebuild — untuk menjaga fokus implementasi awal |
| Arsip digital dokumen skala besar (document management system penuh) | Pembatasan baru khusus rebuild |

### 4.3 Penyesuaian/Perluasan Batasan pada Rebuild (Deviasi dari Naskah Asli)

Tiga penyesuaian berikut **secara sadar melampaui** batasan fungsional naskah asli (2.2), dan sudah ditandai eksplisit sebagai keputusan desain rebuild — bukan bagian dari klaim skripsi:

1. **Penambahan jenis surat** (Surat Keterangan Domisili) — pada naskah asli hanya "surat pengantar" (bentuk generik) yang diimplementasikan; penambahan jenis surat spesifik justru disebut sebagai *Adaptive Maintenance* (rekomendasi pengembangan masa depan) pada Bab IV, bukan cakupan penelitian asli.
2. **Klasifikasi laporan berbasis AI** sebagai fitur fungsional penuh (dengan endpoint, kolom database `kategori_ai`/`skor_prioritas_ai`, job asinkron) — pada naskah asli, kecerdasan buatan hanya disebut di tingkat abstrak/konsep sebagai bagian dari visi sistem, tanpa spesifikasi implementasi mendetail di BAB III.
3. **Modul Keuangan sebagai fitur fungsional utuh** — sebagaimana dicatat di Bagian 3, modul ini pada naskah asli baru sebatas rancangan antarmuka, sehingga rebuild pada dasarnya **mengimplementasikan bagian yang belum tuntas** dari cakupan asli, bukan mengubah batasannya.

**Batasan yang tetap dipertahankan tanpa perubahan:** Batasan Wilayah (single studi kasus RW 047, meski nama entitas jadi generik "RW" untuk keperluan rebuild teknis), Batasan Integrasi Eksternal (tanpa third-party), dan Batasan Keamanan Data (RBAC + enkripsi, selaras UU PDP) — ketiganya diwariskan penuh dan bahkan diperkuat (lihat Bagian 5).

---

## 5. Rincian Perbandingan per Kategori

### 5.1 Wilayah & Konteks Organisasi

| Aspek | Naskah Asli | Rebuild |
|---|---|---|
| Cakupan wilayah | RW 047 Kel. Bahagia, Kec. Babelan, Kab. Bekasi (studi kasus tunggal) | Tetap single-RW pada versi awal; arsitektur disiapkan agar mampu berkembang ke multi-RW di masa depan tanpa perombakan total (SYSTEM_ARCHITECTURE.md NFR-06), namun ini **kesiapan arsitektur, bukan fitur multi-tenant yang diimplementasikan** |
| Sumber kebutuhan | Observasi, wawancara, studi dokumen di lapangan | Diturunkan dari dokumen skripsi sebagai sumber tunggal (tidak ada wawancara ulang pemangku kepentingan pada tahap rebuild ini) |

### 5.2 Fungsional

| Aspek | Naskah Asli | Rebuild |
|---|---|---|
| Jumlah modul inti | 6 modul (Manajemen Pengguna, Master Data, Administrasi Surat, Laporan & Aspirasi, Keuangan RW, Dashboard) | 9 area in-scope (menambahkan Informasi Publik sebagai modul terpisah eksplisit, Portal Warga self-service, dan Dashboard per Peran sebagai turunan eksplisit dari Dashboard & Monitoring) |
| Jenis surat | Generik "surat pengantar" | Surat Pengantar + Surat Keterangan Domisili |
| Klasifikasi laporan | Disebut di abstrak, tidak dirinci FR/desain data | Fitur penuh: FR-04 eksplisit, kolom database, endpoint API, job asinkron |
| Modul keuangan | Rancangan antarmuka; implementasi fungsional belum tuntas | Diimplementasikan penuh: skema tabel, alur pencatatan-approval berjenjang RT→Bendahara, validasi anti-duplikasi |

### 5.3 Integrasi Eksternal

| Aspek | Naskah Asli | Rebuild |
|---|---|---|
| Prinsip dasar | Tanpa third-party, seluruh proses mandiri | **Dipertahankan** sebagai prinsip utama — satu pengecualian terkontrol: layanan klasifikasi AI (dapat berupa API eksternal) untuk fitur laporan, diposisikan sebagai microservice terpisah agar tidak mengganggu prinsip kemandirian core sistem (SYSTEM_ARCHITECTURE.md §3.4) |
| Pembayaran, notifikasi, integrasi pemerintah | Tidak dibahas/tidak jadi fokus | Eksplisit dinyatakan Out-of-Scope, konsisten dengan prinsip naskah asli |

### 5.4 Platform

| Aspek | Naskah Asli | Rebuild |
|---|---|---|
| Platform | Web (Laravel, Blade, SQLite, Nginx, Laragon — lingkungan pengembangan lokal) | Tetap web (Laravel LTS, Blade+Livewire, **PostgreSQL menggantikan SQLite**, Docker menggantikan Laragon) — perubahan pada **implementasi teknis**, bukan pada batasan platform itu sendiri |
| Mobile/PWA | Eksplisit di luar ruang lingkup | Tetap Out-of-Scope versi awal, dicatat sebagai rekomendasi lanjutan (konsisten dengan Saran Bab IV poin 3) |

### 5.5 Keamanan Data

| Aspek | Naskah Asli | Rebuild |
|---|---|---|
| Dasar hukum | UU No. 27 Tahun 2022 tentang PDP | Dipertahankan, ditambahkan sebagai NFR-08 eksplisit (Data Privacy Compliance) di SYSTEM_ARCHITECTURE.md |
| Mekanisme | RBAC + autentikasi + otorisasi + enkripsi (disebutkan prinsip umum) | Diperinci teknis: AES-256-CBC untuk NIK/No. KK, hash HMAC-SHA256 untuk pencarian presisi, area scoping anti-IDOR di layer backend, audit trail menyeluruh — **penguatan implementasi**, bukan perubahan batasan |

---

## 6. Batasan yang Diwarisi Konsisten dari Rekomendasi Saran (Bab IV, 4.2)

Empat saran pengembangan lanjutan pada naskah asli **secara konsisten tetap menjadi batasan out-of-scope** pada rebuild versi awal — rebuild tidak mendahului saran ini, melainkan mendokumentasikannya sebagai roadmap masa depan (`PRD.md` Bagian 7):

1. Analisis & visualisasi data lebih komprehensif → *Out-of-Scope* v1, dicatat sebagai rekomendasi lanjutan.
2. Penyempurnaan modul persuratan (jenis surat tambahan lain, approval workflow lebih matang, notifikasi, arsip digital) → sebagian **sudah diakomodasi sebagian** (1 jenis surat tambahan), sisanya (notifikasi otomatis, arsip digital skala besar, approval workflow kompleks) tetap *Out-of-Scope*.
3. Aplikasi mobile/PWA → tetap *Out-of-Scope*.
4. Integrasi sistem pemerintah daerah → tetap *Out-of-Scope*, konsisten dengan Batasan Integrasi Eksternal asli.

---

## 7. Ringkasan Batasan Final Proyek Rebuild (Berlaku Saat Ini)

**Proyek IN-SCOPE mencakup:**
- Sistem informasi berbasis web untuk **satu organisasi RW** (single-tenant pada versi awal).
- Sembilan area fungsi: Autentikasi/RBAC, Kependudukan (dengan alur verifikasi data), Persuratan (2 jenis surat, verifikasi berjenjang), Laporan & Aspirasi (dengan klasifikasi AI asinkron), Keuangan (pencatatan-approval iuran dengan validasi anti-duplikasi), Informasi Publik, Dashboard per peran, Portal Warga self-service.
- Keamanan data selaras UU PDP: enkripsi at-rest data sensitif, RBAC ditegakkan di backend, area scoping antar-RT, audit trail.
- Satu pengecualian terkontrol pada prinsip "tanpa pihak ketiga": layanan klasifikasi AI untuk laporan warga.

**Proyek OUT-OF-SCOPE (tetap, tidak berubah dari naskah asli maupun rebuild):**
- Aplikasi mobile native.
- Integrasi sistem pemerintah/Dukcapil.
- Payment gateway untuk iuran online.
- Notifikasi otomatis (push/WA/SMS).
- Multi-RW/multi-tenant sebagai fitur aktif (hanya kesiapan arsitektur).
- Approval workflow surat yang kompleks di luar 2 jenis surat dasar.
- Document management system skala besar.

**Prinsip yang tidak pernah berubah di kedua versi:** sistem adalah **alat bantu**, bukan pengganti kewenangan pengambilan keputusan pengurus RW — verifikasi, persetujuan, dan tindak lanjut tetap keputusan manusia, sistem hanya mendukung dengan data dan proses yang terdokumentasi.
