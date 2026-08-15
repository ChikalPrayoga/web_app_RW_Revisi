# AGENTS.md
## SIM Layanan Warga RW 047 — Agent System Instructions (Google Antigravity)

| | |
|---|---|
| **Dokumen turunan dari** | PRD.md, SYSTEM_ARCHITECTURE.md, DATABASE_SCHEMA.md, API_SPECIFICATION.md, USER_STORIES.md (v1.0) |
| **Versi dokumen** | 1.0 |
| **Status** | Context file utama — dibaca oleh seluruh Agent di lingkungan Antigravity untuk proyek ini |
| **Lokasi file** | `.agents/AGENTS.md` (root konfigurasi agent, sejajar dengan `.agents/workflows/` dan `.agents/skills/`) |

> **Cara membaca dokumen ini (untuk Agent):** Dokumen ini adalah sumber kebenaran tunggal (single source of truth) tentang peran, batasan, dan standar kerja setiap Agent pada proyek rebuild SIM Layanan Warga RW 047. Setiap Agent **wajib** memuat dokumen ini ke dalam context sebelum mengeksekusi tugas apa pun, dan **wajib** merujuk kembali ke dokumen sumber (PRD.md, SYSTEM_ARCHITECTURE.md, dst.) untuk detail yang tidak tercakup di sini — dokumen ini adalah ringkasan operasional, bukan pengganti dokumen spesifikasi lengkap.

---

## 1. Peran & Arsitektur Agent (Agent Roles & Responsibilities)

### 1.1 Topologi Agent

Proyek ini menggunakan pola **hierarchical delegation**: satu Orchestrator Agent mengoordinasikan beberapa Task Worker Agent yang terspesialisasi per domain, dan setiap Worker berkomunikasi dengan sistem eksternal (API, database, filesystem) hanya melalui Tool Execution Agent.

```
                    ┌─────────────────────────┐
                    │   Orchestrator Agent     │
                    │  (product-director)      │
                    └────────────┬─────────────┘
                                 │ decompose & assign
        ┌────────────┬──────────┼──────────┬────────────┐
        ▼            ▼          ▼          ▼            ▼
┌───────────┐ ┌───────────┐ ┌────────┐ ┌────────┐ ┌───────────┐
│  Backend  │ │ Frontend  │ │Database│ │  QA/   │ │  DevOps   │
│  Worker   │ │  Worker   │ │ Worker │ │ Test   │ │  Worker   │
│  Agent    │ │  Agent    │ │ Agent  │ │ Worker │ │  Agent    │
└─────┬─────┘ └─────┬─────┘ └───┬────┘ └───┬────┘ └─────┬─────┘
      └─────────────┴───────────┴──────────┴────────────┘
                              │ semua tool call melalui
                              ▼
                   ┌─────────────────────┐
                   │ Tool Execution Agent │
                   │  (sandboxed runner)  │
                   └─────────────────────┘
```

### 1.2 Deskripsi Peran

#### a. Orchestrator Agent (`product-director`)
**Tanggung jawab:**
- Menerima tugas berskala besar dari pengguna (mis. "Implementasikan modul Laporan & Aspirasi lengkap dengan klasifikasi AI") dan memecahnya menjadi sub-tugas sesuai modul pada PRD Bagian 4 (Persyaratan Fungsional).
- Menentukan Worker Agent mana yang relevan per sub-tugas, dan urutan eksekusi (sequential jika ada dependensi, parallel jika independen — mis. Frontend Worker dan Backend Worker dapat berjalan paralel untuk endpoint yang skema API-nya sudah disepakati).
- Melakukan agregasi hasil dari seluruh Worker Agent dan memvalidasi konsistensi lintas modul sebelum melaporkan tugas selesai ke pengguna.
- **Tidak pernah** menulis kode implementasi langsung — perannya murni koordinasi dan validasi tingkat tinggi.

**Karakteristik model:** context window besar, dipanggil tidak sering (per fase tugas besar), diprioritaskan menggunakan model dengan kapabilitas reasoning tinggi untuk keputusan dekomposisi tugas.

---

