# API SPECIFICATION
## SIM Layanan Warga RW 047 — REST API Documentation

| | |
|---|---|
| **Dokumen turunan dari** | PRD SIM Layanan Warga RW 047 v1.0, SYSTEM_ARCHITECTURE.md v1.0, DATABASE_SCHEMA.md v1.0 |
| **Versi dokumen** | 1.0 |
| **Versi API** | v1 |
| **Status** | Draft teknis untuk tim development |
| **Audiens** | Backend Engineer, Frontend Engineer, Mobile Engineer (future), QA Engineer |

---

## 1. Gambaran Umum & Standar API (API Overview & Standards)

### 1.1 Format Data

- Seluruh request dan response menggunakan format **JSON** (`Content-Type: application/json`).
- Encoding karakter: **UTF-8**.
- Format tanggal/waktu mengikuti standar **ISO 8601** (`YYYY-MM-DDTHH:mm:ssZ`), disimpan dalam UTC dan dikonversi ke timezone lokal (`Asia/Jakarta`) di sisi klien.

### 1.2 Base URL

| Environment | Base URL |
|---|---|
| Local | `http://localhost:8000/api/v1` |
| Staging | `https://staging.simwarga-rw047.id/api/v1` |
| Production | `https://simwarga-rw047.id/api/v1` |

Seluruh path endpoint pada dokumen ini bersifat **relatif terhadap Base URL** di atas. Contoh: `POST /auth/login` berarti `POST {base_url}/auth/login`.

### 1.3 Konvensi Penamaan Endpoint

| Konvensi | Aturan | Contoh |
|---|---|---|
| Struktur path | `{base_url}/{modul}/{resource}[/{id}][/{sub-resource}]` (versi `/v1` sudah termasuk dalam Base URL, tidak diulang di path) | `/surat/pengajuan/12/verify` |
| Penamaan resource | Kata benda, plural, `kebab-case` untuk multi-kata | `/laporan-aspirasi`, `/kartu-keluarga` |
| Penamaan action non-CRUD | Kata kerja sebagai sub-path setelah ID resource | `POST /surat/pengajuan/{id}/verify`, `POST /laporan-aspirasi/{id}/klasifikasi-ulang` |
| Query parameter | `snake_case` | `?rt_code=001&status=PENDING&page=2` |
| Body JSON | `snake_case` (selaras dengan nama kolom database) | `{"nama_lengkap": "Budi Santoso"}` |

### 1.4 Metode HTTP yang Digunakan

| Metode | Kegunaan |
|---|---|
| `GET` | Mengambil data (list atau detail), tidak mengubah state |
| `POST` | Membuat resource baru, atau menjalankan aksi non-idempotent (mis. verifikasi, submit) |
| `PUT` | Mengganti seluruh resource (jarang dipakai, hanya untuk update penuh) |
| `PATCH` | Mengubah sebagian field resource (metode utama untuk update) |
| `DELETE` | Soft-delete resource |

### 1.5 Standar Kode Status HTTP

| Kode | Nama | Kapan Digunakan |
|---|---|---|
| `200 OK` | Success | Request GET/PATCH/DELETE berhasil |
| `201 Created` | Created | Request POST berhasil membuat resource baru |
| `204 No Content` | No Content | Aksi berhasil tanpa body response (jarang dipakai, API ini konsisten mengembalikan body) |
| `400 Bad Request` | Bad Request | Format request salah (JSON tidak valid, tipe data salah) |
| `401 Unauthorized` | Unauthorized | Token tidak ada/tidak valid/kedaluwarsa |
| `403 Forbidden` | Forbidden | Token valid, tetapi tidak berwenang mengakses resource ini (RBAC/area scoping gagal) |
| `404 Not Found` | Not Found | Resource dengan ID yang diminta tidak ditemukan |
| `409 Conflict` | Conflict | Konflik data, mis. duplikasi unique constraint (NIK sudah terdaftar) |
| `422 Unprocessable Entity` | Validation Error | Request valid secara format tapi gagal validasi bisnis (field wajib kosong, format NIK salah) |
| `429 Too Many Requests` | Rate Limited | Melebihi batas rate limiting (mis. percobaan login berlebihan) |
| `500 Internal Server Error` | Server Error | Kesalahan tak terduga di server |

### 1.6 Format Response Standar

Seluruh response API mengikuti **envelope** yang konsisten agar mudah diparsing di sisi klien.

**Response sukses (single resource / aksi):**
```json
{
  "success": true,
  "message": "Data berhasil disimpan",
  "data": { }
}
```

**Response sukses (list/collection) — dengan pagination:**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": [ ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

**Response gagal:**
```json
{
  "success": false,
  "message": "Deskripsi kesalahan singkat",
  "errors": {
    "field_name": ["Pesan error spesifik field ini"]
  }
}
```

> Field `errors` hanya muncul pada `422 Unprocessable Entity` (validation error) yang mengandung kesalahan per-field. Untuk error lain (401, 403, 404, 500), field `errors` boleh tidak disertakan.

### 1.7 Pagination

