# Product Requirement Document (PRD)
## Rebuild — Sistem Informasi Layanan Warga RW 047

| | |
|---|---|
| **Dokumen sumber** | Skripsi "Implementasi Sistem Informasi Layanan Warga RW 047 Berbasis Web Dengan Framework Laravel" |
| **Versi PRD** | 1.0 |
| **Status** | Draft untuk perombakan/rebuild sistem |
| **Disusun oleh** | Product Manager & System Analyst (AI-assisted analysis) |

---

## 1. Ringkasan Eksekutif (Executive Summary)

### 1.1 Latar Belakang

Pengelolaan administrasi di lingkungan Rukun Warga (RW) — khususnya RW 047, Kelurahan Bahagia, Kecamatan Babelan, Kabupaten Bekasi — masih menghadapi lima titik masalah utama:

1. **Data warga tidak terintegrasi** — pencatatan kependudukan tersebar dan tidak konsisten.
2. **Layanan administrasi manual** — pengajuan surat pengantar masih berbasis kertas dan berjenjang secara informal.
3. **Pencatatan iuran tidak terdokumentasi optimal** — rekapitulasi kas RT/RW rawan selisih dan sulit ditelusuri.
4. **Laporan & aspirasi warga tidak terpusat** — keluhan warga tidak terdokumentasi secara formal sehingga status penyelesaiannya sulit dipantau.
5. **Tidak ada kontrol akses berbasis peran** — belum ada mekanisme baku yang memastikan setiap pengguna hanya mengakses data sesuai kewenangannya.

Sistem eksisting (skripsi acuan) telah membuktikan konsep ini dengan Laravel 10 + SQLite dan berhasil lulus Black Box Testing untuk seluruh fungsi utama. Namun sistem tersebut adalah hasil riset akademik skala kecil (single RW, single developer, 3 sprint) sehingga perlu **dirombak ulang (rebuild)** agar lebih matang secara arsitektur, teknologi, dan siap untuk skala produksi/multi-RW di masa depan.

### 1.2 Tujuan Pembangunan Ulang

Membangun ulang Sistem Informasi Layanan Warga (SIM Layanan Warga) berbasis web yang mendukung digitalisasi penuh proses administrasi RW — mulai dari data kependudukan, persuratan, keuangan/iuran, informasi publik, hingga pengelolaan laporan & aspirasi warga — dengan fondasi keamanan, skalabilitas, dan kualitas kode yang lebih kuat dari versi awal.

### 1.3 Visi Produk

> Menjadi platform digital tepercaya yang menjadikan pelayanan RW **terintegrasi, transparan, terdokumentasi, dan mudah diakses** oleh warga maupun pengurus — menggantikan proses manual berbasis kertas dengan alur kerja digital yang terverifikasi dan auditable.

### 1.4 Nilai Utama (Core Value Propositions)

| Nilai | Deskripsi |
|---|---|
| **Transparansi** | Warga dapat memantau status pengajuan surat, laporan, dan aspirasi mereka secara real-time. |
| **Akuntabilitas** | Setiap perubahan data (siapa, kapan, apa) tercatat melalui audit trail dan soft-delete. |
| **Keamanan data pribadi** | Data sensitif (NIK, No. KK) dienkripsi at-rest, bukan hanya dibatasi lewat UI. |
| **Efisiensi kerja pengurus** | Alur verifikasi berjenjang (RT → RW) yang jelas menggantikan koordinasi manual/rapat rutin. |
| **Keputusan berbasis data** | Dashboard & klasifikasi otomatis (AI) membantu pengurus memprioritaskan laporan warga. |

---

## 2. Target Pengguna & Persona (User Personas)

Sistem menerapkan **Role-Based Access Control (RBAC)** dengan 6 aktor utama:

| Peran | Tujuan Penggunaan | Hak Akses | Aktivitas Utama |
|---|---|---|---|
| **Warga** | Memanfaatkan layanan & memperoleh informasi lingkungan secara digital | Akses layanan publik & layanan mandiri (self-service) | Mengajukan surat, menyampaikan laporan/aspirasi, memantau status layanan, mengakses info & pengumuman RW |
| **Ketua RT** | Mengelola pelayanan & administrasi wilayah RT yang dipimpinnya | Akses ke data & layanan pada lingkup RT terkait (area-scoped) | Mengelola data warga, memverifikasi pengajuan surat tahap awal, menindaklanjuti laporan warga, mencatat pembayaran iuran, memantau pelayanan RT |
| **Sekretaris RW** | Mendukung pengelolaan administrasi & dokumentasi tingkat RW | Akses ke administrasi & dokumen organisasi RW | Mengelola data administrasi RW, pengumuman, agenda kegiatan, arsip dokumen, proses administrasi surat |
| **Bendahara RW** | Mengelola aktivitas keuangan RW | Akses ke data keuangan & pelaporan keuangan | Mengelola iuran, validasi kas masuk/keluar dari tiap RT, menyusun laporan keuangan |
| **Ketua RW** | Mengelola & mengendalikan operasional administrasi RW; mendukung pengambilan keputusan | Akses ke seluruh data operasional + fungsi monitoring & persetujuan | Memantau pelayanan, memberikan persetujuan surat final, mengawasi laporan warga, verifikasi & tindak lanjut layanan |
| **Super Admin** | Mengelola administrasi teknis sistem agar operasional aplikasi optimal | Akses penuh ke pengelolaan sistem & pengguna | Mengelola akun pengguna, hak akses, konfigurasi sistem, integrasi layanan, audit aktivitas sistem |

**Catatan desain penting dari sistem acuan yang wajib dipertahankan:** pembatasan akses tidak boleh hanya diterapkan di level UI (menu tersembunyi), tetapi **ditegakkan di layer backend/controller** (mis. policy/authorization guard) dengan *area scoping* antar-RT, guna mencegah **Insecure Direct Object Reference (IDOR)** — request ke data di luar kewenangan harus ditolak dengan HTTP 403.

---

## 3. Cakupan Produk (Scope of Work)

### 3.1 Fitur dalam Cakupan (In-Scope)

| # | Modul | Fitur Utama |
|---|---|---|
| 1 | **Manajemen Pengguna & Autentikasi** | Login/logout, manajemen akun, role & hak akses (RBAC), rate limiting percobaan login, session regeneration |
| 2 | **Master Data Kependudukan** | CRUD data Kartu Keluarga, data Warga, data RT, data pengurus RW |
| 3 | **Administrasi Surat** | Pengajuan surat oleh warga, verifikasi berjenjang (RT → RW), penerbitan nomor surat, tracking status publik via kode unik |
| 4 | **Laporan & Aspirasi Warga** | Pengaduan/aspirasi via web, klasifikasi otomatis berbasis AI, disposisi, tindak lanjut, pemantauan status penyelesaian |
| 5 | **Keuangan RW (Iuran & Kas)** | Master jenis iuran, pencatatan pembayaran per RT, validasi oleh Bendahara RW, rekapitulasi & laporan keuangan |
| 6 | **Informasi Publik** | Pengumuman, berita, agenda kegiatan RW yang dapat diakses warga |
| 7 | **Dashboard & Monitoring** | Statistik, grafik, dan laporan monitoring sesuai peran pengguna |
| 8 | **Portal Warga (Self-Service)** | Beranda publik, form pengajuan, pelacakan status tanpa login penuh (tracking code) |
| 9 | **Dashboard Pengurus per Peran** | Tampilan khusus untuk Ketua RT, Sekretaris RW, Bendahara RW, Ketua RW, Super Admin |

### 3.2 Fitur di Luar Cakupan (Out-of-Scope) — untuk versi awal rebuild

- Aplikasi mobile native (Android/iOS) — direkomendasikan sebagai fase lanjutan (PWA/native).
- Integrasi langsung dengan sistem kependudukan pemerintah (Dukcapil/Disdukcapil) atau sistem pemda lain.
- Pembayaran iuran online (payment gateway) — versi awal masih pencatatan manual oleh Ketua RT/Bendahara.
- Notifikasi push/WhatsApp/SMS otomatis (dicatat sebagai rekomendasi pengembangan lanjutan).
- Modul multi-RW/multi-tenant (sistem acuan dirancang single-RW; multi-tenant perlu keputusan arsitektur terpisah jika dibutuhkan).
- Approval workflow multi-jenis surat yang kompleks (versi awal mendukung jenis surat dasar: Surat Pengantar, Surat Keterangan Domisili).
- Arsip digital dokumen skala besar (document management system penuh).