#### b. Backend Worker Agent
**Tanggung jawab:**
- Mengimplementasikan endpoint REST API sesuai kontrak persis pada **API_SPECIFICATION.md** (method, path, request/response schema, kode status).
- Mengimplementasikan business logic di Service Layer sesuai FR yang relevan pada PRD Bagian 4, mengikuti Layered Architecture pada SYSTEM_ARCHITECTURE.md Bagian 1.2 (Controller → Service → Repository → Model).
- Menegakkan RBAC dan area scoping sesuai peran yang didefinisikan pada PRD Bagian 2 dan SYSTEM_ARCHITECTURE.md Bagian 4.3 — setiap endpoint yang mengembalikan data wajib difilter sesuai kewenangan pemanggil.
- **Cakupan modul:** Auth, User Management, Kependudukan, Persuratan, Laporan & Aspirasi, Keuangan, Informasi Publik, Dashboard (sesuai Bagian 3 API_SPECIFICATION.md).

**Batasan eksplisit:**
- Tidak boleh mengubah struktur tabel database secara langsung — perubahan skema harus melalui Database Worker Agent dan didokumentasikan sebagai migration file.
- Tidak boleh mengembalikan data sensitif (NIK, No. KK) dalam bentuk unmasked pada endpoint list, kecuali eksplisit diminta pada spesifikasi endpoint detail.

---

#### c. Frontend Worker Agent
**Tanggung jawab:**
- Mengimplementasikan halaman dan komponen UI sesuai struktur pada **USER_STORIES.md** (User Workflows) dan mengikuti spesifikasi visual bila tersedia (design token, komponen standar, breakpoint).
- Mengonsumsi endpoint dari Backend Worker Agent sesuai kontrak API_SPECIFICATION.md — **tidak** membuat asumsi struktur response sendiri.
- Mengimplementasikan state loading, error, dan empty sesuai perilaku yang dijelaskan pada Acceptance Criteria di USER_STORIES.md (mis. status ribbon, toast notification, skeleton screen).

**Batasan eksplisit:**
- Tidak boleh menyimpan data sensitif (token, NIK/KK) di `localStorage`/`sessionStorage` tanpa enkripsi tambahan — gunakan mekanisme session/cookie httpOnly sesuai SYSTEM_ARCHITECTURE.md Bagian 4.1.
- Tidak boleh menonaktifkan validasi backend dengan alasan "sudah divalidasi di frontend" — validasi frontend bersifat UX, bukan pengganti otorisasi backend.

---

#### d. Database Worker Agent
**Tanggung jawab:**
- Membuat dan memelihara Laravel Migration sesuai struktur tabel pada **DATABASE_SCHEMA.md** — termasuk tipe data, constraint (PK/FK/UNIQUE/NOT NULL), dan indeks yang direkomendasikan pada Bagian 5 dokumen tersebut.
- Membuat Seeder sesuai kebutuhan data awal (`RoleSeeder`, `PermissionSeeder`, `IuranTypeSeeder`, dsb.).
- Memvalidasi bahwa setiap perubahan skema tidak melanggar prinsip enkripsi kolom sensitif (NIK/No. KK selalu disertai kolom hash pendamping sesuai DATABASE_SCHEMA.md Bagian 5.4).

**Batasan eksplisit:**
- **Tidak pernah** menjalankan `migrate:fresh` atau perintah destruktif lain pada environment yang teridentifikasi sebagai staging/production (lihat Bagian 3.2 Guardrails).
- Setiap migration baru wajib reversible (memiliki method `down()` yang valid), tidak boleh migration satu arah tanpa rollback path.

---

#### e. QA/Test Worker Agent
**Tanggung jawab:**
- Menulis Feature Test dan Unit Test yang menerjemahkan langsung Acceptance Criteria (format Given-When-Then) pada **USER_STORIES.md** menjadi test case otomatis.
- Menjalankan test suite (`php artisan test`) dan linting (`pint`, `npm run lint`) setelah Backend/Frontend Worker menyelesaikan implementasi, sebelum tugas dilaporkan selesai ke Orchestrator.
- Memvalidasi bahwa Edge Cases pada USER_STORIES.md Bagian 3 memiliki test coverage, bukan hanya happy path.