Seluruh endpoint `GET` yang mengembalikan list data mendukung pagination melalui query parameter:

| Parameter | Tipe | Default | Deskripsi |
|---|---|---|---|
| `page` | integer | `1` | Halaman yang diminta |
| `per_page` | integer | `15` | Jumlah item per halaman (maksimal `100`) |
| `sort_by` | string | bervariasi per endpoint | Kolom untuk pengurutan |
| `sort_dir` | string (`asc`/`desc`) | `desc` | Arah pengurutan |

### 1.8 Idempotency

Endpoint aksi non-idempotent yang kritikal (mis. `POST /surat/pengajuan/{id}/verify`) direkomendasikan menerima header opsional `Idempotency-Key` untuk mencegah duplikasi aksi akibat retry jaringan — terutama relevan untuk endpoint yang memicu perubahan status keuangan atau persuratan resmi.

---

## 2. Skema Autentikasi (Authentication Scheme)

### 2.1 Mekanisme

Sistem menggunakan **Laravel Sanctum — Token-Based Authentication** (Bearer Token), sesuai rekomendasi pada `SYSTEM_ARCHITECTURE.md` Bagian 4.1. Token bersifat *first-party*, cocok untuk klien web (SPA/Blade-Livewire) maupun aplikasi mobile/PWA di masa depan.

### 2.2 Pengiriman Token

Seluruh endpoint yang memerlukan autentikasi (seluruhnya kecuali yang ditandai **Publik**) mewajibkan header berikut:

```
Authorization: Bearer {access_token}
Accept: application/json
Content-Type: application/json
```

### 2.3 Siklus Token

| Aksi | Endpoint | Keterangan |
|---|---|---|
| Mendapatkan token | `POST /auth/login` | Token diterbitkan setelah kredensial valid |
| Mencabut token saat ini | `POST /auth/logout` | Menghapus token aktif (invalidasi sesi) |
| Refresh token | `POST /auth/refresh` | Menerbitkan token baru sebelum kedaluwarsa |
| Melihat identitas token aktif | `GET /auth/me` | Mengembalikan data user pemilik token |

### 2.4 Otorisasi (RBAC & Area Scoping)

- Setiap endpoint memvalidasi **permission** pengguna berdasarkan `role` yang tersimpan pada token/session (lihat `DATABASE_SCHEMA.md` tabel `roles`, `permissions`, `role_permissions`).
- Endpoint yang mengembalikan data berbasis wilayah (mis. daftar warga, daftar iuran) **wajib** menerapkan **area scoping** — pengguna dengan `role = KETUA_RT` hanya menerima data dengan `rt_code` miliknya, ditegakkan di backend, bukan di klien.
- Pelanggaran otorisasi (akses ke data di luar kewenangan) direspons `403 Forbidden` secara konsisten untuk kasus "beda wilayah dalam modul yang sama", agar perilaku API mudah diprediksi oleh klien — **namun** untuk resource yang secara eksplisit tidak boleh diketahui keberadaannya oleh pengguna terkait (di luar lingkup sama sekali), sistem dapat merespons `404 Not Found` demi keamanan (lihat catatan per-endpoint).

### 2.5 Rate Limiting Autentikasi

Endpoint `POST /auth/login` dibatasi **maksimal 5 percobaan per menit per kombinasi IP + email**, sesuai kebutuhan keamanan pada PRD (NFR-01) dan SYSTEM_ARCHITECTURE.md. Percobaan yang melebihi batas menerima `429 Too Many Requests`.

---

## 3. Daftar & Detail Endpoint (Endpoint List & Details)

### 3.0 Ringkasan Modul

| Modul | Prefix Path | Deskripsi |
|---|---|---|
| Auth | `/auth` | Autentikasi, sesi, profil pengguna |
| User Management | `/users` | Manajemen akun & role (Super Admin) |
| Kependudukan | `/kartu-keluarga`, `/warga` | Data KK dan data warga |
| Persuratan | `/surat/pengajuan` | Pengajuan & verifikasi surat |
| Laporan & Aspirasi | `/laporan-aspirasi` | Pengaduan warga & klasifikasi AI |
| Keuangan | `/iuran-types`, `/catatan-iuran` | Jenis iuran & transaksi pembayaran |
| Informasi Publik | `/informasi-publik` | Pengumuman, berita, agenda |
| Dashboard | `/dashboard` | Statistik & monitoring per peran |

---

### 3.1 Modul: Auth

#### 3.1.1 `POST /auth/login`

**Deskripsi:** Autentikasi pengguna dan menerbitkan access token.
**Akses:** Publik (tidak memerlukan token).

