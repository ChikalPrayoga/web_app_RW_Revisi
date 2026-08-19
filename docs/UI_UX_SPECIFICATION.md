# UI/UX SPECIFICATION
## SIM Layanan Warga RW 047 — Spesifikasi Antarmuka & Pengalaman Pengguna

| | |
|---|---|
| **Dokumen turunan dari** | PRD SIM Layanan Warga RW 047 v1.0, USER_STORIES.md v1.0 |
| **Versi dokumen** | 1.0 |
| **Status** | Draft acuan desain untuk tim Frontend & UI/UX |
| **Audiens** | Frontend Engineer, UI/UX Designer, QA Engineer |

---

## 0. Filosofi Desain

Sistem ini digunakan oleh dua kelompok pengguna yang sangat berbeda kebutuhannya:

- **Warga** — akses sesekali, seringkali dari HP, literasi digital bervariasi (dari remaja melek teknologi hingga orang tua yang baru pertama kali mengisi form daring). Kebutuhan utamanya: **jelas, cepat, tidak membingungkan**.
- **Pengurus (RT/RW/Bendahara/Sekretaris/Super Admin)** — pengguna berulang harian/mingguan, bekerja dengan tabel data, status, dan approval berjenjang. Kebutuhan utamanya: **efisien, informatif, minim klik untuk tugas rutin**.

Prinsip desain yang mengikat kedua kebutuhan ini:

1. **Kejelasan status di atas segalanya** — hampir seluruh alur kerja sistem berputar di sekitar status (`SUBMITTED`, `RT_REVIEW`, `APPROVED`, dst). Status harus selalu terlihat jelas lewat warna dan label, tidak pernah ambigu.
2. **Kepercayaan melalui konsistensi** — ini adalah layanan pemerintahan tingkat RT/RW yang menyentuh data pribadi (NIK, KK, keuangan). Visualnya harus terasa **resmi namun ramah**, bukan playful, bukan pula kaku seperti sistem birokrasi lama.
3. **Aksesibel untuk semua usia** — kontras tinggi, ukuran target sentuh besar, teks tidak pernah lebih kecil dari yang nyaman dibaca pengguna lanjut usia.

**Elemen Signature:** *Status Ribbon* — sebuah pita status berwarna dengan ikon di bagian atas setiap kartu data (surat, laporan, transaksi iuran) yang menjadi elemen visual paling dikenali di seluruh sistem, dipakai secara konsisten dari Portal Warga hingga Dashboard Pengurus. Alih-alih badge kecil di pojok, status menjadi elemen struktural yang tidak bisa terlewat — mencerminkan bahwa "mengetahui status Anda" adalah nilai inti produk ini (lihat PRD Bagian 1.4: Transparansi).

---

## 1. Panduan Desain & Komponen Utama (Design System & Styling Guidelines)

### 1.1 Skema Warna (Color Palette)

| Token | Hex | Penggunaan |
|---|---|---|
| **Primary** | `#1E5B4F` (Hijau Tua Kehutanan) | Warna identitas utama — header, tombol aksi utama, elemen navigasi aktif. Dipilih alih-alih biru korporat generik agar terasa membumi dan terhubung dengan citra "lingkungan/warga", tanpa jatuh ke kesan formal-dingin. |
| **Primary Light** | `#E8F0EE` | Latar belakang halus untuk elemen ber-state aktif/terpilih (mis. item sidebar aktif) |
| **Secondary** | `#C97A3D` (Terracotta Bata) | Aksen sekunder — elemen highlight non-kritikal, ikon dekoratif, aksen pada Status Ribbon kategori "informasi" |
| **Background** | `#FAF9F6` (Off-white Hangat) | Latar belakang utama aplikasi — lebih hangat dari putih murni agar tidak menyilaukan pada sesi penggunaan lama (khas dashboard pengurus) |
| **Surface (Card)** | `#FFFFFF` | Latar belakang kartu, tabel, modal — kontras lembut terhadap Background |
| **Border** | `#E2DFD8` | Garis pembatas antar elemen, pembatas tabel |
| **Text Primary** | `#1F2624` | Teks utama, judul |
| **Text Secondary** | `#5C6B67` | Teks pendukung, label, caption |
| **Success** | `#2F8A5B` | Status selesai/disetujui (`COMPLETED`, `APPROVED`, `RESOLVED`) |
| **Warning** | `#D69A2D` | Status dalam proses/menunggu (`SUBMITTED`, `RT_REVIEW`, `PENDING`, `IN_PROGRESS`) |
| **Danger** | `#C0432E` | Status ditolak/error (`REJECTED`, pesan kesalahan, aksi hapus) |
| **Info** | `#3D7EA6` | Notifikasi netral dan informasi sistem |

