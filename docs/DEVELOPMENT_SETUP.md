# DEVELOPMENT SETUP
## SIM Layanan Warga RW 047 — Panduan Penyiapan Lingkungan Pengembangan

| | |
|---|---|
| **Dokumen turunan dari** | PRD, SYSTEM_ARCHITECTURE.md, DATABASE_SCHEMA.md, API_SPECIFICATION.md v1.0 |
| **Versi dokumen** | 1.0 |
| **Status** | Draft — panduan onboarding developer |
| **Audiens** | Backend Engineer, Frontend Engineer, DevOps, developer baru yang onboarding |

---

## 0. Ringkasan Tech Stack

Sesuai SYSTEM_ARCHITECTURE.md, stack yang digunakan pada rebuild ini:

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.1+ (Baseline Composer `^8.1`) / Laravel 10 (LTS) |
| Frontend | Blade + Livewire/Alpine.js + Tailwind CSS |
| Database | MySQL 8+ (Runtime Aplikasi) / SQLite in-memory (PHPUnit Testing) |
| Cache/Session/Queue | Redis 7+ / file/sync driver (lokal) |
| Web Server (lokal) | Laravel Sail / built-in `artisan serve` |
| Container | Docker + Docker Compose |
| Package manager | Composer (PHP), npm (JS) |

> Dokumen ini mengasumsikan penggunaan **Docker Compose** atau web server lokal untuk menjalankan aplikasi dengan target database **MySQL 8+** dan pengujian otomatis PHPUnit menggunakan database in-memory **SQLite**.

---

## 1. Prasyarat & Dependensi (Prerequisites & Dependencies)

### 1.1 Wajib Diinstal (Setup dengan Docker — direkomendasikan)

| Tools | Versi Minimum | Catatan |
|---|---|---|
| **Git** | 2.40+ | Version control |
| **Docker Desktop** | 24.0+ | Termasuk Docker Compose v2 |
| **Composer** | 2.6+ | Diperlukan untuk instalasi awal dependency PHP sebelum container aktif (opsional jika memakai Docker image yang sudah include Composer) |
| **Node.js** | 20 LTS | Untuk build asset frontend (Tailwind, Alpine.js) |
| **npm** | 10+ | Terpasang bersama Node.js |

### 1.2 Wajib Diinstal (Setup Manual — tanpa Docker)

| Tools | Versi Minimum | Catatan |
|---|---|---|
| **PHP** | 8.1+ | Baseline `composer.json` (`^8.1`). Ekstensi wajib: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `redis` |
| **Composer** | 2.6+ | Dependency manager PHP |
| **MySQL** | 8.0+ | Database utama runtime aplikasi (port 3306) |
| **Redis** | 7+ | Cache, session store, dan queue driver (opsional di lokal, bisa fallback ke file/sync) |
| **Node.js & npm** | 20 LTS / 10+ | Build asset frontend |
| **Git** | 2.40+ | Version control |

### 1.3 Direkomendasikan (Opsional, mempermudah workflow)

| Tools | Kegunaan |
|---|---|
| **Laravel Sail** | Wrapper Docker Compose bawaan Laravel — mempermudah perintah CLI (`sail artisan` menggantikan `docker compose exec`) |
| **TablePlus / DBeaver / HeidiSQL / phpMyAdmin** | GUI client untuk inspeksi database MySQL |
| **RedisInsight** | GUI client untuk inspeksi Redis (cache, queue jobs) |
| **Postman / Insomnia** | Pengujian manual endpoint REST API (rujuk API_SPECIFICATION.md) |
| **VS Code** dengan ekstensi *PHP Intelephense*, *Laravel Blade Snippets*, *Tailwind CSS IntelliSense* | Editor kode dengan dukungan Laravel yang baik |

---

## 2. Langkah-Langkah Instalasi Lokal (Local Setup Guide)

### 2.1 Kloning Repositori

```bash
git clone https://github.com/rw047/sim-layanan-warga.git
cd sim-layanan-warga
```

### 2.2 Instalasi Dependensi Backend (PHP/Laravel)

```bash
# Instal dependency PHP via Composer
composer install

# Salin file konfigurasi environment
cp .env.example .env

# Generate application key (wajib untuk enkripsi Laravel, termasuk enkripsi NIK/KK)
php artisan key:generate
```

### 2.3 Instalasi Dependensi Frontend (Node/Tailwind)

```bash
# Instal dependency JS
npm install

# Build asset untuk mode development (watch mode)
npm run dev
```