---

## 4. Persyaratan Fungsional (Functional Requirements)

### FR-01 — Manajemen Pengguna & RBAC
Sistem harus menyediakan mekanisme autentikasi pengguna serta pengelolaan akun, peran (role), dan hak akses berdasarkan konsep Role-Based Access Control (RBAC).

**Acceptance Criteria:**
- Login memerlukan email + password (hash satu arah/bcrypt atau setara).
- Percobaan login gagal dibatasi (rate limiting) — mis. maksimal 5 kali per menit per kombinasi IP + email — untuk mencegah brute-force.
- Session ID diregenerasi saat login berhasil dan diinvalidasi total saat logout (mencegah session fixation).
- Setiap request ke resource milik peran/wilayah lain ditolak di layer backend (403 Forbidden), bukan hanya disembunyikan di UI.
- Super Admin dapat membuat, menonaktifkan, dan mengubah peran akun pengguna lain.

### FR-02 — Master Data Kependudukan
Sistem harus menyediakan fungsi pengelolaan data warga, data Kartu Keluarga, data RT, dan data pengurus sesuai kewenangan masing-masing pengguna.

**User Flow (ringkas):**
1. Ketua RT login → membuka menu Data Warga.
2. Ketua RT menambah/mengubah data warga di wilayah RT-nya.
3. Sistem validasi kelengkapan data sebelum simpan.
4. Perubahan tertentu (mis. status kependudukan) memerlukan verifikasi Sekretaris RW.
5. Data terpusat diperbarui dan dapat dilihat sesuai hak akses masing-masing peran.

**Acceptance Criteria:**
- Setiap warga terhubung ke satu Kartu Keluarga (KK).
- Field NIK dan No. KK wajib unik dan tidak dapat diedit sembarangan setelah verifikasi.
- Riwayat perubahan data (audit trail) tercatat.
- Data dapat di-soft-delete (bukan hard delete) untuk keperluan penelusuran historis.

### FR-03 — Administrasi Surat (Persuratan)
Sistem harus menyediakan layanan pengajuan, verifikasi, persetujuan, penerbitan, serta pemantauan status surat pengantar secara bertahap sesuai alur administrasi RW.

**User Flow:**
1. Warga mengisi formulir pengajuan surat (jenis surat, keperluan) melalui portal.
2. Sistem memvalidasi kelengkapan data dan menerbitkan kode pelacakan (tracking code) unik.
3. Status berjalan bertahap: `SUBMITTED → RT_REVIEW → RW_REVIEW → COMPLETED` (atau `REJECTED` di tahap manapun).
4. Setelah `COMPLETED`, sistem menerbitkan nomor surat resmi.
5. Warga dapat memantau status pengajuan menggunakan tracking code, termasuk tanpa login penuh.

**Acceptance Criteria:**
- Jenis surat minimal mendukung: Surat Pengantar, Surat Keterangan Domisili (dapat diperluas).
- Setiap perubahan status tercatat dengan timestamp.
- Nomor surat resmi hanya diterbitkan pada status `COMPLETED`.

### FR-04 — Laporan & Aspirasi Warga
Sistem harus menyediakan fasilitas penyampaian laporan dan aspirasi warga, pengelolaan status penyelesaian, disposisi, riwayat penanganan laporan, **dilengkapi klasifikasi otomatis berbasis AI**.

**User Flow:**
1. Warga mengirim laporan/aspirasi (judul, deskripsi, lokasi kejadian) melalui web.
2. Sistem menghasilkan nomor tiket unik dan menyimpan laporan dengan status `SUBMITTED`.
3. Mekanisme AI mengklasifikasikan kategori/prioritas laporan → status berubah menjadi `CLASSIFIED`.
4. Pengurus RW meninjau, mendisposisikan, dan mengubah status: `IN_PROGRESS → RESOLVED → CLOSED`.
5. Warga memantau perkembangan laporannya secara real-time melalui nomor tiket.