> **Catatan aksesibilitas:** seluruh kombinasi warna teks-di-atas-latar pada tabel ini memenuhi rasio kontras minimal **4.5:1** (WCAG AA) untuk teks berukuran normal. Warna status **tidak pernah** menjadi satu-satunya penanda — selalu disertai ikon dan label teks (lihat Bagian 1.3 Status Ribbon), agar tetap dapat dibedakan oleh pengguna dengan buta warna.

### 1.2 Tipografi (Typography)

| Peran | Font | Ukuran | Bobot | Penggunaan |
|---|---|---|---|---|
| **Display/Judul Halaman** | `Fraunces` (serif, karakter hangat-institusional) | 28–32px | 600 (Semibold) | Judul halaman utama (mis. "Dashboard Ketua RW", "Ajukan Surat Pengantar") |
| **Subjudul/Heading Section** | `Fraunces` | 20–22px | 500 (Medium) | Judul kartu, judul section dalam form panjang |
| **Body/Teks Utama** | `Inter` (sans-serif, sangat legible di ukuran kecil & UI padat) | 15–16px | 400 (Regular) | Isi teks, label form, konten tabel |
| **Body Bold** | `Inter` | 15–16px | 600 (Semibold) | Penekanan dalam teks, nilai penting di tabel (mis. nominal iuran) |
| **Caption/Meta** | `Inter` | 12–13px | 400–500 | Timestamp, keterangan tambahan, label status kecil |
| **Data Monospace** *(opsional)* | `JetBrains Mono` | 14px | 400 | Kode tracking (`SRT-20260812-X7F2`), nomor tiket — memudahkan pembacaan karakter demi karakter |

**Alasan pemilihan pairing:** `Fraunces` untuk judul memberi kesan resmi-hangat yang cocok untuk institusi warga (bukan serif klasik yang terasa kaku, bukan pula sans-serif generik yang terasa seperti aplikasi startup). `Inter` untuk body dipilih karena kejernihannya pada ukuran kecil dan padat data — krusial mengingat banyak layar sistem ini berupa tabel status yang harus dipindai cepat oleh pengurus. Kode tracking memakai monospace agar deretan karakter/angka mudah dibaca ulang tanpa salah (warga sering menyalin kode ini secara manual).

**Skala Tipografi (Type Scale):**
```
Display (Judul Halaman)   32px / 40px line-height / 600
H2 (Judul Section)        22px / 30px line-height / 500
H3 (Judul Card)           18px / 26px line-height / 500
Body                      16px / 24px line-height / 400
Body Small                14px / 20px line-height / 400
Caption                   12px / 16px line-height / 500
```

### 1.3 Komponen UI Standar

#### a. Tombol (Buttons)

| Varian | Gaya | Penggunaan |
|---|---|---|
| **Primary** | Latar `#1E5B4F`, teks putih, radius 8px, padding 12px 20px | Aksi utama per halaman (mis. "Ajukan Surat", "Simpan Data") — maksimal **satu** tombol Primary terlihat per layar |
| **Secondary** | Latar transparan, border 1px `#1E5B4F`, teks `#1E5B4F` | Aksi alternatif (mis. "Batal", "Kembali") |
| **Danger** | Latar `#C0432E`, teks putih | Aksi merusak/tidak dapat dibatalkan (mis. "Tolak Pengajuan", "Nonaktifkan Akun") — selalu memicu modal konfirmasi |
| **Ghost/Text** | Tanpa latar, teks `#1E5B4F`, underline saat hover | Aksi tersier (mis. "Lihat detail", link navigasi dalam teks) |
| **Icon Button** | Persegi 40x40px, radius 8px, ikon 20px di tengah | Aksi ringkas berulang di tabel (mis. ikon mata untuk "lihat detail") |

Seluruh tombol memiliki **target sentuh minimal 44x44px** (mendukung penggunaan di perangkat mobile oleh pengguna segala usia, sesuai NFR-05 pada PRD).

