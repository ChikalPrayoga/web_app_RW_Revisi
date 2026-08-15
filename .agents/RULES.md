# RULES.md
## SIM Layanan Warga RW 047 — Guardrails Mutlak untuk AI Agent

| | |
|---|---|
| **Dokumen turunan dari** | AGENTS.md, PRD.md, SYSTEM_ARCHITECTURE.md, DATABASE_SCHEMA.md, API_SPECIFICATION.md, USER_STORIES.md, DEVELOPMENT_SETUP.md (v1.0) |
| **Versi dokumen** | 1.0 |
| **Status** | **Mengikat (binding)** — berlaku di atas preferensi gaya pribadi mana pun dalam sesi |
| **Lokasi file** | `.agents/RULES.md` — dibaca oleh seluruh Worker Agent (AGENTS.md Bagian 1) sebelum menyentuh basis kode |

> **Status dokumen ini terhadap AGENTS.md:** AGENTS.md mendefinisikan *siapa* mengerjakan *apa*; RULES.md mendefinisikan *bagaimana* kode ditulis dan *apa yang tidak boleh terjadi*, tidak peduli Agent mana yang menulisnya. Jika ada instruksi dalam sesi percakapan yang bertentangan dengan dokumen ini, **dokumen ini menang** — kecuali pengguna secara eksplisit meminta perubahan pada RULES.md itu sendiri (bukan sekadar mengabaikannya untuk satu tugas).

---

## 1. Prinsip Utama Koding (Core Principles)

### 1.1 Clean Code