**Header:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "ketuart01@rw047.id",
  "password": "PasswordAman123!"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "access_token": "1|abcdef1234567890tokenexample",
    "token_type": "Bearer",
    "expires_at": "2026-08-13T10:00:00Z",
    "user": {
      "id": 12,
      "full_name": "Budi Santoso",
      "email": "ketuart01@rw047.id",
      "role": "KETUA_RT",
      "rt_code": "001"
    }
  }
}
```

**Error Response — `422 Unprocessable Entity` (kredensial salah):**
```json
{
  "success": false,
  "message": "Email atau kata sandi yang Anda masukkan salah",
  "errors": {
    "email": ["Kredensial tidak cocok dengan data kami"]
  }
}
```

**Error Response — `429 Too Many Requests`:**
```json
{
  "success": false,
  "message": "Terlalu banyak percobaan login. Silakan coba lagi dalam 45 detik"
}
```

---

#### 3.1.2 `POST /auth/logout`

**Deskripsi:** Mencabut token akses aktif (invalidasi sesi).
**Akses:** Terautentikasi (seluruh role).

**Header:**
```
Authorization: Bearer {access_token}
```

**Request Body:** *(kosong)*

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Berhasil keluar dari sistem",
  "data": null
}
```

**Error Response — `401 Unauthorized`:**
```json
{
  "success": false,
  "message": "Token tidak valid atau sudah kedaluwarsa"
}
```

---

#### 3.1.3 `GET /auth/me`

**Deskripsi:** Mengambil data profil pengguna yang sedang login berdasarkan token aktif.
**Akses:** Terautentikasi (seluruh role).

**Header:**
```
Authorization: Bearer {access_token}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data profil berhasil diambil",
  "data": {
    "id": 12,
    "username": "ketuart01",
    "full_name": "Budi Santoso",
    "email": "ketuart01@rw047.id",
    "phone_number": "+6281234567890",
    "role": "KETUA_RT",
    "rt_code": "001",
    "status": "ACTIVE",
    "last_login_at": "2026-08-12T08:15:00Z"
  }
}
```

**Error Response — `401 Unauthorized`:**
```json
{
  "success": false,
  "message": "Silakan login terlebih dahulu"
}
```

---

### 3.2 Modul: User Management

> Seluruh endpoint modul ini memerlukan permission `user.manage`, secara default hanya dimiliki role `SUPER_ADMIN`.

#### 3.2.1 `GET /users`

**Deskripsi:** Mengambil daftar akun pengguna dengan filter dan pagination.
**Akses:** `SUPER_ADMIN`.

**Header:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Tipe | Wajib | Deskripsi |
|---|---|---|---|
| `role` | string | Tidak | Filter berdasarkan nama role |
| `status` | string | Tidak | Filter `ACTIVE`/`INACTIVE` |
| `search` | string | Tidak | Pencarian berdasarkan nama/email/username |
| `page`, `per_page` | integer | Tidak | Pagination standar |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data pengguna berhasil diambil",
  "data": [
    {
      "id": 12,
      "full_name": "Budi Santoso",
      "email": "ketuart01@rw047.id",
      "role": "KETUA_RT",
      "rt_code": "001",
      "status": "ACTIVE"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 8,
    "last_page": 1
  }
}
```

**Error Response — `403 Forbidden`:**
```json
{
  "success": false,
  "message": "Anda tidak memiliki wewenang untuk mengakses resource ini"
}
```

---

#### 3.2.2 `POST /users`

**Deskripsi:** Membuat akun pengguna baru (mis. akun pengurus RT/RW baru).
**Akses:** `SUPER_ADMIN`.

**Header:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "ketuart02",
  "full_name": "Siti Aminah",
  "email": "ketuart02@rw047.id",
  "password": "PasswordAman456!",
  "password_confirmation": "PasswordAman456!",
  "phone_number": "+6281298765432",
  "role": "KETUA_RT",
  "rt_code": "002"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Akun pengguna berhasil dibuat",
  "data": {
    "id": 13,
    "username": "ketuart02",
    "full_name": "Siti Aminah",
    "email": "ketuart02@rw047.id",
    "role": "KETUA_RT",
    "rt_code": "002",
    "status": "ACTIVE"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "email": ["Email sudah terdaftar pada sistem"],
    "password": ["Konfirmasi kata sandi tidak cocok"]
  }
}
```

---

#### 3.2.3 `PATCH /users/{id}`

**Deskripsi:** Memperbarui sebagian data akun pengguna (mis. mengubah status, role, atau kontak).
**Akses:** `SUPER_ADMIN`.

**Path Parameter:** `id` — ID pengguna (integer)

**Request Body (contoh menonaktifkan akun):**
```json
{
  "status": "INACTIVE"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data pengguna berhasil diperbarui",
  "data": {
    "id": 13,
    "status": "INACTIVE"
  }
}
```

**Error Response — `404 Not Found`:**
```json
{
  "success": false,
  "message": "Pengguna dengan ID tersebut tidak ditemukan"
}
```

---

### 3.3 Modul: Kependudukan

#### 3.3.1 `GET /kartu-keluarga`

**Deskripsi:** Mengambil daftar Kartu Keluarga sesuai kewenangan (area-scoped untuk Ketua RT).
**Akses:** `KETUA_RT` (lingkup RT sendiri), `SEKRETARIS_RW`, `KETUA_RW`, `SUPER_ADMIN` (seluruh RT).