#### b. Bidang Isian (Input Fields)

- **Struktur:** label di atas field (bukan placeholder-as-label), field dengan border 1px `#E2DFD8`, radius 8px, padding 10px 14px, latar putih.
- **Field wajib** ditandai asterisk merah kecil (`*`) setelah label, bukan hanya warna.
- **Helper text** (petunjuk format, mis. "16 digit angka") tampil di bawah field dengan warna Text Secondary.
- **Input NIK/No. KK** menggunakan varian khusus dengan ikon gembok kecil di sisi kanan yang menandakan data akan dienkripsi — memberi sinyal kepercayaan (trust signal) kepada warga bahwa data sensitif mereka diperlakukan khusus.
- **Textarea** (mis. teks keluhan laporan) menampilkan penghitung karakter (mis. "142/500") di sudut kanan bawah agar warga tahu batas dan progres pengisian.

#### c. Kartu (Cards)

- Latar putih (`Surface`), radius 12px, shadow halus (`0 1px 3px rgba(0,0,0,0.06)`), padding 20px.
- Kartu data transaksional (surat, laporan, iuran) **selalu** menampilkan **Status Ribbon** — strip warna setinggi 4px di tepi atas kartu, warnanya mengikuti token status (Success/Warning/Danger/Info), disertai badge label status di pojok kanan atas kartu dengan ikon + teks (tidak hanya warna).

```
┌────────────────────────────────────┐
│▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│ ← Status Ribbon (4px, warna status)
│                                      │
│  Surat Pengantar         [● RT_REVIEW]│ ← badge ikon+teks
│  SRT-20260812-X7F2                  │
│  Diajukan: 12 Agt 2026              │
│                                      │
│  [Lihat Detail →]                   │
└────────────────────────────────────┘
```

#### d. Modal/Pop-up

- Lebar maksimal 480px (desktop), full-width dengan margin 16px (mobile).
- Struktur: judul modal (H3) → konten → baris tombol aksi rata kanan (Secondary di kiri, Primary/Danger di kanan).
- Modal konfirmasi aksi merusak (mis. "Tolak Pengajuan Surat") **wajib** menampilkan ringkasan konsekuensi secara eksplisit, contoh: *"Pengajuan akan ditolak dan warga akan menerima notifikasi. Tindakan ini tidak dapat dibatalkan."*
- Overlay latar belakang: `rgba(31, 38, 36, 0.5)`, modal menutup saat overlay diklik **kecuali** untuk modal dengan form yang sudah diisi sebagian (mencegah kehilangan input tidak sengaja).

---

## 2. Tata Letak & Struktur Halaman (Layout & Screen Structure)

### 2.1 Kerangka Navigasi Global

Sistem memiliki **dua kerangka navigasi berbeda** sesuai konteks pengguna:

**a. Portal Warga (Publik)** — navigasi minimal, fokus pada tugas:
```
┌──────────────────────────────────────────────┐
│  [Logo RW 047]      Beranda  Ajukan  Lacak    │ ← Header sederhana
├──────────────────────────────────────────────┤
│                                                │
│              (Konten halaman)                 │
│                                                │
├──────────────────────────────────────────────┤
│  Footer: kontak sekretariat, jam layanan       │
└──────────────────────────────────────────────┘
```

**b. Dashboard Pengurus** — navigasi lengkap berbasis Sidebar, standar aplikasi kerja:
```
┌───────────┬────────────────────────────────────┐
│           │  Header: [Breadcrumb]     [🔔] [👤] │
│  Sidebar  ├────────────────────────────────────┤
│           │                                     │
│  Logo     │         Konten Halaman              │
│  ────     │                                     │
│  Dashboard│                                     │
│  Warga    │                                     │
│  Surat    │                                     │
│  Laporan  │                                     │
│  Iuran    │                                     │
│  Info     │                                     │
│  ────     │                                     │
│  [Profil] │                                     │
└───────────┴────────────────────────────────────┘
```

- **Sidebar** menampilkan menu sesuai **peran pengguna yang login** — menu di luar kewenangan tidak ditampilkan sama sekali (bukan ditampilkan lalu di-disable), selaras dengan prinsip RBAC pada SYSTEM_ARCHITECTURE.md.
- **Header** menampilkan Breadcrumb (untuk orientasi navigasi di halaman berlapis, mis. "Laporan & Aspirasi > Detail Laporan #8823"), ikon notifikasi, dan menu profil pengguna.
- Item sidebar aktif ditandai latar `Primary Light` dan indikator garis vertikal `Primary` di sisi kiri.

