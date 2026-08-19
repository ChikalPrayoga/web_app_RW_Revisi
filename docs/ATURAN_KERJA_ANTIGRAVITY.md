# Dokumen Acuan Teknis — SIM Layanan Warga RW 047
## Untuk AI Agent (Antigravity)

**Fungsi dokumen:** source of truth teknis/operasional. Ini BUKAN prompt — ini spesifikasi yang dirujuk saat menyusun instruksi kerja harian. Isi dokumen ini WAJIB dipatuhi tanpa pengecualian kecuali ada instruksi eksplisit dari reviewer manusia yang menyatakan sebaliknya.

Dokumen pasangan `REFERENSI_AI_CHAT.md` berisi konteks strategis/rasional keputusan — kalau butuh alasan "kenapa", rujuk ke sana. Dokumen ini murni "apa" dan "bagaimana".

---

## 1. Konteks Proyek (Ringkas)

SIM Layanan Warga RW 047 — Laravel 10.x, PHP 8.1+, MySQL (runtime) / SQLite (testing), Modular Monolith, Blade + Tailwind + Vite, Sanctum (auth), RBAC custom, enkripsi PII AES-256-CBC + hash HMAC-SHA256. Modul Autentikasi/RBAC dan Kependudukan (Warga + Kartu Keluarga) **sudah selesai dan production-ready** — jadikan sebagai referensi pola untuk semua modul baru.

---

## 2. Aturan Kerja Non-Negotiable

```
1. JANGAN pernah menjalankan `git commit` atau `git push`. Commit adalah hak Human Reviewer.
   Setelah selesai satu task, STOP dan laporkan ringkasan perubahan + hasil test.

2. JANGAN mengubah kontrak REST API Sprint 5 (route, request/response shape) tanpa
   sepengetahuan reviewer.

3. Pola arsitektur WAJIB konsisten: Controller → Service → Repository/Model.
   Web controller memanggil Service Layer langsung (bukan fetch ke REST API sendiri).

4. RBAC pakai sistem custom (role_id FK di users, method hasRole()/hasAnyRole() di
   model User). JANGAN install/pakai Spatie Permission atau paket RBAC lain.

5. Semua tabel data utama WAJIB SoftDeletes. Hard-delete dilarang total.

6. Data PII (NIK, No KK, dan data sensitif sejenis) WAJIB dienkripsi AES-256-CBC via
   DataEncryptionService. Identifier route WAJIB pakai kolom *_hash (HMAC-SHA256,
   via DataEncryptionService::deterministicHash()). TIDAK PERNAH plaintext PII di
   URL, query string, atau log aplikasi.

7. File JS sisi Blade HANYA untuk UX progressive enhancement (validasi format,
   prevent double-submit, character counter, dsb). DILARANG fetch() ke endpoint
   bisnis dari file JS front-end — kecuali endpoint publik non-sensitif yang memang
   didesain publik (contoh: tracking status surat by kode).

8. JANGAN membangun apapun yang termasuk daftar OUT-OF-SCOPE (Bagian 3), walau
   terasa melengkapi fitur yang sedang dikerjakan. Kalau ada permintaan yang
   berpotensi melebar ke situ, STOP dan tanyakan ke reviewer dulu — jangan asumsi.

9. Modul Laporan & Aspirasi adalah SATU-SATUNYA tempat yang boleh memanggil API
   atau layanan pihak ketiga (untuk klasifikasi AI). Semua modul lain WAJIB mandiri
   tanpa dependensi eksternal.

10. SETIAP task yang mengubah/menambah kode WAJIB diakhiri dengan:
    - `php artisan test` → laporkan jumlah passed/failed
    - `./vendor/bin/pint --test` → laporkan violation (0 diharapkan)
    - Jika ada perubahan asset: `npm run build` → laporkan hasil build

11. Sebelum membuat modul baru, audit struktur `app/Modules/Kependudukan/` yang
    sudah ada. JANGAN duplikasi pola/struktur berbeda — modul baru harus punya
    struktur folder yang sama persis (Controllers/Services/Requests/Policies).

12. Kalau ragu, requirement ambigu, atau test gagal setelah 2x percobaan
    perbaikan: STOP, jangan menebak-nebak. Laporkan detail error lengkap
    (pesan error, stack trace, file yang diubah) ke reviewer.
```

---

## 3. Ruang Lingkup — Tabel Referensi Cepat