**Header:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Tipe | Deskripsi |
|---|---|---|
| `rt_code` | string | Filter wilayah RT (otomatis dipaksa ke RT milik user untuk role `KETUA_RT`, diabaikan bila dikirim berbeda) |
| `search` | string | Pencarian berdasarkan alamat/blok |
| `page`, `per_page` | integer | Pagination standar |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data Kartu Keluarga berhasil diambil",
  "data": [
    {
      "no_kk_masked": "3216xxxxxxxx0012",
      "rt_code": "001",
      "alamat_lengkap_masked": "Jl. Mawar Blok C No. 12",
      "blok": "C",
      "nomor_rumah": "12",
      "status_kepemilikan_rumah": "Milik Sendiri",
      "jumlah_anggota": 4
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 56,
    "last_page": 4
  }
}
```

> Field bertanda `_masked` mengembalikan data yang sudah disamarkan sebagian di sisi API (mis. `3216xxxxxxxx0012`) sesuai kebijakan masking pada `SYSTEM_ARCHITECTURE.md` Bagian 4.4. Data unmasked hanya tersedia melalui endpoint detail dan dicatat di `audit_logs`.

**Error Response — `403 Forbidden`:**
```json
{
  "success": false,
  "message": "Anda hanya dapat mengakses data pada wilayah RT Anda"
}
```

---

#### 3.3.2 `POST /kartu-keluarga`

**Deskripsi:** Mendaftarkan Kartu Keluarga baru.
**Akses:** `KETUA_RT` (untuk RT-nya sendiri), `SEKRETARIS_RW`, `SUPER_ADMIN`.

**Request Body:**
```json
{
  "no_kk": "3216010101230012",
  "rt_code": "001",
  "alamat_lengkap": "Jl. Mawar Blok C No. 12, RT 001/RW 047",
  "blok": "C",
  "nomor_rumah": "12",
  "status_kepemilikan_rumah": "Milik Sendiri"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Data Kartu Keluarga berhasil didaftarkan",
  "data": {
    "id": 145,
    "no_kk_masked": "3216xxxxxxxx0012",
    "rt_code": "001",
    "status_kepemilikan_rumah": "Milik Sendiri",
    "created_at": "2026-08-12T09:30:00Z"
  }
}
```

**Error Response — `409 Conflict`:**
```json
{
  "success": false,
  "message": "Nomor Kartu Keluarga sudah terdaftar pada sistem"
}
```

---

#### 3.3.3 `POST /warga`

**Deskripsi:** Menambahkan data warga baru ke dalam suatu Kartu Keluarga. Data tersimpan berstatus `MENUNGGU_VERIFIKASI` hingga disetujui Sekretaris RW (lihat 3.3.6).
**Akses:** `KETUA_RT`, `SEKRETARIS_RW`.

**Request Body:**
```json
{
  "nik": "3216011505900021",
  "no_kk": "3216010101230012",
  "nama_lengkap": "Ahmad Fauzi",
  "jenis_kelamin": "L",
  "tempat_lahir": "Bekasi",
  "tanggal_lahir": "1990-05-15",
  "pekerjaan": "Wiraswasta",
  "nomor_hp": "081234500001",
  "status_hubungan_keluarga": "Kepala Keluarga",
  "status_warga": "TETAP"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Data warga berhasil ditambahkan, menunggu verifikasi Sekretaris RW",
  "data": {
    "nik_masked": "3216xxxxxxxx0021",
    "nama_lengkap": "Ahmad Fauzi",
    "verification_status": "MENUNGGU_VERIFIKASI"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "nik": ["NIK harus terdiri dari 16 digit angka"],
    "no_kk": ["Nomor KK tidak ditemukan"]
  }
}
```

---

#### 3.3.4 `GET /warga/{nik_hash}`

**Deskripsi:** Mengambil detail satu data warga berdasarkan referensi hash NIK (bukan NIK plaintext, untuk menghindari NIK asli muncul pada URL/log server).
**Akses:** `KETUA_RT` (lingkup RT sendiri), `SEKRETARIS_RW`, `KETUA_RW`, `SUPER_ADMIN`.

**Path Parameter:** `nik_hash` — hash HMAC-SHA256 dari NIK (diperoleh dari response list warga)

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data warga berhasil diambil",
  "data": {
    "nik_masked": "3216xxxxxxxx0421",
    "nama_lengkap": "Andi Wijaya",
    "jenis_kelamin": "L",
    "tempat_lahir": "Bekasi",
    "tanggal_lahir": "1990-04-21",
    "pekerjaan": "Wiraswasta",
    "status_hubungan_keluarga": "Kepala Keluarga",
    "status_warga": "TETAP",
    "no_kk_masked": "3216xxxxxxxx0012"
  }
}
```

**Error Response — `404 Not Found`:**
```json
{
  "success": false,
  "message": "Data warga tidak ditemukan"
}
```

---

#### 3.3.5 `PATCH /warga/{nik_hash}`

**Deskripsi:** Memperbarui data warga. Perubahan tertentu (mis. `status_warga`) memicu alur verifikasi Sekretaris RW sesuai proses bisnis pada dokumen skripsi.
**Akses:** `KETUA_RT` (mengajukan perubahan), `SEKRETARIS_RW` (memverifikasi).