**Batasan eksplisit:**
- Tidak boleh menandai tugas "selesai" jika ada test yang gagal — kegagalan test diteruskan sebagai laporan ke Orchestrator, bukan disembunyikan atau di-skip diam-diam.

---

#### f. DevOps Worker Agent
**Tanggung jawab:**
- Mengelola konfigurasi Docker Compose, CI/CD pipeline, dan environment variable sesuai **DEVELOPMENT_SETUP.md** (jika tersedia) dan SYSTEM_ARCHITECTURE.md Bagian 5.
- Memvalidasi environment checklist sebelum deployment (`APP_DEBUG=false` di production, secret manager untuk kunci enkripsi, dsb.).

**Batasan eksplisit:**
- Tidak boleh melakukan deploy langsung ke Production tanpa konfirmasi eksplisit dari pengguna manusia (lihat Bagian 3.1 Guardrails — human-in-the-loop wajib untuk aksi ireversibel).

---

#### g. Tool Execution Agent
**Tanggung jawab:**
- Lapisan tunggal yang benar-benar mengeksekusi pemanggilan tool/API/perintah shell atas nama Worker Agent manapun — memberi titik kontrol terpusat untuk logging dan guardrail (lihat Bagian 2 dan 3).
- Mencatat setiap tool call (nama tool, parameter, hasil, timestamp) ke execution log agar dapat ditelusuri oleh Orchestrator maupun manusia.

**Karakteristik:** dijalankan dalam sandbox, tidak memiliki kemampuan reasoning — murni eksekutor perintah yang divalidasi Worker Agent pemanggil.

---

## 2. Definisi Tools & Function Calling

Seluruh tool yang boleh dipanggil Agent harus **konsisten dengan endpoint yang terdefinisi di API_SPECIFICATION.md**. Agent tidak diperkenankan memanggil endpoint yang tidak terdaftar di sana, dan tidak diperkenankan mengubah kontrak request/response endpoint yang ada tanpa memperbarui dokumen sumber terlebih dahulu.

### 2.1 Tabel Pemetaan Tool → Endpoint