### In-Scope (9 area, semua wajib)
| # | Modul | Status |
|---|---|---|
| 1 | Autentikasi & RBAC | Selesai |
| 2 | Kependudukan (Warga, KK, verifikasi berjenjang) | Selesai — jadi pola referensi |
| 3 | Persuratan (2 jenis surat, verifikasi RT→RW, tracking publik) | Belum |
| 4 | Laporan & Aspirasi (klasifikasi AI asinkron) | Belum |
| 5 | Keuangan RW (iuran & kas, approval RT→Bendahara, anti-duplikasi) | Belum |
| 6 | Informasi Publik (pengumuman, berita, agenda) | Belum |
| 7 | Dashboard per peran | Belum |
| 8 | Portal Warga (self-service publik) | Belum |

### Out-of-Scope (JANGAN dibangun)
Aplikasi mobile native/PWA, integrasi Dukcapil/pemerintah, payment gateway iuran online, notifikasi otomatis (push/WA/SMS), multi-RW/multi-tenant aktif sebagai fitur, approval workflow surat kompleks di luar 2 jenis dasar, document management system skala besar.

---

## 4. Struktur Direktori Wajib (ikuti pola Kependudukan persis)

```
app/Modules/<NamaModul>/
├── Controllers/
│   ├── <NamaModul>Controller.php          ← REST API v1
│   └── <NamaModul>WebController.php       ← Blade web views (panggil Service langsung)
├── Services/
│   └── <NamaModul>Service.php             ← business logic, area scoping, enkripsi
├── Requests/
│   ├── Store<Entity>Request.php
│   ├── Update<Entity>Request.php
│   └── List<Entity>Request.php
└── Policies/
    └── <Entity>Policy.php

resources/views/<nama-modul-kebab>/
├── index.blade.php    ← table desktop / card mobile
├── create.blade.php
├── show.blade.php
└── edit.blade.php     (jika relevan)

tests/Feature/<NamaModul>/
├── <NamaModul>WebTest.php
└── <NamaModul>EndpointTest.php
```

Model baru masuk `app/Models/`, ikuti pola `Warga.php`/`KartuKeluarga.php` (SoftDeletes, enkripsi field sensitif via cast/mutator custom, kolom `*_hash` untuk lookup).

---

## 5. Spesifikasi Teknis per Modul

### 5.1 Persuratan
- **Tabel:** `pengajuan_surats` — `tracking_code` (unik, untuk tracking publik), `warga_id` (FK → `wargas.id`), `nomor_surat` (nullable), `jenis_surat` (enum: `SURAT_PENGANTAR`, `SURAT_KETERANGAN_DOMISILI`), `keperluan`, `current_status` (enum: `SUBMITTED`, `RT_REVIEW`, `RW_REVIEW`, `COMPLETED`, `REJECTED`), `catatan_penolakan` (nullable), `tanggal_pengajuan`, `tanggal_selesai` (nullable), timestamps, SoftDeletes. (Area scoping ditegakkan melalui relasi Warga/KK ke `rt_code`).
- **Service (`SuratService`):** `submitPengajuan(array $data)` (public submission via NIK lookup ke `warga_id`), `trackByKode(string $trackingCode)`, `listPengajuan(User $user, array $filters)` (area-scoped via relasi Warga/KK), `reviewByRt()`, `verifyByRw()`.
- **REST API (`/api/v1/surat/pengajuan`):** submit (publik, input: `nik`, `jenis_surat`, `keperluan`), track by kode (publik), list (auth, area-scoped), review RT, verifikasi RW.
- **Frontend:** form pengajuan publik, halaman tracking (input kode → status + catatan penolakan jika REJECTED), list pengajuan pengurus (table/card), halaman review RT, halaman verifikasi RW. Update sidebar: link Persuratan aktif.
- **Test:** `SuratWebTest.php`, `SuratEndpointTest.php` — guest access ke form publik & tracking, public submission validation (NIK terdaftar/tidak), RBAC forbidden non-RT/RW, area scoping, alur status lengkap kedua jenis surat, validasi field, 409 conflict pada aksi ganda.