**Request Body (contoh oleh Ketua RT):**
```json
{
  "pekerjaan": "Karyawan Swasta",
  "nomor_hp": "081234567890"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data warga berhasil diperbarui, menunggu verifikasi Sekretaris RW",
  "data": {
    "nik_masked": "3216xxxxxxxx0421",
    "verification_status": "MENUNGGU_VERIFIKASI"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "nomor_hp": ["Format nomor telepon tidak valid"]
  }
}
```

---

#### 3.3.6 `PATCH /warga/{nik_hash}/verify`

**Deskripsi:** Memverifikasi (menyetujui/menolak) data warga baru atau perubahan data warga yang berstatus `MENUNGGU_VERIFIKASI`.
**Akses:** `SEKRETARIS_RW`.

**Request Body (menyetujui):**
```json
{
  "decision": "APPROVED"
}
```

**Request Body (menolak):**
```json
{
  "decision": "REJECTED",
  "rejection_notes": "Data tidak sesuai dengan KTP yang dilampirkan"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data warga berhasil diverifikasi",
  "data": {
    "nik_masked": "3216xxxxxxxx0021",
    "status_warga": "TETAP",
    "verification_status": "TERVERIFIKASI"
  }
}
```

**Error Response — `409 Conflict`:**
```json
{
  "success": false,
  "message": "Data warga ini tidak sedang dalam status menunggu verifikasi"
}
```

---

### 3.4 Modul: Persuratan

#### 3.4.1 `POST /surat/pengajuan`

**Deskripsi:** Mengajukan permohonan surat baru oleh warga.
**Akses:** `WARGA`.

**Request Body:**
```json
{
  "jenis_surat": "SURAT_PENGANTAR",
  "keperluan": "Pengurusan administrasi pembuatan KTP baru"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Pengajuan surat berhasil dikirim",
  "data": {
    "pengajuan_id": 245,
    "tracking_code": "SRT-20260812-A8F3K2",
    "jenis_surat": "SURAT_PENGANTAR",
    "current_status": "SUBMITTED",
    "tanggal_pengajuan": "2026-08-12T09:45:00Z"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "jenis_surat": ["Jenis surat yang dipilih tidak valid"],
    "keperluan": ["Kolom keperluan wajib diisi"]
  }
}
```

---

#### 3.4.2 `GET /surat/pengajuan/track/{tracking_code}`

**Deskripsi:** Melacak status pengajuan surat menggunakan kode pelacakan publik — dapat diakses tanpa login penuh (khusus tracking_code milik sendiri).
**Akses:** Publik (memerlukan `tracking_code` yang valid sebagai bentuk verifikasi kepemilikan).

**Path Parameter:** `tracking_code` — string

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Status pengajuan berhasil diambil",
  "data": {
    "tracking_code": "SRT-20260812-A8F3K2",
    "jenis_surat": "SURAT_PENGANTAR",
    "current_status": "RT_REVIEW",
    "nomor_surat": null,
    "tanggal_pengajuan": "2026-08-12T09:45:00Z",
    "riwayat_status": [
      { "status": "SUBMITTED", "timestamp": "2026-08-12T09:45:00Z" },
      { "status": "RT_REVIEW", "timestamp": "2026-08-12T10:00:00Z" }
    ]
  }
}
```

**Error Response — `404 Not Found`:**
```json
{
  "success": false,
  "message": "Kode pelacakan tidak ditemukan"
}
```

---

#### 3.4.3 `GET /surat/pengajuan`

**Deskripsi:** Mengambil daftar pengajuan surat untuk keperluan verifikasi pengurus (area-scoped).
**Akses:** `KETUA_RT`, `SEKRETARIS_RW`, `KETUA_RW`, `SUPER_ADMIN`.

**Query Parameters:**
| Parameter | Tipe | Deskripsi |
|---|---|---|
| `current_status` | string | Filter status (`SUBMITTED`, `RT_REVIEW`, `RW_REVIEW`, `COMPLETED`, `REJECTED`) |
| `jenis_surat` | string | Filter jenis surat |
| `rt_code` | string | Filter wilayah (dipaksa ke RT milik user untuk role `KETUA_RT`) |
| `page`, `per_page` | integer | Pagination standar |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Daftar pengajuan surat berhasil diambil",
  "data": [
    {
      "pengajuan_id": 245,
      "tracking_code": "SRT-20260812-A8F3K2",
      "pemohon": "Andi Wijaya",
      "jenis_surat": "SURAT_PENGANTAR",
      "current_status": "RT_REVIEW",
      "tanggal_pengajuan": "2026-08-12T09:45:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 9,
    "last_page": 1
  }
}
```

---

#### 3.4.4 `POST /surat/pengajuan/{id}/verify`