**Acceptance Criteria:**
- Input teks keluhan disanitasi (sanitized input) untuk mencegah injection/XSS.
- Setiap laporan memiliki jejak status lengkap (submitted_at, resolved_at, dan histori perubahan).
- Keputusan tindak lanjut akhir tetap berada di tangan pengurus RW (AI hanya membantu klasifikasi/prioritisasi, bukan menggantikan keputusan manusia).

### FR-05 — Keuangan RW (Iuran & Kas)
Sistem harus menyediakan pengelolaan data iuran warga, kas masuk, kas keluar, serta penyusunan laporan keuangan RW.

**User Flow:**
1. Ketua RT mencatat pembayaran iuran warga (jenis iuran, nominal, periode) melalui sistem.
2. Sistem memvalidasi data transaksi sebelum menyimpan.
3. Bendahara RW meninjau seluruh data pembayaran dari setiap RT, melakukan rekonsiliasi terhadap dokumen fisik jika diperlukan, dan dapat mengoreksi data.
4. Sistem menghasilkan rekapitulasi & laporan keuangan RW.

**Acceptance Criteria:**
- Tabel master "Jenis Iuran" dapat dikonfigurasi (nama, kode, nominal standar, status aktif) oleh pengurus berwenang.
- Setiap transaksi tercatat dengan siapa yang menginput dan kapan (auditability).
- Laporan keuangan dapat difilter berdasarkan periode dan/atau RT.

### FR-06 — Informasi Publik
Sistem harus menyediakan pengelolaan informasi publik berupa pengumuman, berita, agenda kegiatan, dan informasi lainnya yang dapat diakses sesuai hak akses pengguna.

**Acceptance Criteria:**
- Sekretaris RW/Ketua RW dapat membuat, mengubah, dan menghapus pengumuman/agenda.
- Konten publik dapat diakses warga tanpa perlu login (portal publik).

### FR-07 — Dashboard & Monitoring
Sistem harus menyajikan dashboard, statistik, dan laporan monitoring operasional sesuai dengan peran masing-masing pengguna.

**Acceptance Criteria:**
- Setiap peran (RT, Sekretaris, Bendahara, Ketua RW, Super Admin) memiliki dashboard khusus sesuai kewenangan dan kebutuhan informasinya.
- Data dashboard real-time atau near real-time terhadap perubahan data operasional.

---

## 5. Persyaratan Non-Fungsional (Non-Functional Requirements)

| Kode | Aspek | Kebutuhan Non-Fungsional |
|---|---|---|
| NFR-01 | **Security** | Sistem harus menerapkan autentikasi & otorisasi berbasis RBAC yang ditegakkan di backend (bukan hanya UI). Data sensitif (NIK, No. KK) wajib **dienkripsi at-rest** (mis. AES-256), dengan mekanisme pencarian presisi via hash deterministik (mis. HMAC-SHA256) tanpa membuka data asli. Password disimpan dengan hashing satu arah. Wajib ada rate limiting login dan perlindungan session fixation. |
| NFR-02 | **Performance** | Sistem harus memberikan respons yang memadai selama pengelolaan data dan pelayanan administrasi pada kondisi penggunaan normal (target: response time halaman utama < 2 detik pada beban normal — nilai target spesifik ditentukan saat tahap desain teknis). |
| NFR-03 | **Reliability / Availability** | Sistem harus dapat digunakan secara berkelanjutan untuk mendukung pelayanan administrasi selama jam operasional, dengan strategi backup data berkala. |
| NFR-04 | **Usability** | Antarmuka harus mudah dipahami dan digunakan oleh seluruh pengguna sesuai tingkat kewenangan dan kebutuhannya masing-masing (termasuk pengguna dengan literasi digital terbatas seperti pengurus RT/RW). |
| NFR-05 | **Compatibility** | Sistem harus dapat diakses melalui browser modern di desktop maupun perangkat mobile dengan tampilan responsif (mobile-first direkomendasikan mengingat sebagian besar interaksi warga terjadi via smartphone). |
| NFR-06 | **Maintainability & Scalability** | Struktur aplikasi harus mendukung pemeliharaan, perbaikan, dan pengembangan berkelanjutan. Untuk rebuild ini, arsitektur harus disiapkan agar mampu berkembang dari skala single-RW ke potensi multi-RW/multi-tenant tanpa perombakan total (unlike versi acuan yang SQLite file-based dan cocok untuk skala kecil saja). |
| NFR-07 *(baru)* | **Auditability** | Setiap perubahan data penting (kependudukan, surat, laporan, keuangan) harus tercatat dalam audit trail (siapa, kapan, perubahan apa) untuk mendukung akuntabilitas — ini adalah penguatan dari praktik soft-delete di sistem acuan. |
| NFR-08 *(baru)* | **Data Privacy Compliance** | Penanganan data pribadi (NIK, KK, alamat) harus selaras dengan prinsip perlindungan data pribadi yang berlaku (UU PDP di Indonesia), termasuk minimalisasi data yang ditampilkan di UI dan pembatasan siapa yang bisa melihat data mentah (unmasked). |