### 2.4 Konfigurasi Variabel Lingkungan (`.env`)

Berikut daftar variabel penting pada `.env.example` beserta penjelasannya. Salin dan sesuaikan sesuai environment lokal Anda.

```env
# ==========================================
# APLIKASI
# ==========================================
APP_NAME="SIM Layanan Warga RW 047"
APP_ENV=local                      # local | staging | production
APP_KEY=                           # diisi otomatis oleh `php artisan key:generate`
APP_DEBUG=true                     # WAJIB false di production
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

# ==========================================
# DATABASE (MySQL — Target Runtime Aplikasi)
# ==========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database_simbaru
DB_USERNAME=root
DB_PASSWORD=

# ==========================================
# CACHE, SESSION, QUEUE (Redis / Local Fallback)
# ==========================================
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120                # menit
QUEUE_CONNECTION=sync
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ==========================================
# AUTENTIKASI (Laravel Sanctum)
# ==========================================
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
SESSION_DOMAIN=localhost

# ==========================================
# ENKRIPSI DATA SENSITIF (NIK, No. KK)
# ==========================================
# Kunci HMAC terpisah dari APP_KEY, khusus untuk hash pencarian presisi
# (lihat DATABASE_SCHEMA.md Bagian 5.4). JANGAN gunakan nilai contoh ini di staging/production —
# generate nilai acak sendiri dan simpan di secret manager, bukan hardcode.
DATA_SEARCH_HASH_KEY=sim-warga-rw047-deterministic-search-key-32chars!

# ==========================================
# LAYANAN AI CLASSIFICATION (Laporan & Aspirasi)
# ==========================================
AI_CLASSIFICATION_API_URL=https://api.contoh-ai-provider.com/v1/classify
AI_CLASSIFICATION_API_KEY=          # diperoleh dari provider AI/NLP yang dipilih
AI_CLASSIFICATION_TIMEOUT=15        # detik
AI_CLASSIFICATION_MAX_RETRIES=3

# ==========================================
# OBJECT STORAGE (opsional — bukti bayar/lampiran laporan)
# ==========================================
FILESYSTEM_DISK=local               # local (dev) | s3 (staging/production)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=

# ==========================================
# MAIL (notifikasi, reset password pengurus)
# ==========================================
MAIL_MAILER=smtp
MAIL_HOST=mailpit                   # gunakan Mailpit/Mailhog untuk testing email lokal
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@simwarga-rw047.id"
MAIL_FROM_NAME="${APP_NAME}"

# ==========================================
# MONITORING (opsional, direkomendasikan untuk staging/production)
# ==========================================
SENTRY_LARAVEL_DSN=
```

> **Peringatan keamanan:** `DATA_SEARCH_HASH_KEY` dan `AI_CLASSIFICATION_API_KEY` **tidak boleh** disamakan dengan nilai contoh di atas dan **tidak boleh** di-commit ke Git. Pastikan `.env` tercantum di `.gitignore` (default Laravel sudah menanganinya).

---

## 3. Inisialisasi & Migrasi Basis Data (Database Setup & Migration)

### 3.1 (Jika Setup Manual) Membuat Database MySQL

```bash
# Masuk ke CLI MySQL (mis. via MySQL Command Line Client / Terminal)
mysql -u root -p

# Di dalam prompt MySQL:
CREATE DATABASE database_simbaru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Sesuaikan `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` pada `.env` dengan konfigurasi lokal Anda.

### 3.2 Menjalankan Migrasi

```bash
# Menjalankan seluruh migration (membuat skema tabel sesuai DATABASE_SCHEMA.md)
php artisan migrate

# Jika ingin reset total (drop semua tabel lalu migrate ulang) — HANYA untuk lokal/staging
php artisan migrate:fresh
```

Urutan migrasi mengikuti dependensi foreign key sesuai domain pada DATABASE_SCHEMA.md: `roles` & `permissions` → `users` → `kartu_keluargas` → `wargas` → `pengajuan_surats` / `laporan_aspirasis` → `iuran_types` → `catatan_iurans` → `kas_keluars` → `informasi_publiks` → `audit_logs`.

### 3.3 Menjalankan Seeder (Data Awal)

```bash
# Menjalankan seluruh seeder terdaftar
php artisan db:seed