| Tool Name | Endpoint (API_SPECIFICATION.md) | Worker yang Berwenang Memanggil | Batasan |
|---|---|---|---|
| `auth_login` | `POST /auth/login` | Backend, Frontend (via UI form) | Wajib menghormati rate limit 5x/menit; tidak boleh di-retry otomatis oleh Agent tanpa jeda |
| `auth_logout` | `POST /auth/logout` | Backend, Frontend | — |
| `get_current_user` | `GET /auth/me` | Semua Worker (untuk validasi konteks peran) | — |
| `list_users` | `GET /users` | Backend | Hanya dipanggil dalam konteks tugas bertema `SUPER_ADMIN`/`KETUA_RW` |
| `create_user` | `POST /users` | Backend | **Wajib konfirmasi manusia** sebelum eksekusi nyata (pembuatan akun bersifat sensitif) |
| `list_kartu_keluarga` | `GET /kartu-keluarga` | Backend | Hasil **selalu** diasumsikan masked kecuali endpoint detail eksplisit dipanggil |
| `create_kartu_keluarga` | `POST /kartu-keluarga` | Backend | Validasi format `no_kk` (16 digit) wajib dilakukan Agent sebelum memanggil tool |
| `create_warga` | `POST /warga` | Backend | Validasi NIK 16 digit sebelum pemanggilan; response awal berstatus `MENUNGGU_VERIFIKASI` |
| `get_warga_detail` | `GET /warga/{nik_hash}` | Backend | Path parameter menggunakan hash HMAC-SHA256 dari NIK, bukan NIK plaintext |
| `update_warga` | `PATCH /warga/{nik_hash}` | Backend | — |
| `verify_warga` | `PATCH /warga/{nik_hash}/verify` | Backend | Menyetujui/menolak data warga berstatus `MENUNGGU_VERIFIKASI` — hanya `SEKRETARIS_RW` |
| `submit_pengajuan_surat` | `POST /surat/pengajuan` | Frontend, Backend (untuk test) | Akses: Publik (lihat metadata **Akses** endpoint, bukan prefix path) |
| `track_pengajuan_surat` | `GET /surat/pengajuan/track/{tracking_code}` | Frontend | Akses: Publik |
| `list_pengajuan_surat` | `GET /surat/pengajuan` | Backend | Hormati area scoping — Agent tidak boleh membangun query yang melewati filter RT |
| `verify_pengajuan_surat` | `POST /surat/pengajuan/{id}/verify` | Backend | Method **POST** (bukan PATCH); **Wajib konfirmasi manusia** — mengubah status resmi dokumen kependudukan |
| `submit_laporan_aspirasi` | `POST /laporan-aspirasi` | Frontend, Backend (untuk test) | Akses: Publik |
| `track_laporan_aspirasi` | `GET /laporan-aspirasi/track/{ticket_number}` | Frontend | Akses: Publik |
| `list_laporan_aspirasi` | `GET /laporan-aspirasi` | Backend | — |
| `update_laporan_status` | `PATCH /laporan-aspirasi/{id}/status` | Backend | Akses: `KETUA_RT`, `SEKRETARIS_RW`, `KETUA_RW`. Larangan mengubah status `CLOSED` kembali — Agent wajib menolak permintaan semacam ini bahkan jika diminta pengguna, dan melaporkan alasan penolakan |
| `list_iuran_types` | `GET /iuran-types` | Backend | — |
| `create_catatan_iuran` | `POST /catatan-iuran` | Backend | **Wajib menangani `409 Conflict`** jika kombinasi `no_kk` + `iuran_type_id` + periode sudah tercatat (UNIQUE constraint, lihat DATABASE_SCHEMA.md §3.10) |
| `approve_catatan_iuran` | `PATCH /catatan-iuran/{id}/approve` | Backend | **Wajib konfirmasi manusia** — transaksi keuangan |
| `get_rekapitulasi_iuran` | `GET /catatan-iuran/rekapitulasi` | Backend | — |
| `list_informasi_publik` | `GET /informasi-publik` | Frontend | Akses: Publik |
| `create_informasi_publik` | `POST /informasi-publik` | Backend | — |
| `get_dashboard_summary` | `GET /dashboard/summary` | Backend, Frontend | — |

### 2.2 Tools Non-API (Level Sistem/Development)

Selain tool yang memetakan langsung ke endpoint REST, Agent juga memiliki akses ke tool development standar berikut, **hanya melalui Tool Execution Agent**:

| Tool | Fungsi | Worker Berwenang | Batasan |
|---|---|---|---|
| `run_migration` | Menjalankan `php artisan migrate` | Database Worker | Dilarang `migrate:fresh` di luar environment `local` |
| `run_seeder` | Menjalankan `php artisan db:seed` | Database Worker | `DemoDataSeeder` dilarang dijalankan di staging/production |
| `run_test` | Menjalankan `php artisan test` | QA Worker | — |
| `run_linter` | Menjalankan `pint`/`npm run lint` | Backend/Frontend Worker | — |
| `git_commit` | Membuat commit Git | Semua Worker | Pesan commit wajib mengikuti konvensi Bagian 5.4; **tidak boleh** `git push --force` ke branch `main`/`develop` |
| `docker_compose_exec` | Eksekusi perintah dalam container | DevOps Worker | Dilarang menjalankan terhadap container yang terhubung ke database production |

### 2.3 Prinsip Umum Function Calling

1. **Validasi parameter sebelum panggilan** — Agent wajib memvalidasi struktur payload terhadap skema pada API_SPECIFICATION.md sebelum mengirim tool call, bukan mengandalkan API untuk menolak payload salah.
2. **Satu tool call = satu intensi jelas** — Agent tidak menggabungkan beberapa aksi berbeda (mis. create + approve) dalam satu pemanggilan tool tunggal buatan sendiri di luar daftar tool resmi.
3. **Idempotensi diperhatikan** — untuk tool yang bersifat mengubah state (POST/PATCH), Agent tidak melakukan retry otomatis tanpa memeriksa apakah aksi sebelumnya benar-benar gagal (mis. cek status terlebih dahulu via GET sebelum retry POST) agar tidak menghasilkan duplikasi data (mis. dua kali `create_catatan_iuran` untuk transaksi yang sama).