---

## 6. Arsitektur & Rekomendasi Teknologi (Tech Stack & Architecture)

### 6.1 Ringkasan Tech Stack Sistem Acuan (untuk konteks)

| Komponen | Teknologi Sistem Acuan |
|---|---|
| Bahasa pemrograman | PHP 8.1 |
| Framework | Laravel 10 (MVC) |
| Basis data | SQLite 3 (file-based) |
| Web server | Nginx |
| Dev environment | Laragon 6 (lokal, Windows) |
| Enkripsi data sensitif | AES-256-CBC (application layer, bawaan Laravel) + HMAC-SHA256 untuk hash pencarian |
| Otorisasi | Laravel Policy (`$this->authorize()`) |

### 6.2 Rekomendasi Tech Stack untuk Rebuild

Sistem acuan cocok untuk skala riset/single-RW, tetapi untuk rebuild yang lebih matang dan siap produksi, berikut rekomendasi:

| Layer | Rekomendasi | Justifikasi |
|---|---|---|
| **Backend/Framework** | Tetap **Laravel (versi LTS terbaru)** atau alternatif Node.js (NestJS) bila tim ingin unifikasi bahasa dengan frontend modern | Laravel sudah terbukti sesuai untuk domain ini (Eloquent ORM, Policy, ekosistem matang); pertahankan investasi tim jika familiar |
| **Basis Data** | **PostgreSQL/MySQL** (server-based RDBMS), bukan SQLite | SQLite file-based tidak ideal untuk concurrency multi-user produksi dan sulit di-scale; PostgreSQL juga punya dukungan native untuk enkripsi kolom & indexing yang lebih baik |
| **Frontend** | Laravel Blade + Alpine.js/Livewire (jika tetap monolitik) **atau** SPA terpisah dengan React/Vue + API Laravel (jika ingin kesiapan mobile app di masa depan) | Pilihan tergantung roadmap mobile app; SPA + API lebih future-proof untuk saran pengembangan PWA/mobile pada skripsi |
| **Autentikasi** | Laravel Sanctum/Fortify + RBAC (Spatie Laravel-Permission direkomendasikan) | Mempercepat implementasi RBAC yang robust dan teruji komunitas |
| **Enkripsi data sensitif** | Pertahankan pola AES-256 at rest untuk NIK/KK + hash deterministik untuk pencarian, tapi kelola key management via secret manager (bukan hardcode di `.env`) | Praktik keamanan sistem acuan sudah baik, tinggal diperkuat dari sisi key management |
| **Klasifikasi Laporan (AI)** | Layanan klasifikasi terpisah (mikroservice) memanggil LLM/API klasifikasi teks, atau model NLP ringan yang di-hosting terpisah dari core app | Memisahkan concern AI dari core CRUD memudahkan maintenance & upgrade model tanpa mengganggu sistem inti |
| **Web Server** | Nginx + PHP-FPM (tetap) | Sudah tepat, kompatibel dengan Laravel |
| **Hosting/Infra** | VPS/cloud (mis. AWS/GCP/DigitalOcean) dengan CI/CD, bukan lokal Laragon | Laragon hanya cocok untuk development lokal, bukan produksi |
| **Monitoring & Logging** | Laravel Telescope (dev) + logging terpusat (mis. Sentry) untuk produksi | Mendukung NFR maintainability & reliability |
| **Testing** | PHPUnit/Pest untuk unit & feature test, dilengkapi automated testing (sistem acuan hanya memakai Black Box Testing manual) | Meningkatkan kualitas rebuild dibanding versi awal |