**Deskripsi:** Melakukan verifikasi/persetujuan tahap berjenjang pada satu pengajuan surat. Endpoint yang sama digunakan Ketua RT (SUBMITTED→RT_REVIEW) maupun Sekretaris/Ketua RW (RT_REVIEW→RW_REVIEW→COMPLETED), dibedakan berdasarkan status saat ini dan role pemanggil.
**Akses:** `KETUA_RT`, `SEKRETARIS_RW`, `KETUA_RW`.

**Path Parameter:** `id` — `pengajuan_id` (integer)

**Header:**
```
Authorization: Bearer {access_token}
Idempotency-Key: 8f14e45f-ceea-4b3d-8e21-1c9a5b6e0a11
```

**Request Body:**
```json
{
  "action": "APPROVE",
  "catatan": "Data pemohon telah sesuai dan lengkap"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Pengajuan surat berhasil diverifikasi",
  "data": {
    "pengajuan_id": 245,
    "current_status": "RW_REVIEW",
    "verified_by": "Budi Santoso",
    "verified_at": "2026-08-12T10:00:00Z"
  }
}
```

**Error Response — `403 Forbidden` (mencoba verifikasi di luar wilayah/tahap kewenangan):**
```json
{
  "success": false,
  "message": "Anda tidak berwenang memverifikasi pengajuan surat pada tahap ini"
}
```

**Error Response — `409 Conflict` (status sudah berubah oleh proses lain):**
```json
{
  "success": false,
  "message": "Status pengajuan telah berubah, silakan muat ulang data terbaru"
}
```

---

### 3.5 Modul: Laporan & Aspirasi

#### 3.5.1 `POST /laporan-aspirasi`

**Deskripsi:** Mengirim laporan/aspirasi baru dari warga. Setelah tersimpan, sistem menjadwalkan job klasifikasi AI secara asinkron (lihat `SYSTEM_ARCHITECTURE.md` Bagian 2.2).
**Akses:** `WARGA`.

**Request Body:**
```json
{
  "judul_laporan": "Lampu jalan mati di Blok C",
  "teks_keluhan": "Lampu penerangan jalan di depan rumah nomor 12 sudah mati sejak 3 hari terakhir, mohon segera diperbaiki.",
  "lokasi_kejadian": "Jl. Mawar Blok C, depan rumah No. 12"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Laporan berhasil dikirim dan sedang diproses",
  "data": {
    "aspirasi_id": 88,
    "ticket_number": "LPR2026081200088",
    "current_status": "SUBMITTED",
    "submitted_at": "2026-08-12T11:00:00Z"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "judul_laporan": ["Kolom judul laporan wajib diisi"],
    "teks_keluhan": ["Deskripsi keluhan minimal 20 karakter"]
  }
}
```

---

#### 3.5.2 `GET /laporan-aspirasi/track/{ticket_number}`

**Deskripsi:** Melacak status penanganan laporan menggunakan nomor tiket.
**Akses:** Publik (memerlukan `ticket_number` yang valid).

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Status laporan berhasil diambil",
  "data": {
    "ticket_number": "LPR2026081200088",
    "judul_laporan": "Lampu jalan mati di Blok C",
    "current_status": "CLASSIFIED",
    "kategori_ai": "Infrastruktur",
    "submitted_at": "2026-08-12T11:00:00Z",
    "resolved_at": null
  }
}
```

**Error Response — `404 Not Found`:**
```json
{
  "success": false,
  "message": "Nomor tiket tidak ditemukan"
}
```

---

#### 3.5.3 `GET /laporan-aspirasi`

**Deskripsi:** Mengambil daftar laporan/aspirasi untuk keperluan pemantauan pengurus, dapat difilter berdasarkan kategori hasil klasifikasi AI.
**Akses:** `KETUA_RT`, `SEKRETARIS_RW`, `KETUA_RW`, `SUPER_ADMIN`.

**Query Parameters:**
| Parameter | Tipe | Deskripsi |
|---|---|---|
| `current_status` | string | Filter status penanganan |
| `kategori_ai` | string | Filter berdasarkan kategori hasil klasifikasi AI |
| `sort_by` | string | `submitted_at` (default) atau `skor_prioritas_ai` |
| `page`, `per_page` | integer | Pagination standar |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Daftar laporan berhasil diambil",
  "data": [
    {
      "aspirasi_id": 88,
      "ticket_number": "LPR2026081200088",
      "judul_laporan": "Lampu jalan mati di Blok C",
      "kategori_ai": "Infrastruktur",
      "skor_prioritas_ai": 72.5,
      "current_status": "CLASSIFIED",
      "submitted_at": "2026-08-12T11:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 23,
    "last_page": 2
  }
}
```

---

#### 3.5.4 `PATCH /laporan-aspirasi/{id}/status`

**Deskripsi:** Memperbarui status penanganan laporan oleh pengurus RW (disposisi/tindak lanjut).
**Akses:** `KETUA_RT`, `SEKRETARIS_RW`, `KETUA_RW`.

**Path Parameter:** `id` — `aspirasi_id` (integer)