### 5.2 Keuangan RW (Iuran & Kas)
- **Tabel:**
  - `iuran_types` — `name`, `code` (unik), `default_amount`, `description`, `is_active`, timestamps.
  - `catatan_iurans` — `iuran_id` (PK), `kartu_keluarga_id` (FK → `kartu_keluargas.id`), `iuran_type_id` (FK → `iuran_types.id`), `nominal`, `periode_bulan`, `periode_tahun`, `tanggal_pembayaran` (nullable), `recorded_by_user_id` (FK → `users.id`, Ketua RT), `approved_by_user_id` (FK → `users.id`, Bendahara RW, nullable), `approved_at` (nullable), `status` (enum: `PENDING`, `APPROVED`, `REJECTED`), `payment_proof_path` (nullable), `rejection_notes` (nullable), timestamps, SoftDeletes. (Lookup KK via input `no_kk` → HMAC-SHA256 hash → `kartu_keluargas.no_kk_hash` → `kartu_keluargas.id`; area scoping RT diverifikasi di Service).
  - `kas_keluars` — `id` (PK), `kategori`, `keterangan`, `nominal`, `tanggal_pengeluaran`, `bukti_path` (nullable), `recorded_by_user_id` (FK → `users.id`, Bendahara RW), `status` (enum: `PENDING`, `APPROVED`, `REJECTED`), `approved_by_user_id` (FK → `users.id`, Ketua RW, nullable), `approved_at` (nullable), `rejection_notes` (nullable), timestamps, SoftDeletes. (Tanpa relasi KK/Warga, murni pengeluaran operasional RW).
- **Constraint anti-duplikasi iuran:** Kombinasi (`kartu_keluarga_id`, `iuran_type_id`, `periode_bulan`, `periode_tahun`) wajib unik untuk transaksi aktif (`PENDING`, `APPROVED`, dan `deleted_at IS NULL`). Row `REJECTED` dan soft-deleted dikecualikan agar dapat dicatat ulang. Di MySQL via generated column `guard_col` + unique index; di SQLite (testing) via partial unique index. Kas keluar tidak memerlukan anti-duplikasi.
- **Service (`KeuanganService`):** `catatIuran()`, `approveIuran()`, `catatKasKeluar()`, `approveKasKeluar()`, `rekapIuran()`, `rekapGabungan()`.
- **REST API (`/api/v1/...`):**
  - `/iuran-types` (GET, all auth)
  - `/catatan-iuran` (POST, input: `no_kk`, `iuran_type_id`, `nominal`, `periode_bulan`, `periode_tahun`, `tanggal_pembayaran`, role: KETUA_RT)
  - `/catatan-iuran/{id}/approve` (PATCH, action: APPROVE/REJECT, role: BENDAHARA_RW)
  - `/catatan-iuran/rekapitulasi` (GET, role: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN)
  - `/kas-keluar` (POST, input: `kategori`, `keterangan`, `nominal`, `tanggal_pengeluaran`, `bukti_path`, role: BENDAHARA_RW; GET, role: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN)
  - `/kas-keluar/{id}/approve` (PATCH, action: APPROVE/REJECT, role: KETUA_RW — dual-control)
  - `/keuangan/rekapitulasi` (GET, rekap gabungan pemasukan + pengeluaran + saldo, role: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN)
- **Frontend:** form pencatatan iuran (RT), form pencatatan kas keluar (Bendahara), list approval iuran (Bendahara), list approval kas keluar (Ketua RW), halaman rekapitulasi iuran & gabungan. Update sidebar: link Keuangan aktif.
- **Test:** `KeuanganWebTest.php`, `KeuanganEndpointTest.php` — RBAC (Ketua RT catat iuran, Bendahara approve iuran & catat kas keluar, Ketua RW approve kas keluar), dual-control enforcement (Bendahara tidak bisa self-approve kas keluar), anti-duplikasi iuran (409 Conflict pada insert kedua KK+periode sama), area scoping RT pada iuran, rekapitulasi iuran & rekapitulasi gabungan (kalkulasi saldo = pemasukan APPROVED - pengeluaran APPROVED).

### 5.3 Informasi Publik
- **Tabel:** `informasi_publiks` — `tipe` (enum: `pengumuman`, `berita`, `agenda`), `judul`, `isi`, `tanggal_publikasi`, `dipublikasikan_oleh` (FK users), timestamps, SoftDeletes. (Boleh satu tabel dengan kolom `tipe`, tidak perlu 3 tabel terpisah.)
- **Service:** CRUD standar, filter by tipe untuk publik.
- **Frontend:** CRUD oleh pengurus (Sekretaris/Ketua RW — cek policy), halaman publik daftar info tanpa login (paginated, filter tipe).
- **Test:** akses publik tanpa auth berhasil melihat list, CRUD pengurus RBAC-protected.