# Atau kombinasikan langsung dengan migrate:fresh (rekomendasi untuk setup awal lokal)
php artisan migrate:fresh --seed
```

**Seeder yang tersedia (`database/seeders/`):**

| Seeder | Fungsi |
|---|---|
| `RoleSeeder` | Mengisi tabel `roles` dengan 6 peran baku (`WARGA`, `KETUA_RT`, `SEKRETARIS_RW`, `BENDAHARA_RW`, `KETUA_RW`, `SUPER_ADMIN`) |
| `PermissionSeeder` | Mengisi tabel `permissions` dan relasi `role_permissions` sesuai matriks RBAC |
| `SuperAdminSeeder` | Membuat satu akun Super Admin awal untuk login pertama kali (kredensial dicetak di terminal setelah seeding, **wajib diganti** setelah login pertama) |
| `IuranTypeSeeder` | Mengisi data master jenis iuran contoh (mis. "Iuran Kebersihan & Keamanan") |
| `DemoDataSeeder` *(opsional, hanya untuk lokal/staging)* | Data dummy Kartu Keluarga, Warga, Pengajuan Surat, dan Laporan untuk keperluan development/demo — **jangan dijalankan di production** |

```bash
# Menjalankan seeder tertentu saja
php artisan db:seed --class=DemoDataSeeder
```

### 3.4 Verifikasi Setup Database

```bash
# Cek status migrasi
php artisan migrate:status

# Masuk ke Tinker (REPL Laravel) untuk verifikasi cepat
php artisan tinker
>>> App\Models\User::count();
>>> App\Models\Role::all();
```

---

## 4. Menjalankan Aplikasi (Running the Application)

### 4.1 Opsi A — Menggunakan Docker Compose (direkomendasikan)

**File `docker-compose.yml` mendefinisikan service berikut:** `app` (PHP-FPM/Laravel), `nginx`, `postgres`, `redis`, `mailpit` (untuk testing email lokal), dan `queue` (worker Laravel Queue).

```bash
# Build image dan jalankan seluruh service di background
docker compose up -d --build

# Instal dependency PHP di dalam container (jika belum dilakukan sebelumnya)
docker compose exec app composer install

# Jalankan migrasi + seeder di dalam container
docker compose exec app php artisan migrate:fresh --seed

# Lihat log aplikasi secara real-time
docker compose logs -f app

# Menghentikan seluruh service
docker compose down
```

Aplikasi dapat diakses di `http://localhost:8000` setelah seluruh container berjalan.

**Jika menggunakan Laravel Sail** (alternatif wrapper Docker Compose bawaan Laravel):

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run dev
```

### 4.2 Opsi B — Setup Manual (tanpa Docker)

```bash
# Terminal 1 — Menjalankan server backend Laravel
php artisan serve
# Aplikasi berjalan di http://localhost:8000

# Terminal 2 — Menjalankan build asset frontend dalam mode watch
npm run dev

# Terminal 3 — Menjalankan queue worker
# WAJIB berjalan agar job klasifikasi AI (lihat SYSTEM_ARCHITECTURE.md Bagian 2.2) diproses
php artisan queue:work --tries=3

# Terminal 4 (opsional) — Menjalankan scheduler untuk tugas berkala
# Di lokal cukup dijalankan manual saat dibutuhkan; di production dikonfigurasi via cron
php artisan schedule:work
```

### 4.3 Verifikasi Aplikasi Berjalan dengan Baik

```bash
# Cek endpoint health/status API
curl http://localhost:8000/api/v1/informasi-publik

# Cek koneksi queue (pastikan worker aktif memproses job dummy)
php artisan queue:work --once
```

---

## 5. Perintah Pengujian & Quality Control (Testing & Code Standards)

### 5.1 Unit Test & Feature Test (Pest/PHPUnit)

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan test untuk satu file/kelas spesifik
php artisan test --filter=PengajuanSuratTest

# Menjalankan test dengan laporan code coverage (memerlukan Xdebug/PCOV terpasang)
php artisan test --coverage --min=70
```

**Struktur direktori test yang direkomendasikan (`tests/`):**
```
tests/
├── Unit/               → Test logika murni (Service, Value Object) tanpa dependency database
├── Feature/             → Test end-to-end per endpoint API/flow (mis. alur verifikasi surat)
│   ├── Auth/
│   ├── Persuratan/
│   ├── LaporanAspirasi/
│   └── Keuangan/
└── TestCase.php
```

> Setiap User Story pada USER_STORIES.md idealnya memiliki minimal satu Feature Test yang memvalidasi Acceptance Criteria-nya (format Given-When-Then diterjemahkan langsung menjadi struktur `it('...', function () { ... })` pada Pest).