**Request Body:**
```json
{
  "current_status": "IN_PROGRESS",
  "catatan_tindak_lanjut": "Sudah dikoordinasikan dengan petugas kebersihan dan penerangan RW"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Status laporan berhasil diperbarui",
  "data": {
    "aspirasi_id": 88,
    "current_status": "IN_PROGRESS",
    "updated_by": "Siti Aminah",
    "updated_at": "2026-08-12T13:00:00Z"
  }
}
```

**Error Response — `422 Unprocessable Entity` (transisi status tidak valid):**
```json
{
  "success": false,
  "message": "Transisi status tidak valid",
  "errors": {
    "current_status": ["Tidak dapat mengubah status dari CLOSED ke IN_PROGRESS"]
  }
}
```

---

### 3.6 Modul: Keuangan

#### 3.6.1 `GET /iuran-types`

**Deskripsi:** Mengambil daftar jenis iuran yang aktif.
**Akses:** Seluruh peran terautentikasi.

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data jenis iuran berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "Iuran Kebersihan & Keamanan",
      "code": "IKK",
      "default_amount": 50000.00,
      "is_active": true
    },
    {
      "id": 2,
      "name": "Kas RW",
      "code": "KAS-RW",
      "default_amount": 25000.00,
      "is_active": true
    }
  ]
}
```

---

#### 3.6.2 `POST /catatan-iuran`

**Deskripsi:** Mencatat transaksi pembayaran iuran warga oleh Ketua RT.
**Akses:** `KETUA_RT`.

**Request Body:**
```json
{
  "no_kk": "3216010101230012",
  "iuran_type_id": 1,
  "nominal": 50000.00,
  "periode_bulan": 8,
  "periode_tahun": 2026,
  "tanggal_pembayaran": "2026-08-10"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Pencatatan iuran berhasil disimpan, menunggu persetujuan Bendahara RW",
  "data": {
    "iuran_id": 512,
    "no_kk_masked": "3216xxxxxxxx0012",
    "nominal": 50000.00,
    "periode_bulan": 8,
    "periode_tahun": 2026,
    "status": "PENDING"
  }
}
```

**Error Response — `409 Conflict` (duplikasi pembayaran periode sama):**
```json
{
  "success": false,
  "message": "Iuran untuk KK ini pada periode Agustus 2026 sudah tercatat sebelumnya"
}
```

---

#### 3.6.3 `PATCH /catatan-iuran/{id}/approve`

**Deskripsi:** Menyetujui atau menolak transaksi iuran yang dicatat Ketua RT.
**Akses:** `BENDAHARA_RW`.

**Path Parameter:** `id` — `iuran_id` (integer)

**Request Body (menyetujui):**
```json
{
  "action": "APPROVE"
}
```

**Request Body (menolak):**
```json
{
  "action": "REJECT",
  "rejection_notes": "Nominal tidak sesuai dengan bukti transfer yang dilampirkan"
}
```

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Transaksi iuran berhasil disetujui",
  "data": {
    "iuran_id": 512,
    "status": "APPROVED",
    "approved_by": "Andi Wijaya",
    "approved_at": "2026-08-12T14:00:00Z"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "rejection_notes": ["Alasan penolakan wajib diisi ketika aksi REJECT"]
  }
}
```

---

#### 3.6.4 `GET /catatan-iuran/rekapitulasi`

**Deskripsi:** Mengambil rekapitulasi laporan keuangan iuran per periode/RT.
**Akses:** `BENDAHARA_RW`, `KETUA_RW`, `SUPER_ADMIN`.