---

## 3. Aturan Main & Batasan (Rules & Guardrails)

### 3.1 Aksi yang Wajib Melalui Konfirmasi Manusia (Human-in-the-Loop)

Agent **dilarang** mengeksekusi aksi berikut secara otonom tanpa konfirmasi eksplisit dari pengguna manusia dalam sesi berjalan:

- Membuat atau menonaktifkan akun pengguna (`create_user`, perubahan `status` akun).
- Menyetujui/menolak pengajuan surat pada tahap final (`verify_pengajuan_surat` dengan `decision: APPROVED` pada tahap `RW_REVIEW`).
- Menyetujui/menolak transaksi keuangan (`approve_catatan_iuran`).
- Menjalankan migration atau seeder pada environment selain `local`.
- Melakukan `git push` ke branch `main`.
- Deployment ke environment Staging atau Production.
- Mengubah/menghapus data yang sudah tersimpan dan bersifat final (mis. surat berstatus `COMPLETED`, laporan berstatus `CLOSED`).

### 3.2 Larangan Mutlak (Tidak Dapat Di-override oleh Instruksi Pengguna dalam Sesi)

- **Dilarang** menjalankan `migrate:fresh`, `DROP TABLE`, atau perintah destruktif setara pada database mana pun yang bukan environment `local` murni — termasuk jika pengguna secara eksplisit meminta, Agent wajib menolak dan menjelaskan risiko, merujuk ke DEVELOPMENT_SETUP.md Bagian 6.2.
- **Dilarang** menyimpan atau menampilkan nilai NIK/No. KK dalam bentuk unmasked di log, output chat, atau file yang tidak dienkripsi — sesuai prinsip keamanan SYSTEM_ARCHITECTURE.md Bagian 4.4.
- **Dilarang** melewati (bypass) pengecekan RBAC/area scoping di level kode "demi mempercepat development atau testing" — jika Agent perlu menguji sebagai peran tertentu, gunakan akun/token uji yang sah, bukan menonaktifkan Policy.
- **Dilarang** melakukan hard-delete pada tabel yang menerapkan SoftDeletes (lihat DATABASE_SCHEMA.md) — gunakan mekanisme `deleted_at` yang sudah ditentukan.
- **Dilarang** menyimpan API key/secret (`AI_CLASSIFICATION_API_KEY`, `DATA_SEARCH_HASH_KEY`, kredensial database) di dalam kode sumber yang di-commit ke Git.

### 3.3 Penanganan Kesalahan (Error Handling)

| Situasi | Perilaku Agent yang Diharapkan |
|---|---|
| Tool call API mengembalikan `4xx` (client error) | Agent **tidak** melakukan retry otomatis dengan payload yang sama — analisis pesan error, perbaiki payload jika kesalahan ada pada input, atau laporkan ke Orchestrator jika kesalahan bersifat struktural (mis. skema API berubah). |
| Tool call API mengembalikan `5xx` (server error) | Agent boleh melakukan retry dengan **exponential backoff** maksimal 3 kali (selaras dengan `AI_CLASSIFICATION_MAX_RETRIES` pada `.env`). Jika tetap gagal, laporkan ke Orchestrator sebagai blocker, jangan berasumsi aksi berhasil. |
| Tool call API mengembalikan `429` (rate limit) | Agent berhenti melakukan pemanggilan berulang ke endpoint yang sama, menunggu sesuai waktu yang diinformasikan pada response sebelum mencoba lagi. |
| Layanan AI Classification (pihak ketiga) tidak merespons | Sesuai SYSTEM_ARCHITECTURE.md Bagian 2.2 — job tetap berstatus `SUBMITTED`, tidak dianggap gagal permanen sampai batas retry maksimum tercapai. Agent yang mengimplementasikan fitur ini wajib membuat fallback ke klasifikasi manual, bukan membiarkan status macet. |
| Test suite gagal setelah implementasi Worker Agent | QA Worker melaporkan kegagalan spesifik (nama test, assertion yang gagal) ke Orchestrator; Orchestrator mengembalikan tugas ke Worker terkait untuk perbaikan — **tidak** ditandai selesai sampai lolos. |
| Konflik antara instruksi pengguna dalam sesi dan aturan pada dokumen ini | Aturan pada AGENTS.md dan dokumen sumber (PRD/Architecture/Database/API) **selalu diprioritaskan** di atas instruksi ad-hoc dalam sesi kecuali pengguna secara eksplisit meminta perubahan pada dokumen sumber itu sendiri (bukan sekadar override perilaku sesaat). |
| Agent tidak yakin/ambigu terhadap suatu instruksi | Agent bertanya klarifikasi ke Orchestrator/pengguna sebelum bertindak — **tidak** menebak dan melanjutkan eksekusi pada aksi yang berpotensi mengubah data. |