### 5.2 Linting & Code Style (Backend — PHP)

```bash
# Cek pelanggaran code style (PSR-12) tanpa mengubah file
./vendor/bin/pint --test

# Otomatis memperbaiki code style
./vendor/bin/pint

# Analisis statis (deteksi bug potensial, type error) — jika PHPStan/Larastan terpasang
./vendor/bin/phpstan analyse
```

### 5.3 Linting & Formatting (Frontend — JS/CSS)

```bash
# Cek pelanggaran linting JS/Alpine.js
npm run lint

# Format kode otomatis (Prettier)
npm run format

# Build ulang asset Tailwind untuk memvalidasi tidak ada class error
npm run build
```

### 5.4 Pre-commit Checklist (disarankan sebagai Git Hook)

```bash
# Contoh urutan perintah sebelum commit (dapat diotomatisasi via Husky/Git Hooks)
./vendor/bin/pint --test && \
php artisan test && \
npm run lint
```

---

## 6. Panduan Build & Deployment Singkat (Build & Deployment Guide)

### 6.1 Production Build (Asset Frontend)

```bash
# Build asset frontend teroptimasi (minified, versioned) untuk production
npm run build
```

Perintah ini menghasilkan berkas hasil build di `public/build/`, yang di-reference otomatis oleh Laravel Vite Plugin di Blade layout (`@vite(...)`).

### 6.2 Optimasi Backend untuk Production

```bash
# Instal dependency tanpa package development, optimasi autoloader
composer install --optimize-autoloader --no-dev

# Cache konfigurasi, route, dan view untuk performa maksimal
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi production (TANPA --seed, dan TANPA migrate:fresh yang menghapus data)
php artisan migrate --force
```

> **Peringatan:** `migrate:fresh` **tidak pernah** dijalankan di production karena akan menghapus seluruh data (termasuk data kependudukan, surat, dan keuangan warga). Gunakan `migrate --force` yang hanya menjalankan migrasi baru secara inkremental.

### 6.3 Alur Deployment Singkat

Mengacu pada strategi CI/CD di SYSTEM_ARCHITECTURE.md Bagian 5.4:

```bash
# 1. Push ke branch develop → CI otomatis menjalankan test & lint
git push origin feature/nama-fitur

# 2. Setelah PR di-review & merge ke develop → auto-deploy ke Staging
#    (dikonfigurasi via GitHub Actions/GitLab CI, tidak dijalankan manual oleh developer)

# 3. Setelah UAT disetujui, merge develop → main
git checkout main
git merge develop
git push origin main
#    → memicu pipeline auto-deploy ke Production
```

**Langkah pada server saat deployment (dijalankan otomatis oleh pipeline CI/CD, referensi manual jika diperlukan):**

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart   # memuat ulang worker agar menggunakan kode terbaru
```

### 6.4 Environment Checklist Sebelum Deploy ke Production

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_KEY`, `DATA_SEARCH_HASH_KEY`, dan seluruh API key eksternal diambil dari secret manager (bukan `.env` file biasa) — lihat SYSTEM_ARCHITECTURE.md Bagian 4.3
- [ ] Koneksi database menggunakan SSL (`DB_SSLMODE=require` jika memakai managed PostgreSQL)
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS wajib di production)
- [ ] Queue worker (`php artisan queue:work`) berjalan sebagai service terkelola (systemd/Supervisor), bukan proses manual
- [ ] Backup database terjadwal aktif (lihat SYSTEM_ARCHITECTURE.md Bagian 5.5)
- [ ] Monitoring error (Sentry) aktif dan menerima event test

---

## 7. Ringkasan Perintah Cepat (Quick Reference)

```bash
# Setup awal proyek dari nol (Docker)
git clone <repo-url> && cd sim-layanan-warga
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
npm install && npm run dev

# Workflow harian development
docker compose up -d              # jalankan environment
php artisan test                  # jalankan test sebelum commit
./vendor/bin/pint                 # rapikan code style
git add . && git commit -m "..."  # commit perubahan

# Troubleshooting umum
php artisan config:clear          # bersihkan cache config jika .env tidak terbaca
php artisan cache:clear           # bersihkan cache aplikasi
php artisan queue:restart         # restart worker jika job tidak berjalan sesuai perubahan kode terbaru
docker compose logs -f app        # cek log jika container error
```