### 2.2 Struktur Halaman Utama

#### a. Dashboard (Beranda Pengurus)

```
┌─────────────────────────────────────────────────┐
│ Selamat datang, [Nama Pengguna]                  │
│ [Peran] — [Wilayah jika relevan]                 │
├─────────────────────────────────────────────────┤
│ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐      │
│ │ Kartu  │ │ Kartu  │ │ Kartu  │ │ Kartu  │      │ ← Ringkasan statistik (angka besar + label)
│ │ Statistik│ Statistik│ Statistik│ Statistik│      │
│ └────────┘ └────────┘ └────────┘ └────────┘      │
├─────────────────────────────────────────────────┤
│ Butuh Tindakan Anda                              │
│ ┌───────────────────────────────────────────┐   │ ← Daftar item berstatus "pending" milik pengguna
│ │ [Status Ribbon Card] ...                    │   │   ditampilkan prioritas di atas (bukan grafik)
│ └───────────────────────────────────────────┘   │
├─────────────────────────────────────────────────┤
│ Grafik/Tren (opsional, mis. tren laporan bulanan)│
└─────────────────────────────────────────────────┘
```

Prinsip: bagian **"Butuh Tindakan Anda"** selalu berada di posisi paling menonjol setelah kartu statistik — dashboard ini bukan sekadar laporan pasif, tapi *action center* harian pengurus.

#### b. Halaman Form Input (mis. Tambah Data Warga, Ajukan Surat)

```
┌─────────────────────────────────────────────────┐
│ ← Kembali        Judul Form                      │
├─────────────────────────────────────────────────┤
│ [Progress indicator jika multi-step]             │
│                                                   │
│ Label Field 1 *                                  │
│ [                                          ]     │
│ Petunjuk format jika ada                         │
│                                                   │
│ Label Field 2 *                                  │
│ [                                          ]     │
│                                                   │
│ ─────────────────────────────────────────────   │
│                        [Batal]  [Simpan/Kirim]   │
└─────────────────────────────────────────────────┘
```

- Form panjang (mis. pendaftaran warga baru dengan banyak field) dipecah menjadi **section berlabel jelas** (mis. "Data Pribadi", "Data Keluarga") dengan spasi visual antar-section, bukan satu daftar field yang menerus tanpa jeda.
- Validasi real-time (inline) muncul di bawah field segera setelah pengguna keluar dari field (`onBlur`), tidak menunggu submit — mengurangi kejutan error di akhir.

#### c. Halaman Tabel Data (mis. Daftar Pengajuan Surat, Daftar Warga)

```
┌─────────────────────────────────────────────────┐
│ Judul Halaman                    [+ Tambah Baru] │
├─────────────────────────────────────────────────┤
│ [Cari...]  [Filter Status ▾]  [Filter Periode ▾] │
├─────────────────────────────────────────────────┤
│ Nama        │ Status         │ Tanggal  │ Aksi   │
├─────────────┼────────────────┼──────────┼────────┤
│ Ahmad Fauzi │ ● RT_REVIEW    │ 12 Agt   │ [👁]    │
│ Siti Aminah │ ● COMPLETED    │ 10 Agt   │ [👁]    │
├─────────────────────────────────────────────────┤
│              [◀ Sebelumnya]  1 2 3  [Berikutnya ▶]│
└─────────────────────────────────────────────────┘
```

- Kolom status pada tabel menggunakan **badge kecil berwarna + label teks** (representasi ringkas dari Status Ribbon), konsisten dengan kartu.
- Tabel mendukung **sorting** pada kolom yang relevan (mis. tanggal, prioritas AI untuk laporan) dan **filter** yang persisten selama sesi (tidak reset saat pindah halaman pagination).
- Baris tabel yang diklik membuka halaman/panel detail — seluruh baris menjadi target klik (bukan hanya ikon aksi), memudahkan penggunaan di layar sentuh.

#### d. Halaman Detail (mis. Detail Pengajuan Surat)