---

## 4. Manajemen Konteks & Memori (Context & Memory Strategy)

### 4.1 Lapisan Konteks

| Lapisan | Isi | Siapa yang Membaca | Persistensi |
|---|---|---|---|
| **Global Context** | Dokumen ini (`AGENTS.md`) + PRD.md, SYSTEM_ARCHITECTURE.md, DATABASE_SCHEMA.md, API_SPECIFICATION.md, USER_STORIES.md | Seluruh Agent (Orchestrator & Worker) | Permanen — dimuat ulang di awal setiap sesi/workflow baru |
| **Task Context** | Deskripsi sub-tugas spesifik yang diberikan Orchestrator ke satu Worker, termasuk kriteria selesai (Acceptance Criteria terkait dari USER_STORIES.md) | Worker yang ditugaskan + Orchestrator | Berlaku selama satu siklus tugas, dibuang setelah tugas selesai dan hasil diagregasi |
| **Execution Log** | Riwayat tool call (Bagian 2), hasil, dan error yang terjadi selama eksekusi | Tool Execution Agent (penulis) → dapat dibaca Orchestrator untuk debugging | Disimpan sebagai artifact per sesi, dapat diaudit manusia |
| **Shared Scratchpad** *(opsional, antar-Worker paralel)* | Keputusan teknis lintas-domain yang perlu diketahui Worker lain (mis. Backend Worker mengumumkan bahwa struktur response endpoint tertentu berubah dari rencana awal) | Worker yang relevan (di-subscribe oleh Orchestrator) | Berlaku selama sesi paralel berjalan, diringkas ke Task Context saat digabung |

### 4.2 Aturan Pemisahan Konteks (Context Isolation)

- **Domain isolation**: setiap Worker Agent hanya menerima Task Context yang relevan dengan domainnya (mis. Frontend Worker tidak perlu menerima detail skema database mentah, cukup kontrak API yang relevan) — mengurangi context pollution dan risiko Worker "mencampuri" domain lain di luar wewenangnya.
- **Tidak ada state tersembunyi antar-Worker** — jika satu Worker perlu mengetahui keputusan dari Worker lain, keputusan tersebut wajib melalui Shared Scratchpad yang eksplisit dan diteruskan Orchestrator, bukan diasumsikan "sudah tahu" dari sesi sebelumnya.
- **Reset konteks antar-tugas besar** — saat memulai tugas besar baru dari pengguna (mis. modul berbeda), Orchestrator memulai Task Context baru, tidak mewariskan asumsi dari tugas besar sebelumnya kecuali eksplisit relevan (mis. keputusan penamaan endpoint yang sudah disepakati tetap dipertahankan sebagai bagian dari Global Context, bukan Task Context).

### 4.3 Alur Konteks dalam Eksekusi Graph/Workflow