### 6.3 Entitas Data Utama (berdasarkan ERD/LRS skripsi)

Struktur data dikelompokkan ke dalam 4 domain utama:

**a. Autentikasi & RBAC**
- `User` (akun pengguna: username, email, password hash, role_id, status, last_login_at)
- `Role` / `Jabatan Organisasi` (peran & hak akses)

**b. Kependudukan (Core Identity & Demographics)**
- `KartuKeluarga` (no_kk terenkripsi, no_kk_hash, rt_code, alamat terenkripsi, status kepemilikan rumah)
- `Warga` (nik terenkripsi, nik_hash, relasi ke no_kk, nama, jenis kelamin, tempat/tanggal lahir, pekerjaan, nomor HP terenkripsi, status hubungan keluarga, status sosio-ekonomi, status_warga: TETAP/KONTRAK/PINDAH/MENINGGAL)

**c. Layanan Warga (Persuratan & Laporan)**
- `PengajuanSurat` (tracking_code, nik pemohon, nomor_surat, jenis_surat, keperluan, current_status: SUBMITTED→RT_REVIEW→RW_REVIEW→COMPLETED/REJECTED, tanggal pengajuan/selesai)
- `LaporanAspirasi` (ticket_number, nik pelapor terenkripsi, kanal_laporan, judul, teks_keluhan (sanitized), lokasi_kejadian, current_status: SUBMITTED→CLASSIFIED→IN_PROGRESS→RESOLVED→CLOSED, submitted_at, resolved_at)

**d. Keuangan RW (Iuran & Universal Ledger)**
- `JenisIuran` (name, code, default_amount, description, is_active) — tabel master
- Transaksi Iuran / Kas (relasi ke JenisIuran, warga/RT pembayar, nominal, periode, status validasi)

**e. Pendukung**
- Audit log / activity log (pencatatan aktivitas & perubahan data)
- Informasi Publik (pengumuman, berita, agenda)

> **Catatan untuk tim rebuild:** seluruh entitas menerapkan soft-delete (`deleted_at`) — pola ini sebaiknya dipertahankan untuk mendukung NFR-07 (Auditability).

### 6.4 Prinsip Arsitektur yang Wajib Dipertahankan dari Sistem Acuan

1. **Enkripsi at-rest untuk data sensitif** (NIK, No. KK) — jangan simpan plaintext.
2. **Otorisasi ditegakkan di backend**, bukan hanya UI — cegah IDOR dengan area scoping antar-RT.
3. **Rate limiting & session hardening** pada mekanisme login.
4. **Sanitasi input** pada field teks bebas (mis. teks keluhan laporan).
5. **Soft delete** untuk semua entitas transaksional demi audit trail.

---

## 7. Rekomendasi Pengembangan Lanjutan (dari Saran Skripsi)

Empat area berikut disebutkan eksplisit dalam skripsi sebagai saran pengembangan dan relevan untuk dipertimbangkan pada roadmap rebuild (di luar cakupan versi awal, lihat Bagian 3.2):

1. Analisis & visualisasi data yang lebih komprehensif (statistik pelayanan, rekap laporan, grafik iuran).
2. Penyempurnaan modul persuratan: tambahan jenis surat, approval workflow lebih matang, notifikasi pengguna, arsip digital.
3. Aplikasi mobile (Android/iOS) atau Progressive Web App (PWA).
4. Integrasi dengan sistem administrasi kependudukan pemerintah daerah.

---

## 8. Ketentuan Output

Dokumen ini disusun dalam format Markdown terstruktur sesuai dengan hasil analisis mendalam terhadap naskah skripsi 109 halaman (BAB I–IV), mencakup abstrak, landasan teori, hasil analisis kebutuhan (Tabel III.1–III.5), spesifikasi teknis (Tabel III.6–III.7), pemodelan proses (Use Case, Activity, Sequence Diagram), perancangan basis data (ERD, LRS, struktur tabel III.8–III.13), perancangan antarmuka (mockup portal & dashboard), serta kesimpulan dan saran (BAB IV).