```
┌─────────────────────────────────────────────────┐
│ ← Kembali ke daftar                              │
│                                                   │
│ ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ (Status Ribbon)│
│ Surat Pengantar — SRT-20260812-X7F2  [● RT_REVIEW]│
│                                                   │
│ Timeline Status:                                 │
│  ● Diajukan — 12 Agt 09:30                       │
│  ● Diverifikasi RT — 12 Agt 14:00                │
│  ○ Menunggu Persetujuan RW                       │
│                                                   │
│ Detail Pemohon: ...                              │
│                                                   │
│                    [Tolak]      [Setujui]        │
└─────────────────────────────────────────────────┘
```

- **Timeline status** vertikal menjadi elemen wajib di setiap halaman detail entitas berstatus (surat, laporan, iuran) — memvisualisasikan progres secara linear, konsisten dengan filosofi "kejelasan status" (Bagian 0).
- Tombol aksi (Setujui/Tolak) hanya muncul jika pengguna yang login memiliki wewenang pada tahap status saat ini — bukan ditampilkan lalu di-disable.

#### e. Halaman Pengaturan (Settings)

```
┌───────────┬─────────────────────────────────────┐
│ Tab Menu  │  Konten Tab Aktif                    │
│ ─────     │                                       │
│ Profil    │  Form pengaturan sesuai tab           │
│ Keamanan  │                                       │
│ Notifikasi│                                       │
└───────────┴─────────────────────────────────────┘
```
Khusus Super Admin, halaman Pengaturan diperluas dengan tab tambahan: **Manajemen Pengguna**, **Master Data** (Jenis Iuran, dsb.), dan **Log Audit**.

### 2.3 Portal Warga — Struktur Khusus

Karena warga tidak login penuh, dua halaman berikut menjadi kritis dan dirancang seringan mungkin:

**Halaman Lacak Status** (surat/laporan):
```
┌─────────────────────────────────────┐
│  Lacak Status Pengajuan Anda         │
│  [Masukkan kode tracking/tiket]      │
│  [Lacak]                             │
│                                       │
│  ↓ setelah submit ↓                  │
│                                       │
│  ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ [● RT_REVIEW]     │
│  Timeline status lengkap             │
└─────────────────────────────────────┘
```
Satu halaman, satu input, satu aksi — sengaja dibuat sesederhana mungkin karena ini kemungkinan besar diakses dari HP dalam kondisi tergesa.

---

## 3. Panduan Interaksi & Umpan Balik Pengguna (Interaction & State Guidelines)

### 3.1 Status Komponen

| State | Perlakuan Visual | Berlaku Untuk |
|---|---|---|
| **Normal** | Sesuai spesifikasi dasar komponen (Bagian 1.3) | Semua komponen interaktif |
| **Hover** | Tombol: sedikit gelapkan warna latar (darken 8%); Baris tabel: latar `Primary Light` tipis; Kartu: shadow sedikit membesar | Perangkat dengan pointer (desktop) — **diabaikan** pada layar sentuh murni |
| **Focus** | Outline 2px warna `Primary` dengan offset 2px, **selalu terlihat** saat navigasi keyboard (Tab) | Seluruh elemen interaktif — wajib untuk aksesibilitas, tidak boleh dihilangkan dengan `outline: none` tanpa pengganti |
| **Active/Pressed** | Tombol: darken 12%, scale 0.98 sesaat | Tombol, item yang dapat diklik |
| **Disabled** | Opacity 40%, cursor `not-allowed`, tanpa hover effect | Tombol aksi yang belum memenuhi syarat (mis. "Setujui" sebelum field wajib terisi) |
| **Loading** | Lihat Bagian 3.2 | Tombol setelah diklik, area konten saat fetch data |

### 3.2 Loading State

| Konteks | Pola yang Digunakan |
|---|---|
| **Tombol setelah diklik** (submit form) | Teks tombol diganti spinner kecil + label tetap terlihat pudar (mis. "Menyimpan..."), tombol otomatis `disabled` untuk mencegah submit ganda |
| **Halaman tabel saat memuat data** | **Skeleton screen** — baris tabel placeholder abu-abu berbentuk sama seperti data asli, bukan spinner tunggal di tengah layar (memberi ekspektasi struktur konten yang akan muncul) |
| **Kartu dashboard saat memuat statistik** | Skeleton kotak dengan animasi shimmer halus menggantikan angka |
| **Proses latar belakang/asinkron** | Badge status menampilkan animasi shimmer halus dengan label status terkait — memberi sinyal bahwa sistem sedang memproses data |
| **Full-page loading** (jarang, hanya saat transisi besar) | Logo RW kecil dengan indikator progres halus di bawahnya, dibatasi maksimal 3 detik sebelum menampilkan pesan "Memuat lebih lama dari biasanya..." |