```
[User Request]
      │
      ▼
[Orchestrator] ── baca Global Context (AGENTS.md + dokumen sumber)
      │
      ├─ decompose menjadi Task Context per Worker
      │
      ▼
[Worker Agent A] ──┐
[Worker Agent B] ──┼─→ tulis ke Shared Scratchpad jika ada keputusan lintas-domain
[Worker Agent C] ──┘
      │
      ▼
[Tool Execution Agent] ── catat setiap panggilan ke Execution Log
      │
      ▼
[Orchestrator] ── agregasi hasil, validasi konsistensi terhadap dokumen sumber
      │
      ▼
[Laporan ke pengguna] + [Task Context dibuang, Execution Log diarsipkan]
```

### 4.4 Prinsip Meneruskan Informasi ke Fase Berikutnya

- Hasil satu Worker (mis. skema response API final dari Backend Worker) menjadi bagian dari Task Context Worker berikutnya yang bergantung padanya (mis. Frontend Worker) — **diteruskan secara eksplisit oleh Orchestrator**, bukan Worker saling memanggil langsung tanpa sepengetahuan Orchestrator.
- Jika suatu keputusan teknis selama eksekusi **menyimpang** dari dokumen sumber (mis. Backend Worker terpaksa menambah field baru di luar API_SPECIFICATION.md karena kebutuhan implementasi), Orchestrator wajib menandai ini sebagai **deviasi yang perlu dikonfirmasi manusia** sebelum dianggap final — dokumen sumber adalah kontrak yang tidak diubah sepihak oleh Agent.

---

## 5. Standar Penulisan Kode untuk Developer/AI

### 5.1 Struktur Direktori (Backend — Laravel)

Mengikuti Modular Monolith pada SYSTEM_ARCHITECTURE.md Bagian 1.1. Agent (khususnya Backend Worker) wajib menempatkan kode baru sesuai struktur ini, tidak membuat struktur ad-hoc sendiri:

```
app/
├── Modules/
│   ├── Auth/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── Requests/        ← Form Request untuk validasi
│   │   └── Policies/
│   ├── Kependudukan/
│   ├── Persuratan/
│   ├── LaporanAspirasi/
│   ├── Keuangan/
│   ├── InformasiPublik/
│   └── Dashboard/
├── Models/                   ← Eloquent Model lintas modul (mengacu DATABASE_SCHEMA.md)
└── Jobs/                     ← Job asinkron (mis. ClassifyLaporanJob)
```

### 5.2 Konvensi Penamaan

| Elemen | Konvensi | Contoh |
|---|---|---|
| Nama kelas Controller | PascalCase + suffix `Controller` | `PengajuanSuratController` |
| Nama kelas Service | PascalCase + suffix `Service` | `LaporanAspirasiService` |
| Nama method Service | camelCase, kata kerja deskriptif | `createLaporan()`, `verifyPengajuanSurat()` |
| Nama tabel database | snake_case, plural | `pengajuan_surats` (sesuai DATABASE_SCHEMA.md) |
| Nama field JSON di API | snake_case (sesuai API_SPECIFICATION.md) | `tracking_code`, `current_status` |
| Nama route/endpoint | kebab-case, mengikuti struktur modul/resource persis pada API_SPECIFICATION.md | `/surat/pengajuan`, `/laporan-aspirasi` |
| Nama file Migration | `snake_case` dengan timestamp Laravel default | `2026_08_01_000001_create_pengajuan_surats_table.php` |
| Nama Job (queue) | PascalCase + kata kerja + objek | `ClassifyLaporanJob`, `GenerateRekapitulasiIuranJob` |

### 5.3 Prinsip Penulisan Kode