- [ ] Nama variabel, fungsi, dan kelas **harus** mendeskripsikan maksudnya tanpa perlu komentar tambahan (`$pengajuanSurat`, bukan `$data` atau `$temp`).
- [ ] Satu fungsi/method **hanya** melakukan satu tanggung jawab (Single Responsibility). Jika sebuah method Service melebihi ~40 baris atau menangani lebih dari satu concern (mis. validasi + persist + kirim notifikasi), pecah menjadi method/kelas terpisah.
- [ ] Tidak ada *magic number* atau *magic string* — status (`SUBMITTED`, `RT_REVIEW`, dst.), kode error, dan konstanta bisnis lainnya **wajib** didefinisikan sebagai Enum/konstanta bernama, merujuk persis ke nilai yang didefinisikan di DATABASE_SCHEMA.md dan API_SPECIFICATION.md.
- [ ] Tidak ada duplikasi logika (DRY) — jika logika yang sama muncul ≥2 kali (mis. enkripsi NIK, penegakan area scoping), logika **wajib** diekstrak ke method/trait/service bersama, bukan disalin-tempel.
- [ ] Kedalaman nesting (`if`/`foreach` bersarang) dibatasi maksimal 3 level — gunakan *early return*/*guard clause* untuk memangkas nesting yang dalam.

### 1.2 Konsistensi Arsitektur

- [ ] Setiap kode baru **wajib** mengikuti Layered Architecture pada SYSTEM_ARCHITECTURE.md Bagian 1.2: Controller (tipis) → Service (business logic) → Repository/Model (akses data). Tidak ada logika bisnis yang ditulis langsung di Controller atau di Blade view.
- [ ] Struktur folder modul **wajib** mengikuti `app/Modules/<NamaModul>/` sesuai AGENTS.md Bagian 5.1 — dilarang membuat folder terstruktur berbeda per modul yang tidak konsisten satu sama lain.
- [ ] Query database yang menyentuh entitas dengan area scoping (Warga, Pengajuan Surat, dsb.) **wajib** melalui Global Scope/Policy yang sudah ada — dilarang menulis query manual yang melewati lapisan otorisasi (lihat AGENTS.md Bagian 3.2).
- [ ] Penamaan (tabel, field JSON, route, kelas) **wajib** persis mengikuti konvensi pada AGENTS.md Bagian 5.2 — tidak menciptakan konvensi baru meski "lebih baik" menurut Agent, kecuali dikonfirmasi dan didokumentasikan ulang ke seluruh dokumen sumber terkait.

### 1.3 Modularitas

- [ ] Setiap modul bisnis (Auth, Kependudukan, Persuratan, Laporan & Aspirasi, Keuangan, Informasi Publik, Dashboard) **wajib** berdiri sebagai unit yang dapat diuji dan dipahami secara independen — dependensi antar-modul hanya melalui Service/Interface publik, bukan mengakses Model/Repository modul lain secara langsung.
- [ ] Kode yang bersifat lintas-modul (enkripsi, audit log, RBAC) **wajib** ditempatkan sebagai *shared utility/trait* di lokasi terpusat (mis. `app/Support/` atau `app/Traits/`), bukan diduplikasi per modul.
- [ ] Modul baru yang ditambahkan (di luar cakupan PRD versi awal) **wajib** mengikuti pola modul yang sudah ada — jangan memperkenalkan pola arsitektur berbeda hanya untuk satu modul baru.

---

## 2. Larangan Keras (Strict Prohibitions / Never Do)

> Setiap baris pada bagian ini adalah **larangan mutlak**. Tidak ada pengecualian berdasarkan tenggat waktu, permintaan verbal dalam sesi, atau asumsi "sementara saja".

### 2.1 Tidak Ada Kode Placeholder

- [ ] **DILARANG** menulis komentar seperti `// implement later`, `// TODO`, `// existing code`, `// ... rest unchanged`, atau bentuk apa pun yang menunda implementasi nyata.
- [ ] **DILARANG** mengirim/menyimpan berkas dengan fungsi yang isinya hanya `throw new Exception('Not implemented')` sebagai pengganti implementasi yang diminta — jika tugas tidak dapat diselesaikan penuh, laporkan sebagai blocker ke Orchestrator (AGENTS.md Bagian 3.3), jangan simpan kode setengah jadi seolah selesai.
- [ ] **DILARANG** menggunakan data dummy/hardcoded sebagai pengganti pemanggilan API/database nyata di luar konteks *seeder* atau *test fixture* yang eksplisit ditandai sebagai demo/test data.
- [ ] **DILARANG** memotong (truncate) bagian kode yang sudah ada dengan asumsi "developer akan mengisi sisanya" saat melakukan edit — setiap berkas yang disentuh harus lengkap dan dapat dijalankan setelah perubahan (lihat Bagian 4).

### 2.2 Tidak Ada Instalasi Dependensi Tanpa Izin

- [ ] **DILARANG** menjalankan `composer require`, `npm install <package>`, atau menambahkan dependensi baru ke `composer.json`/`package.json` tanpa konfirmasi eksplisit dari pengguna manusia dalam sesi berjalan.
- [ ] Jika suatu tugas tampak membutuhkan library baru, Agent **wajib** menghentikan implementasi, menjelaskan kebutuhannya (nama package, versi, alasan) ke pengguna/Orchestrator, dan menunggu persetujuan sebelum melanjutkan.
- [ ] Pengecualian **hanya** berlaku untuk dependensi yang sudah eksplisit disebut di DEVELOPMENT_SETUP.md/SYSTEM_ARCHITECTURE.md (mis. `spatie/laravel-permission`, `laravel/sanctum`) — dependensi ini boleh diinstal tanpa konfirmasi ulang karena sudah disetujui di tingkat arsitektur.

### 2.3 Tidak Ada Kredensial/Rahasia dalam Kode

- [ ] **DILARANG** menulis nilai API key, password database, `APP_KEY`, `DATA_SEARCH_HASH_KEY`, atau kredensial apa pun langsung di dalam kode sumber (`.php`, `.js`, `.blade.php`) — seluruhnya **wajib** diakses melalui `env()`/`config()` yang merujuk ke `.env` (lihat DEVELOPMENT_SETUP.md Bagian 2.4).
- [ ] **DILARANG** melakukan commit terhadap berkas `.env` yang berisi nilai nyata — hanya `.env.example` dengan nilai placeholder yang boleh masuk version control.
- [ ] **DILARANG** menuliskan NIK, No. KK, atau data pribadi warga nyata dalam kode, komentar, nama variabel contoh, atau pesan commit — gunakan data fiktif yang jelas-jelas palsu (mis. `3216xxxxxxxxxxxx`) untuk contoh/dokumentasi.
- [ ] **DILARANG** mencatat (log) nilai unmasked data sensitif ke `storage/logs/` atau output debug apa pun, sesuai SYSTEM_ARCHITECTURE.md Bagian 4.4.

### 2.4 Tidak Ada Perombakan Struktur Tanpa Konfirmasi

- [ ] **DILARANG** mengubah struktur folder proyek yang sudah disepakati (`app/Modules/...` sesuai AGENTS.md Bagian 5.1) tanpa konfirmasi eksplisit — termasuk memindahkan, mengganti nama, atau menghapus folder/berkas yang sudah ada.
- [ ] **DILARANG** mengubah skema tabel database (menambah/menghapus/mengganti tipe kolom) di luar proses migration yang terdokumentasi dan tanpa merujuk balik ke DATABASE_SCHEMA.md — perubahan skema **wajib** disertai pembaruan dokumen sumber, bukan hanya kode.
- [ ] **DILARANG** mengubah kontrak endpoint (path, method, struktur request/response) yang sudah didefinisikan di API_SPECIFICATION.md tanpa menandainya eksplisit sebagai **deviasi yang perlu konfirmasi manusia** (AGENTS.md Bagian 4.4) — Frontend/klien lain bergantung pada kontrak tersebut.
- [ ] **DILARANG** melakukan refactor skala besar (mis. mengganti pola arsitektur, mengganti library inti) sebagai efek samping dari tugas kecil yang diminta — refactor besar adalah tugas tersendiri yang wajib direncanakan dan disetujui secara terpisah.

### 2.5 Larangan Tambahan (Konsisten dengan AGENTS.md Bagian 3.2)

- [ ] **DILARANG** menjalankan `migrate:fresh`, `DROP TABLE`, atau perintah destruktif lain di luar environment `local`.
- [ ] **DILARANG** melakukan hard-delete pada entitas yang menerapkan SoftDeletes.
- [ ] **DILARANG** menonaktifkan/melewati validasi Policy/RBAC "sementara untuk testing" dan lupa mengaktifkannya kembali.
- [ ] **DILARANG** melakukan `git push --force` ke branch `main`/`develop`.

### 2.6 Token & Workflow Loop Prevention (Pencegahan Token Boros & Looping)

- **DILARANG membuat file baru non-kode:** Jangan pernah membuat file Markdown (.md), laporan analisis, audit, atau rangkuman baru kecuali pengguna secara eksplisit mengetik kata "buatkan dokumentasi/audit".
- **DILARANG melakukan analisis berulang:** Setelah selesai menulis/mengedit kode fitur, langsung hentikan eksekusi (STOP). Jangan menjalankan siklus review/audit otomatis yang membaca ulang seluruh file.
- **Fokus File Spesifik:** Hanya baca dan edit file yang berkaitan langsung dengan tugas yang diminta. Jangan lakukan scanning ke seluruh folder proyek.
- **Eksekusi Sekali Jalan:** Selesaikan modifikasi kode, berikan konfirmasi singkat bahwa tugas selesai, dan tunggu instruksi pengguna berikutnya.

> **Klarifikasi istilah:** larangan pada bagian ini menyasar **berkas dokumen** (`.md` berisi laporan/analisis/rangkuman/audit naratif yang ditujukan untuk dibaca manusia). Larangan ini **tidak berlaku** untuk tabel `audit_logs` pada basis data maupun kode yang mengimplementasikannya (Model, Migration, Service, Observer) — `audit_logs` adalah bagian dari fitur aplikasi inti (lihat DATABASE_SCHEMA.md §3.12 dan AGENTS.md §5.3) yang wajib diimplementasikan sebagai kode, bukan dokumen audit yang dimaksud pada larangan di atas.

---

## 3. Standar Penulisan & Kualitas Kode (Code Quality Standards)

### 3.1 Error Handling & Validasi — Wajib di Setiap Fungsi/Modul

- [ ] Setiap endpoint yang menerima input pengguna **wajib** memiliki Form Request class untuk validasi — tidak ada validasi inline di Controller, dan tidak ada endpoint yang mempercayai input tanpa validasi eksplisit.
- [ ] Setiap pemanggilan layanan eksternal (mis. AI Classification Service) **wajib** dibungkus `try-catch` dengan penanganan spesifik untuk timeout, koneksi gagal, dan response tidak valid — mengikuti pola retry pada AGENTS.md Bagian 3.3, tidak membiarkan exception menjalar tanpa ditangani hingga merusak proses utama (mis. gagalnya klasifikasi AI tidak boleh membuat penyimpanan laporan warga ikut gagal).
- [ ] Setiap operasi yang melibatkan lebih dari satu perubahan data terkait (mis. update status surat + insert audit log) **wajib** dibungkus dalam **database transaction** (`DB::transaction()`) agar tetap konsisten (ACID) jika salah satu langkah gagal.
- [ ] Setiap Service method **wajib** menangani dan melempar exception yang bermakna (custom Exception class, bukan generic `Exception`) agar Controller dapat menerjemahkannya ke kode status HTTP yang tepat sesuai API_SPECIFICATION.md Bagian 1.4 (`404`, `409`, `422`, dst.) — tidak ada exception generik yang diteruskan mentah ke response API.
- [ ] Pesan error yang dikembalikan ke pengguna **wajib** ramah dan tidak membocorkan detail internal (query SQL, stack trace, path server) — selaras dengan prinsip pada UI_UX_SPECIFICATION.md Bagian 3.3.

### 3.2 Standar Tipe Data

- [ ] Seluruh method PHP **wajib** menggunakan **strict type hints** untuk parameter dan return type (`function createLaporan(array $data): LaporanAspirasi`, bukan tanpa tipe atau `mixed` tanpa alasan kuat).
- [ ] Berkas PHP **wajib** mendeklarasikan `declare(strict_types=1);` di baris pertama setelah tag pembuka.
- [ ] Properti Eloquent Model yang berelasi dengan enkripsi/hash (`nik`, `no_kk`) **wajib** menggunakan Cast kustom bertipe eksplisit (mis. `EncryptedNik::class`), bukan `string` polos yang membuka risiko lupa mengenkripsi.
- [ ] Data Transfer Object (DTO) atau Laravel API Resource **wajib** digunakan untuk membentuk response API — dilarang mengembalikan Eloquent Model mentah langsung sebagai response JSON, agar struktur field (camelCase sesuai API_SPECIFICATION.md) dan data ter-mask terjamin konsisten.
- [ ] Untuk kode Frontend (JS/Alpine.js), gunakan JSDoc type annotation minimal pada fungsi yang menerima/mengembalikan struktur data kompleks (payload form, response API) agar bentuk data eksplisit meski tanpa TypeScript penuh.

### 3.3 Komentar & Dokumentasi Fungsi

- [ ] Setiap method Service publik **wajib** memiliki PHPDoc yang menjelaskan tujuan, bukan mengulang nama method — sertakan referensi `@see US-xxx` ke USER_STORIES.md bila method tersebut mengimplementasikan Acceptance Criteria tertentu (format persis seperti contoh di AGENTS.md Bagian 5.5).
- [ ] Komentar inline **hanya** ditulis untuk menjelaskan **mengapa** (alasan bisnis/teknis non-obvious), bukan **apa** yang sudah jelas dari kode itu sendiri. Contoh yang **dilarang**: `// set status jadi submitted` di atas `$status = 'SUBMITTED';`. Contoh yang **benar**: `// klasifikasi AI berjalan async agar response ke warga tidak tertahan`.
- [ ] Method yang mengandung aturan bisnis non-trivial dari PRD (mis. transisi status berjenjang, perhitungan skor prioritas) **wajib** mencantumkan referensi ke bagian dokumen sumber terkait (mis. `// lihat PRD.md FR-04`).
- [ ] Dokumentasi tidak boleh menjadi basi (out of sync) — jika Agent mengubah perilaku fungsi, komentar/PHPDoc terkait **wajib** diperbarui dalam edit yang sama, bukan ditinggalkan menyesatkan.

---

## 4. Prosedur Modifikasi Berkas (File Modification Protocol)

### 4.1 Sebelum Mengedit Berkas

- [ ] **Baca isi lengkap berkas** yang akan diedit terlebih dahulu — dilarang mengedit berdasarkan asumsi/ingatan dari konteks sebelumnya tanpa membaca ulang kondisi berkas saat ini, terutama jika berkas mungkin telah diubah oleh Worker Agent lain.
- [ ] **Identifikasi seluruh pemanggil (caller)** dari fungsi/kelas yang akan diubah (cari referensi penggunaan di seluruh basis kode) — memastikan perubahan tidak merusak kode lain yang bergantung padanya.
- [ ] **Rujuk kembali ke dokumen sumber terkait** (DATABASE_SCHEMA.md untuk perubahan model, API_SPECIFICATION.md untuk perubahan endpoint, USER_STORIES.md untuk perubahan perilaku bisnis) untuk memastikan perubahan yang direncanakan konsisten dengan spesifikasi yang disepakati.
- [ ] **Periksa keberadaan test yang sudah ada** untuk berkas/fungsi terkait — pahami perilaku yang divalidasi test tersebut sebelum mengubah kode agar tidak merusak kontrak yang sudah teruji tanpa disadari.

### 4.2 Selama Mengedit Berkas

- [ ] **Tulis kode secara utuh dan dapat dijalankan** — dilarang menyisakan bagian kode yang terpotong, tidak lengkap, atau bergantung pada asumsi "akan dilengkapi nanti" (lihat larangan placeholder pada Bagian 2.1).
- [ ] **Perubahan bersifat minimal dan bertarget** — mengubah hanya bagian yang relevan dengan tugas yang diminta; tidak melakukan reformat/refactor bagian kode lain yang tidak diminta dalam edit yang sama (memudahkan review perubahan).
- [ ] **Konsisten dengan gaya kode sekitar** — indentasi, penamaan lokal, dan pola yang sudah dipakai di berkas yang sama tetap diikuti, meskipun berbeda dari preferensi Agent, kecuali gaya tersebut secara eksplisit melanggar aturan pada dokumen ini.
- [ ] **Tidak menghapus kode yang masih digunakan** tanpa memastikan lebih dulu (via pencarian referensi) bahwa kode tersebut benar-benar tidak dipanggil di tempat lain.

### 4.3 Setelah Mengedit Berkas

- [ ] **Jalankan linter** (`pint`/`npm run lint`) pada berkas yang diubah dan pastikan lolos tanpa error, sesuai DEVELOPMENT_SETUP.md Bagian 5.
- [ ] **Jalankan test suite yang relevan** (minimal test yang menyentuh modul/berkas yang diubah) dan pastikan lolos sebelum melaporkan tugas selesai — kegagalan test berarti tugas **belum selesai**, bukan "selesai dengan catatan".
- [ ] **Validasi kompatibilitas lintas-lapisan** — jika mengubah struktur data di Model, periksa Migration, Service, API Resource, dan Frontend consumer yang bergantung pada struktur tersebut tetap sinkron.
- [ ] **Perbarui dokumentasi terkait** jika perubahan kode menggeser kontrak yang didokumentasikan (endpoint, skema tabel, Acceptance Criteria) — tandai sebagai deviasi yang perlu dikonfirmasi manusia sesuai AGENTS.md Bagian 4.4, jangan biarkan dokumen dan kode menyimpang diam-diam.
- [ ] **Verifikasi tidak ada rahasia yang ter-commit** — periksa ulang diff perubahan tidak mengandung nilai kredensial nyata sebelum melakukan `git_commit` (lihat Bagian 2.3).
- [ ] **Laporkan ringkasan perubahan yang jujur dan spesifik** ke Orchestrator/pengguna — sebutkan berkas yang diubah, alasan, dan hasil test/linting, bukan klaim generik "sudah selesai" tanpa rincian yang dapat diverifikasi.

---

## 5. Ringkasan Checklist Cepat (Sebelum Setiap Commit)

```
[ ] Tidak ada placeholder/TODO/kode setengah jadi
[ ] Tidak ada dependensi baru tanpa izin eksplisit
[ ] Tidak ada kredensial/rahasia hardcoded
[ ] Struktur folder & skema DB tidak berubah tanpa konfirmasi
[ ] Validasi input & error handling lengkap di setiap fungsi baru
[ ] Strict type hints & declare(strict_types=1) diterapkan
[ ] PHPDoc/komentar menjelaskan "mengapa", bukan "apa"
[ ] Berkas dibaca penuh sebelum diedit, referensi pemanggil diperiksa
[ ] Linter lolos
[ ] Test suite relevan lolos
[ ] Dokumentasi sumber diperbarui jika ada deviasi kontrak
[ ] Diff diperiksa ulang: tidak ada rahasia ter-commit
```

> **Prinsip penutup:** jika ragu apakah suatu tindakan diperbolehkan oleh dokumen ini, **defaultnya adalah berhenti dan bertanya**, bukan melanjutkan dengan asumsi terbaik. Guardrails pada dokumen ini dirancang untuk aplikasi yang menangani data kependudukan dan keuangan warga nyata — kesalahan yang "cepat diperbaiki nanti" pada domain ini memiliki konsekuensi yang tidak sepele.