### 5.4 Portal Warga
- Halaman komposisi publik yang menggabungkan: tracking surat (reuse endpoint 5.1), daftar info publik (reuse endpoint 5.3), dan (kalau waktu cukup) form submit laporan/aspirasi (reuse endpoint 5.5) — **tidak butuh logic baru besar**, ini adalah landing page publik yang merangkai endpoint yang sudah ada.
- **Test:** semua akses tanpa login berhasil menampilkan data dari modul terkait.

### 5.5 Laporan & Aspirasi Warga

> **Catatan Penyelarasan Scope Final:** Fitur Klasifikasi AI telah dipindahkan ke **OUT-OF-SCOPE v1** (mengacu `PROJECT_SCOPE_BOUNDARIES.md` dan skripsi final). Modul diimplementasikan sebagai Laporan & Aspirasi murni tanpa dependensi API eksternal atau queue AI, dengan state machine `SUBMITTED → IN_PROGRESS → RESOLVED → CLOSED`.

- **Tabel:** `laporan_aspirasis` — `ticket_number` (unik), `warga_id` (FK nullable), `judul_laporan`, `teks_keluhan` (sanitized), `lokasi_kejadian` (nullable), `current_status` (enum: `SUBMITTED`, `IN_PROGRESS`, `RESOLVED`, `CLOSED`), `catatan_tindak_lanjut` (nullable), `submitted_at`, `resolved_at` (nullable), timestamps, SoftDeletes.
- **Service (`LaporanAspirasiService`):** submit publik dengan nomor tiket unik & opsional NIK lookup, tracking publik by ticket, list dengan filter status penanganan, update status penanganan dengan catatan penyelesaian, dan soft delete.
- **Audit Trail:** terpusat via `LaporanAspirasiObserver` sesuai standar arsitektur sistem.
- **Frontend & Portal:** form pengaduan publik & pelacakan status tiket di Portal Warga, dashboard list & detail penanganan status bagi pengurus RW.
- **Test:** Feature tests lengkap (Service, Endpoint, Web, Security, Portal).

### 5.6 Dashboard per Peran
- **Service (`DashboardService`):** agregasi read-only dari semua modul — total warga, warga menunggu verifikasi, total KK, pengajuan surat aktif per status, saldo kas & transaksi bulan berjalan, jumlah laporan per kategori/status.
- **Role-aware:** setiap peran (KETUA_RT, SEKRETARIS_RW, KETUA_RW, BENDAHARA_RW, SUPER_ADMIN) melihat subset statistik relevan dengan kewenangannya (KETUA_RT hanya data wilayahnya, reuse area scoping yang sudah ada).
- **Dikerjakan terakhir** karena butuh semua modul lain sudah punya data untuk diagregasi.

---

## 6. Definition of Done — Checklist per Modul

Setiap modul dianggap selesai HANYA kalau semua berikut terpenuhi:

- [ ] Struktur folder mengikuti pola Bagian 4 persis
- [ ] Migration + Model dengan SoftDeletes, enkripsi PII (jika ada field sensitif)
- [ ] Service Layer menegakkan area scoping (bukan hanya di Controller/Policy)
- [ ] REST API mengikuti pola Request/Resource/Policy yang sama dengan Kependudukan
- [ ] Frontend Blade: table desktop + card mobile (pola sama dengan Kependudukan), sidebar link aktif (bukan `href="#"`)
- [ ] Test Feature (Web + Endpoint) mencakup: guest/RBAC access, area scoping, alur status penuh, validasi field
- [ ] `php artisan test` — 0 failed
- [ ] `./vendor/bin/pint --test` — 0 violation
- [ ] Tidak ada NIK/No KK/data PII plaintext di URL, log, atau response yang tidak seharusnya
- [ ] Tidak ada fitur di luar spesifikasi Bagian 5 yang ikut terbangun tanpa diminta

---

## 7. Peta Modul → Hari Kerja (untuk konteks urutan, bukan tenggat kaku per jam)

| Hari | Modul |
|---|---|
| 1 | StoreWargaRequest (Kependudukan) + fondasi Persuratan (migration/model/service) |
| 2 | Persuratan (frontend + REST API + test) |
| 3 | Keuangan RW |
| 4 | Informasi Publik + Portal Warga |
| 5 | Laporan & Aspirasi + Klasifikasi AI |
| 6 | Dashboard per peran + hardening (tanpa fitur baru setelah siang) |
| 7 | Freeze — tidak ada task Antigravity baru kecuali bug blocker yang dilaporkan reviewer |