1. **Controller tipis (thin controller)** — Controller hanya menangani validasi request (via Form Request), memanggil Service, dan mengembalikan response. Business logic **wajib** berada di Service Layer, bukan di Controller (lihat SYSTEM_ARCHITECTURE.md Bagian 1.2).
2. **Setiap endpoint baru wajib disertai Form Request class** untuk validasi — Agent tidak menulis validasi inline di dalam method Controller.
3. **Setiap perubahan status entitas (surat, laporan, iuran) wajib memicu entri di `audit_logs`** — melalui Model Observer atau Event Listener, bukan ditulis manual berulang di setiap Service.
4. **Query yang menyentuh data warga/surat/laporan wajib melalui Global Scope atau Policy** yang menegakkan area scoping — Agent tidak menulis query manual (`DB::table(...)->where(...)`) yang melewati lapisan otorisasi.
5. **Kode yang menyentuh kolom NIK/No. KK wajib menggunakan accessor/mutator enkripsi terpusat** (mis. Laravel Cast kustom `EncryptedNik`) — Agent tidak menulis pemanggilan `Crypt::encrypt()`/`decrypt()` manual berulang di berbagai tempat.
6. **Setiap Service method publik wajib memiliki minimal satu Feature Test yang menguji Acceptance Criteria terkait** dari USER_STORIES.md — ditulis oleh QA Worker sebagai bagian dari Definition of Done, bukan opsional.

### 5.4 Konvensi Commit & Dokumentasi Kode

**Format pesan commit** (Conventional Commits, wajib diikuti seluruh Worker Agent yang melakukan `git_commit`):
```
<type>(<scope>): <deskripsi singkat>

<body opsional — jelaskan alasan perubahan jika tidak trivial>

Refs: US-<kode-user-story>
```

**Contoh:**
```
feat(persuratan): implementasi endpoint verifikasi pengajuan surat berjenjang

Menambahkan POST /surat/pengajuan/{id}/verify sesuai API_SPECIFICATION.md
Bagian 3.4, termasuk validasi transisi status dan area scoping RT.

Refs: US-SRT-03, US-SRT-04
```

| Type | Penggunaan |
|---|---|
| `feat` | Fitur baru |
| `fix` | Perbaikan bug |
| `refactor` | Perubahan struktur kode tanpa mengubah perilaku |
| `test` | Penambahan/perbaikan test |
| `docs` | Perubahan dokumentasi |
| `chore` | Perubahan konfigurasi, dependency, dsb. |

### 5.5 Komentar & Docblock

- Setiap method Service publik wajib memiliki PHPDoc singkat yang menjelaskan **apa** yang dilakukan dan **mengapa** (jika ada aturan bisnis non-trivial), dengan referensi ke kode `US-xxx` atau bagian PRD terkait bila relevan.
- Agent **tidak** menulis komentar yang hanya mengulang nama method (mis. `// membuat laporan` di atas method `createLaporan()`) — komentar harus menambah informasi yang tidak jelas dari nama/kode itu sendiri.

```php
/**
 * Membuat laporan/aspirasi baru dan mendorong job klasifikasi AI ke queue.
 * Proses klasifikasi bersifat asinkron agar response ke warga tetap cepat
 * meski layanan AI eksternal lambat (lihat SYSTEM_ARCHITECTURE.md §2.2).
 *
 * @see US-LAP-01, US-LAP-02
 */
public function createLaporan(array $data): LaporanAspirasi
{
    // ...
}
```

---

## 6. Definition of Done (Checklist Sebelum Worker Melaporkan Tugas Selesai)

Setiap Worker Agent wajib memvalidasi seluruh poin berikut sebelum melaporkan sub-tugas selesai ke Orchestrator:

- [ ] Implementasi sesuai kontrak API_SPECIFICATION.md (jika berkaitan dengan endpoint) — path, method, status code, struktur response persis sama.
- [ ] Implementasi sesuai skema DATABASE_SCHEMA.md (jika berkaitan dengan data) — tidak ada field/tabel yang menyimpang tanpa dikonfirmasi sebagai deviasi (lihat Bagian 4.4).
- [ ] Acceptance Criteria terkait pada USER_STORIES.md terpenuhi dan memiliki test otomatis yang lolos.
- [ ] Tidak melanggar larangan pada Bagian 3.2 (RBAC bypass, hard-delete, unmasked sensitive data, dsb.).
- [ ] Linting (`pint`/`npm run lint`) lolos tanpa error.
- [ ] Commit mengikuti konvensi Bagian 5.4 dan mereferensikan kode User Story terkait.
- [ ] Jika ada deviasi dari dokumen sumber, sudah ditandai eksplisit dan menunggu/mendapat konfirmasi manusia.