### 3.3 Notifikasi & Umpan Balik (Toast/Alert)

| Jenis | Warna & Ikon | Durasi | Contoh Pesan |
|---|---|---|---|
| **Sukses** | Latar `#EAF6EF`, ikon centang hijau (`Success`) | Auto-dismiss 4 detik | "Pengajuan surat berhasil dikirim." |
| **Peringatan** | Latar `#FBF2E1`, ikon segitiga seru kuning (`Warning`) | Auto-dismiss 6 detik atau manual close | "Data belum lengkap, mohon periksa kembali sebelum melanjutkan." |
| **Kesalahan** | Latar `#FBEAE7`, ikon silang merah (`Danger`) | Manual close (tidak auto-dismiss) | "Gagal menyimpan data. Periksa koneksi internet Anda dan coba lagi." |
| **Informasi** | Latar `#EAF2F7`, ikon info biru (`Info`) | Auto-dismiss 5 detik | "Laporan Anda sedang diklasifikasikan oleh sistem." |

**Prinsip penulisan pesan (selaras dengan nada suara antarmuka):**
- Pesan sukses menggunakan kata kerja pasif yang sama dengan label tombol yang memicunya (tombol "Kirim Laporan" → toast "Laporan berhasil dikirim").
- Pesan error **tidak pernah** menampilkan istilah teknis mentah (mis. kode error backend, stack trace) — selalu diterjemahkan ke bahasa yang dapat ditindaklanjuti pengguna.
- Toast muncul di **pojok kanan atas** (desktop) atau **bagian atas layar penuh lebar** (mobile), tidak menutupi tombol aksi utama yang sedang digunakan pengguna.
- Untuk kesalahan validasi form, pesan **tidak hanya** muncul sebagai toast tapi juga inline di bawah field terkait — toast saja tidak cukup karena pengguna mungkin sudah scroll menjauh dari field bermasalah.

### 3.4 Halaman/Kondisi Kosong (Empty State)

Selaras dengan prinsip "empty state adalah ajakan bertindak", setiap halaman tabel/daftar yang kosong menampilkan:
- Ilustrasi sederhana atau ikon besar bernuansa netral (bukan pesan error).
- Judul singkat yang menjelaskan kondisi (mis. "Belum ada laporan masuk").
- Kalimat pendukung dan/atau tombol aksi yang relevan (mis. tombol "Tambah Data Warga" pada tabel warga kosong; untuk pengurus tanpa aksi yang bisa diambil, cukup kalimat informatif seperti "Laporan baru akan muncul di sini setelah warga mengirimkan aspirasi").

---

## 4. Responsivitas & Adaptivitas (Responsiveness)

### 4.1 Breakpoint

| Breakpoint | Lebar Layar | Perangkat Target |
|---|---|---|
| **Mobile** | < 640px | Smartphone — akses utama warga via Portal Warga |
| **Tablet** | 640px – 1023px | Tablet, laptop kecil — akses sekunder pengurus |
| **Desktop** | ≥ 1024px | Laptop/PC — akses utama pengurus untuk kerja tabel/approval |

Pendekatan: **mobile-first** untuk seluruh halaman Portal Warga (sesuai NFR-05 pada PRD, mengingat sebagian besar interaksi warga terjadi via smartphone), dan **desktop-first dengan degradasi baik ke tablet/mobile** untuk Dashboard Pengurus (mengingat pekerjaan tabel data lebih nyaman di layar lebar, meski tetap harus dapat diakses dari HP untuk kondisi darurat/mobile).

### 4.2 Adaptasi Layout per Breakpoint