**Query Parameters:**
| Parameter | Tipe | Wajib | Deskripsi |
|---|---|---|---|
| `periode_bulan` | integer | Ya | Bulan periode (1–12) |
| `periode_tahun` | integer | Ya | Tahun periode |
| `rt_code` | string | Tidak | Filter wilayah tertentu (kosongkan untuk seluruh RW) |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Rekapitulasi iuran berhasil diambil",
  "data": {
    "periode": "2026-08",
    "total_kk_wajib_bayar": 120,
    "total_kk_sudah_bayar": 98,
    "total_nominal_terkumpul": 4900000.00,
    "rincian_per_jenis_iuran": [
      { "jenis_iuran": "Iuran Kebersihan & Keamanan", "total_nominal": 4000000.00 },
      { "jenis_iuran": "Kas RW", "total_nominal": 900000.00 }
    ]
  }
}
```

---

### 3.7 Modul: Informasi Publik

#### 3.7.1 `GET /informasi-publik`

**Deskripsi:** Mengambil daftar pengumuman/berita/agenda yang telah dipublikasikan.
**Akses:** Publik.

**Query Parameters:**
| Parameter | Tipe | Deskripsi |
|---|---|---|
| `kategori` | string | Filter `PENGUMUMAN`/`BERITA`/`AGENDA` |
| `page`, `per_page` | integer | Pagination standar |

**Success Response — `200 OK`:**
```json
{
  "success": true,
  "message": "Data informasi publik berhasil diambil",
  "data": [
    {
      "id": 34,
      "judul": "Jadwal Kerja Bakti Agustus 2026",
      "kategori": "AGENDA",
      "tanggal_publikasi": "2026-08-01",
      "tanggal_agenda": "2026-08-17"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 5,
    "last_page": 1
  }
}
```

---

#### 3.7.2 `POST /informasi-publik`

**Deskripsi:** Membuat konten informasi publik baru.
**Akses:** `SEKRETARIS_RW`, `KETUA_RW`, `SUPER_ADMIN`.

**Request Body:**
```json
{
  "judul": "Jadwal Kerja Bakti Agustus 2026",
  "konten": "Kerja bakti bersama akan dilaksanakan pada hari Minggu, 17 Agustus 2026 pukul 07.00 WIB di lapangan RW.",
  "kategori": "AGENDA",
  "tanggal_agenda": "2026-08-17",
  "status": "PUBLISHED"
}
```

**Success Response — `201 Created`:**
```json
{
  "success": true,
  "message": "Informasi publik berhasil dipublikasikan",
  "data": {
    "id": 34,
    "judul": "Jadwal Kerja Bakti Agustus 2026",
    "status": "PUBLISHED"
  }
}
```

**Error Response — `422 Unprocessable Entity`:**
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid",
  "errors": {
    "kategori": ["Kategori harus salah satu dari: PENGUMUMAN, BERITA, AGENDA"]
  }
}
```

---

### 3.8 Modul: Dashboard

#### 3.8.1 `GET /dashboard/summary`

**Deskripsi:** Mengambil ringkasan statistik dashboard sesuai peran pengguna yang login (konten bervariasi otomatis per role, area-scoped untuk Ketua RT).
**Akses:** Seluruh peran terautentikasi (kecuali `WARGA`).

**Success Response — `200 OK` (contoh untuk role `KETUA_RW`):**
```json
{
  "success": true,
  "message": "Data dashboard berhasil diambil",
  "data": {
    "total_warga": 512,
    "total_kk": 128,
    "surat_menunggu_verifikasi": 6,
    "laporan_aktif": 14,
    "laporan_berdasarkan_kategori_ai": {
      "Infrastruktur": 5,
      "Keamanan": 3,
      "Kebersihan": 6
    },
    "total_iuran_bulan_ini": 4900000.00,
    "kepatuhan_iuran_persen": 81.6
  }
}
```

**Error Response — `401 Unauthorized`:**
```json
{
  "success": false,
  "message": "Silakan login terlebih dahulu"
}
```

---

## 4. Ringkasan Kode Error Kustom (Application Error Codes)

Selain kode status HTTP standar, response error `422`/`409` dapat menyertakan field opsional `error_code` untuk memudahkan penanganan kondisional di sisi klien tanpa parsing pesan teks:

| `error_code` | Kapan Muncul |
|---|---|
| `DUPLICATE_NIK` | Pendaftaran warga dengan NIK yang sudah terdaftar |
| `DUPLICATE_NO_KK` | Pendaftaran KK dengan nomor yang sudah terdaftar |
| `INVALID_STATUS_TRANSITION` | Perubahan status surat/laporan yang tidak sesuai alur (mis. `COMPLETED` → `SUBMITTED`) |
| `OUT_OF_SCOPE_AREA` | Percobaan akses/modifikasi data di luar wilayah RT milik pengguna |
| `DUPLICATE_IURAN_PERIOD` | Pencatatan iuran untuk KK & periode yang sama lebih dari sekali |
| `AI_CLASSIFICATION_PENDING` | Laporan belum selesai diklasifikasikan AI saat diakses (bersifat informatif, bukan error fatal) |

**Contoh response dengan `error_code`:**
```json
{
  "success": false,
  "message": "Nomor Kartu Keluarga sudah terdaftar pada sistem",
  "error_code": "DUPLICATE_NO_KK"
}
```

---

## 5. Catatan Implementasi

- Seluruh endpoint yang mengembalikan data terkait NIK/No. KK **wajib** menerapkan masking sebagian di response (kecuali endpoint detail yang eksplisit memerlukan data penuh dan dibatasi role tertentu), selaras dengan kebijakan perlindungan data pribadi pada `SYSTEM_ARCHITECTURE.md` Bagian 4.4.
- Setiap akses ke data unmasked, serta setiap aksi tulis (`POST`/`PATCH`/`DELETE`), dicatat ke tabel `audit_logs` sesuai skema pada `DATABASE_SCHEMA.md` Bagian 3.12 — bukan tanggung jawab klien, melainkan middleware/observer di backend.
- Endpoint dengan sifat *long-running* (mis. klasifikasi AI) tidak diproses secara sinkron dalam request-response `POST /laporan-aspirasi` — hasil klasifikasi baru tersedia setelah job asinkron selesai, dan dapat dipantau melalui polling pada `GET /laporan-aspirasi/track/{ticket_number}` atau (fase lanjutan) melalui WebSocket/notification.
- Versi API disertakan pada path (`/v1`) — perubahan yang breaking terhadap kontrak API harus dirilis sebagai `/v2`, bukan mengubah `/v1` secara langsung, demi kompatibilitas klien yang sudah terpasang (termasuk rencana aplikasi mobile di masa depan).