| Elemen | Desktop (≥1024px) | Tablet (640–1023px) | Mobile (<640px) |
|---|---|---|---|
| **Sidebar (Dashboard Pengurus)** | Selalu terbuka, lebar tetap 240px | Collapsed menjadi ikon saja (lebar 64px), expand on-hover/tap | Tersembunyi, diakses via ikon hamburger di header, tampil sebagai drawer full-height |
| **Kartu Statistik Dashboard** | Grid 4 kolom | Grid 2 kolom | Tumpuk 1 kolom, dapat digeser horizontal (carousel) untuk hemat ruang vertikal |
| **Tabel Data** | Tabel penuh dengan seluruh kolom | Kolom non-esensial disembunyikan (mis. kolom "Diinput oleh"), dapat dimunculkan via toggle | Berubah menjadi **daftar kartu** (card list), setiap baris data menjadi satu kartu ringkas — tabel lebar tidak dipaksakan muat di layar sempit |
| **Form Multi-kolom** | 2 kolom berdampingan untuk field terkait (mis. Tempat Lahir + Tanggal Lahir) | 2 kolom jika ruang cukup, else 1 kolom | Selalu 1 kolom, field bertumpuk penuh |
| **Modal** | Lebar tetap 480px, di tengah layar | Lebar 90% layar, di tengah | Full-screen (menutupi seluruh viewport), dengan tombol kembali di header modal alih-alih ikon silang kecil |
| **Header/Navbar** | Breadcrumb lengkap + ikon notifikasi + profil | Breadcrumb dipersingkat (hanya halaman saat ini + induk langsung) | Breadcrumb disembunyikan, judul halaman saja; ikon profil masuk ke dalam drawer sidebar |

### 4.3 Pertimbangan Khusus Mobile (Portal Warga)

- **Form pengajuan surat/laporan** dipecah menjadi tidak lebih dari 4–5 field terlihat sekaligus per scroll, dengan tombol "Lanjut/Kirim" selalu **sticky** di bagian bawah layar (tidak perlu scroll jauh untuk submit).
- **Ukuran font dasar minimum 16px** pada input field khusus di mobile — mencegah browser (terutama Safari iOS) melakukan auto-zoom saat field difokuskan, yang mengganggu pengalaman pengisian form.
- **Kode tracking/tiket** ditampilkan dengan tombol "Salin" (copy-to-clipboard) di sebelahnya — warga sering perlu menyalin kode ini ke aplikasi pesan untuk disimpan/dibagikan.
- Halaman publik (Beranda, Info Publik, Lacak Status) dioptimalkan agar dapat dimuat cepat pada koneksi seluler terbatas (gambar dikompresi, tanpa animasi berat) — selaras dengan NFR-02 Performance pada PRD.

### 4.4 Aksesibilitas Lintas Perangkat

- Seluruh elemen interaktif memiliki target sentuh minimal **44x44px**, dengan jarak antar-elemen minimal 8px untuk mencegah salah tekan pada layar kecil.
- Kontras warna dipertahankan identik di seluruh breakpoint — tidak ada penyesuaian warna yang mengorbankan keterbacaan demi estetika mobile.
- Navigasi tetap dapat dilakukan penuh via keyboard pada breakpoint Desktop/Tablet (untuk pengurus yang menggunakan laptop tanpa mouse/di lingkungan kerja formal).
- `prefers-reduced-motion` dihormati di seluruh breakpoint — animasi shimmer/transisi drawer dinonaktifkan otomatis bagi pengguna yang mengaktifkan preferensi tersebut di perangkatnya.

---

## 5. Ringkasan Token Desain (Quick Reference)

```css
:root {
  /* Warna */
  --color-primary: #1E5B4F;
  --color-primary-light: #E8F0EE;
  --color-secondary: #C97A3D;
  --color-background: #FAF9F6;
  --color-surface: #FFFFFF;
  --color-border: #E2DFD8;
  --color-text-primary: #1F2624;
  --color-text-secondary: #5C6B67;
  --color-success: #2F8A5B;
  --color-warning: #D69A2D;
  --color-danger: #C0432E;
  --color-info: #3D7EA6;

  /* Tipografi */
  --font-display: 'Fraunces', serif;
  --font-body: 'Inter', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  /* Radius & Spacing */
  --radius-sm: 8px;
  --radius-md: 12px;
  --touch-target-min: 44px;

  /* Breakpoint */
  --bp-tablet: 640px;
  --bp-desktop: 1024px;
}
```

> Dokumen ini menjadi acuan utama bagi tim Frontend saat implementasi komponen (mis. dengan Tailwind CSS sesuai rekomendasi SYSTEM_ARCHITECTURE.md) — seluruh token warna, tipografi, dan breakpoint di atas sebaiknya dikonfigurasi langsung sebagai design token/theme config, bukan nilai hardcode berulang di setiap komponen.
