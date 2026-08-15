# USER STORIES & WORKFLOWS
## SIM Layanan Warga RW 047 — Cerita Pengguna & Alur Kerja

| | |
|---|---|
| **Dokumen turunan dari** | PRD SIM Layanan Warga RW 047 v1.0, SYSTEM_ARCHITECTURE.md v1.0, DATABASE_SCHEMA.md v1.0, API_SPECIFICATION.md v1.0 |
| **Versi dokumen** | 1.0 |
| **Status** | Draft untuk validasi tim Development & QA |
| **Audiens** | Product Manager, Backend/Frontend Engineer, QA Engineer |

---

## 1. Daftar Cerita Pengguna (User Stories List)

Cerita pengguna dikelompokkan berdasarkan modul fungsional (sesuai PRD Bagian 4) dan disilangkan dengan peran (persona) yang relevan (sesuai PRD Bagian 2). Setiap story diberi kode unik `US-[Modul]-[Nomor]` untuk ditautkan ke backlog/sprint planning.

### 1.1 Modul Autentikasi & Manajemen Pengguna

#### US-AUTH-01
**Sebagai** pengguna terdaftar (peran apa pun), **saya ingin** login menggunakan email dan kata sandi, **sehingga** saya dapat mengakses fitur sesuai kewenangan saya.

**Kriteria Penerimaan:**
- [ ] **Given** saya memasukkan email dan password yang valid, **when** saya menekan tombol login, **then** sistem menerbitkan access token dan mengarahkan saya ke dashboard sesuai peran saya.
- [ ] **Given** saya memasukkan kredensial yang salah, **when** saya menekan tombol login, **then** sistem menampilkan pesan error tanpa memberi tahu apakah email atau password yang salah (mencegah user enumeration).
- [ ] **Given** saya sudah gagal login 5 kali dalam 1 menit, **when** saya mencoba login lagi, **then** sistem menolak dengan pesan rate limit (`429`) dan waktu tunggu yang jelas.

---

#### US-AUTH-02
**Sebagai** pengguna yang sedang login, **saya ingin** logout dari sistem, **sehingga** sesi saya tidak dapat disalahgunakan pihak lain jika perangkat saya diakses orang lain.

**Kriteria Penerimaan:**
- [ ] **Given** saya sedang login, **when** saya menekan tombol logout, **then** token akses saya diinvalidasi dan saya diarahkan ke halaman login.
- [ ] **Given** token saya sudah diinvalidasi, **when** saya mencoba mengakses endpoint terproteksi dengan token lama, **then** sistem menolak dengan `401 Unauthorized`.

---

#### US-AUTH-03
**Sebagai** Super Admin, **saya ingin** membuat, mengubah, dan menonaktifkan akun pengurus (Ketua RT, Sekretaris RW, Bendahara RW, Ketua RW), **sehingga** pengelolaan akses sistem tetap terkendali dan sesuai struktur organisasi RW yang berlaku.

**Kriteria Penerimaan:**
- [ ] **Given** saya login sebagai Super Admin, **when** saya membuat akun baru dengan peran dan wilayah RT tertentu, **then** akun tersimpan berstatus `ACTIVE` dan dapat langsung digunakan untuk login.
- [ ] **Given** saya menonaktifkan sebuah akun, **when** pemilik akun tersebut mencoba login, **then** sistem menolak login meski kredensial benar, dengan pesan bahwa akun nonaktif.
- [ ] **Given** saya bukan Super Admin, **when** saya mencoba mengakses endpoint manajemen pengguna, **then** sistem menolak dengan `403 Forbidden`.

---

### 1.2 Modul Master Data Kependudukan

#### US-KEP-01
**Sebagai** Ketua RT, **saya ingin** menambahkan dan memperbarui data warga di wilayah RT saya, **sehingga** data kependudukan RW selalu akurat dan terkini.

**Kriteria Penerimaan:**
- [ ] **Given** saya login sebagai Ketua RT, **when** saya menambahkan data warga baru dengan NIK dan No. KK valid, **then** data tersimpan dengan status `MENUNGGU_VERIFIKASI`.
- [ ] **Given** saya memasukkan NIK yang sudah terdaftar sebelumnya, **when** saya submit form, **then** sistem menolak dengan pesan validasi bahwa NIK sudah terdaftar.
- [ ] **Given** saya mencoba menambah/mengubah data warga di RT lain, **when** saya mengirim request, **then** sistem menolak dengan `403 Forbidden` (area scoping).

---

#### US-KEP-02
**Sebagai** Sekretaris RW, **saya ingin** memverifikasi perubahan data warga yang diajukan Ketua RT, **sehingga** data kependudukan yang masuk ke sistem sudah tervalidasi sebelum menjadi data resmi.

**Kriteria Penerimaan:**
- [ ] **Given** ada data warga berstatus `MENUNGGU_VERIFIKASI`, **when** saya menyetujui data tersebut, **then** status berubah menjadi status kependudukan definitif (mis. `TETAP`).
- [ ] **Given** saya menolak data warga, **when** saya mengirim alasan penolakan, **then** Ketua RT terkait dapat melihat catatan penolakan dan mengajukan ulang.

---

#### US-KEP-03
**Sebagai** Ketua RW, **saya ingin** melihat rekap jumlah warga dan Kartu Keluarga di seluruh wilayah RW, **sehingga** saya memiliki gambaran demografis untuk pengambilan keputusan.

**Kriteria Penerimaan:**
- [ ] **Given** saya login sebagai Ketua RW, **when** saya membuka dashboard, **then** saya melihat total warga dan total KK dari **seluruh RT**, bukan hanya satu RT (tidak dibatasi area scoping seperti Ketua RT).

---

### 1.3 Modul Administrasi Surat

#### US-SRT-01
**Sebagai** warga, **saya ingin** mengajukan permohonan surat pengantar secara online tanpa perlu datang langsung ke rumah RT, **sehingga** proses administrasi lebih cepat dan tidak tergantung jam kerja pengurus.

**Kriteria Penerimaan:**
- [ ] **Given** saya mengisi formulir pengajuan surat dengan NIK terdaftar dan jenis surat valid, **when** saya submit, **then** sistem menerbitkan kode tracking unik dan status awal `SUBMITTED`.
- [ ] **Given** NIK yang saya masukkan tidak terdaftar di data kependudukan RW, **when** saya submit, **then** sistem menolak dengan pesan validasi yang jelas.

---

#### US-SRT-02
**Sebagai** warga, **saya ingin** memantau status pengajuan surat saya menggunakan kode tracking, **sehingga** saya tahu kapan surat saya selesai tanpa perlu login atau menghubungi pengurus.

**Kriteria Penerimaan:**
- [ ] **Given** saya memasukkan kode tracking yang valid, **when** saya membuka halaman pelacakan, **then** saya melihat status terkini beserta riwayat perubahan status dengan timestamp.
- [ ] **Given** saya memasukkan kode tracking yang salah/tidak ada, **when** saya submit, **then** sistem menampilkan pesan `404 Not Found` yang ramah pengguna.

---

#### US-SRT-03
**Sebagai** Ketua RT, **saya ingin** meninjau dan memverifikasi pengajuan surat dari warga di wilayah saya, **sehingga** hanya pengajuan yang sah dan lengkap yang diteruskan ke tahap RW.

**Kriteria Penerimaan:**
- [ ] **Given** ada pengajuan surat berstatus `SUBMITTED` dari warga di RT saya, **when** saya menyetujui, **then** status berubah menjadi `RT_REVIEW` dan diteruskan ke antrean Sekretaris/Ketua RW.
- [ ] **Given** saya menolak pengajuan, **when** saya mengirim alasan penolakan, **then** status berubah menjadi `REJECTED` dan warga dapat melihat alasannya melalui halaman tracking.
- [ ] **Given** pengajuan surat berasal dari warga di RT lain, **when** saya membuka daftar pengajuan, **then** pengajuan tersebut **tidak muncul** dalam daftar saya.

---

#### US-SRT-04
**Sebagai** Ketua RW (atau Sekretaris RW), **saya ingin** memberikan persetujuan final atas pengajuan surat yang sudah diverifikasi Ketua RT, **sehingga** surat resmi dapat diterbitkan dengan nomor surat yang sah.

**Kriteria Penerimaan:**
- [ ] **Given** pengajuan berstatus `RT_REVIEW`, **when** saya menyetujui, **then** status berubah menjadi `COMPLETED` dan sistem otomatis menerbitkan nomor surat resmi.
- [ ] **Given** saya mencoba menyetujui pengajuan yang masih berstatus `SUBMITTED` (belum melalui RT), **when** saya mengirim request, **then** sistem menolak dengan `409 Conflict` karena melompati tahapan yang seharusnya.

---

### 1.4 Modul Laporan & Aspirasi Warga

#### US-LAP-01
**Sebagai** warga, **saya ingin** menyampaikan laporan atau aspirasi terkait kondisi lingkungan RW secara online, **sehingga** keluhan saya terdokumentasi resmi dan dapat ditindaklanjuti secara transparan.

**Kriteria Penerimaan:**
- [ ] **Given** saya mengisi form laporan dengan judul, deskripsi (≥20 karakter), dan lokasi, **when** saya submit, **then** sistem menerbitkan nomor tiket unik dan status `SUBMITTED`.
- [ ] **Given** saya mengisi deskripsi kurang dari 20 karakter, **when** saya submit, **then** sistem menolak dengan pesan validasi `422`.

---

#### US-LAP-02
**Sebagai** sistem, **saya ingin** mengklasifikasikan kategori dan prioritas laporan warga secara otomatis menggunakan AI, **sehingga** pengurus dapat memprioritaskan penanganan tanpa harus membaca dan mengkategorikan setiap laporan secara manual.

**Kriteria Penerimaan:**
- [ ] **Given** laporan baru tersimpan dengan status `SUBMITTED`, **when** job klasifikasi berjalan di background, **then** status berubah menjadi `CLASSIFIED` beserta `kategoriAi` dan `skorPrioritasAi` terisi.
- [ ] **Given** layanan AI eksternal gagal merespons/timeout, **when** job klasifikasi dijalankan, **then** laporan tetap berstatus `SUBMITTED` (tidak stuck error) dan job otomatis dijadwalkan ulang (retry).
- [ ] **Given** klasifikasi AI gagal berulang kali melebihi batas retry, **when** batas tersebut tercapai, **then** laporan ditandai untuk klasifikasi manual oleh pengurus, bukan dibiarkan tanpa status.

---

#### US-LAP-03
**Sebagai** Ketua RT/Sekretaris RW/Ketua RW, **saya ingin** melihat daftar laporan warga terurut berdasarkan prioritas hasil klasifikasi AI, **sehingga** saya dapat menindaklanjuti isu yang paling mendesak terlebih dahulu.

**Kriteria Penerimaan:**
- [ ] **Given** ada beberapa laporan dengan skor prioritas berbeda, **when** saya membuka daftar laporan dan mengurutkan berdasarkan prioritas, **then** laporan dengan skor tertinggi tampil di posisi teratas.
- [ ] **Given** saya memfilter berdasarkan kategori tertentu (mis. "Infrastruktur"), **when** saya menerapkan filter, **then** hanya laporan dengan kategori tersebut yang ditampilkan.

---

#### US-LAP-04
**Sebagai** pengurus RW (RT/Sekretaris/Ketua RW), **saya ingin** memperbarui status penanganan laporan warga (proses → selesai), **sehingga** warga dapat memantau progres penanganan keluhannya secara transparan.

**Kriteria Penerimaan:**
- [ ] **Given** laporan berstatus `CLASSIFIED` atau `IN_PROGRESS`, **when** saya mengubah status menjadi `RESOLVED` dengan catatan penyelesaian, **then** timestamp `resolvedAt` tercatat dan warga dapat melihat catatan tersebut via tracking.
- [ ] **Given** laporan sudah berstatus `CLOSED`, **when** saya mencoba mengubah statusnya kembali, **then** sistem menolak dengan pesan bahwa laporan sudah ditutup secara final.

---

### 1.5 Modul Keuangan RW (Iuran & Kas)

#### US-KEU-01
**Sebagai** Ketua RT, **saya ingin** mencatat pembayaran iuran warga di wilayah saya, **sehingga** rekapitulasi kas RW dapat disusun secara akurat dan tidak lagi manual di buku catatan.

**Kriteria Penerimaan:**
- [ ] **Given** saya mencatat pembayaran iuran dengan No. KK, jenis iuran, nominal, dan periode yang valid, **when** saya submit, **then** transaksi tersimpan dengan status `PENDING` menunggu persetujuan Bendahara.
- [ ] **Given** saya memasukkan periode bulan di luar rentang 1–12, **when** saya submit, **then** sistem menolak dengan pesan validasi.
- [ ] **Given** kombinasi No. KK + jenis iuran + periode yang saya masukkan sudah pernah tercatat sebelumnya dan belum berstatus `REJECTED`, **when** saya submit, **then** sistem menolak dengan `409 Conflict` dan pesan bahwa iuran periode tersebut sudah tercatat.

---

#### US-KEU-02
**Sebagai** Bendahara RW, **saya ingin** meninjau dan menyetujui/menolak setiap transaksi iuran yang dicatat Ketua RT, **sehingga** ada kontrol ganda (dual control) terhadap keakuratan data keuangan sebelum masuk ke rekapitulasi resmi.

**Kriteria Penerimaan:**
- [ ] **Given** ada transaksi berstatus `PENDING`, **when** saya menyetujui, **then** status berubah menjadi `APPROVED` beserta timestamp dan identitas penyetuju tercatat.
- [ ] **Given** saya menolak transaksi dengan alasan (mis. nominal tidak sesuai bukti), **when** saya submit penolakan, **then** status berubah menjadi `REJECTED` dan Ketua RT terkait dapat melihat alasan penolakan untuk mencatat ulang.

---

#### US-KEU-03
**Sebagai** Bendahara RW/Ketua RW, **saya ingin** melihat rekapitulasi keuangan iuran per periode dan per wilayah RT, **sehingga** saya dapat menyusun laporan pertanggungjawaban keuangan RW secara berkala.

**Kriteria Penerimaan:**
- [ ] **Given** saya memilih periode bulan dan tahun tertentu, **when** saya membuka rekapitulasi, **then** sistem menampilkan total dana diterima, jumlah transaksi, dan rincian per jenis iuran hanya dari transaksi berstatus `APPROVED`.
- [ ] **Given** belum ada transaksi `APPROVED` pada periode yang dipilih, **when** saya membuka rekapitulasi, **then** sistem menampilkan nilai nol/kosong, bukan error.

---

### 1.6 Modul Informasi Publik

#### US-INFO-01
**Sebagai** Sekretaris RW, **saya ingin** mempublikasikan pengumuman, berita, atau agenda kegiatan RW, **sehingga** seluruh warga mendapat informasi terkini tanpa perlu papan pengumuman fisik.

**Kriteria Penerimaan:**
- [ ] **Given** saya mengisi judul, konten, kategori, dan status `PUBLISHED`, **when** saya submit, **then** konten langsung tampil di portal publik tanpa perlu approval tambahan.
- [ ] **Given** saya menyimpan konten dengan status `DRAFT`, **when** saya submit, **then** konten tersimpan tapi **tidak** muncul di portal publik sampai statusnya diubah menjadi `PUBLISHED`.

---

#### US-INFO-02
**Sebagai** warga, **saya ingin** melihat pengumuman dan agenda kegiatan RW tanpa perlu login, **sehingga** saya bisa mengakses informasi lingkungan dengan cepat dan mudah.

**Kriteria Penerimaan:**
- [ ] **Given** saya membuka portal publik tanpa login, **when** saya membuka halaman informasi, **then** saya melihat daftar konten `PUBLISHED` terurut dari yang terbaru.
- [ ] **Given** saya memfilter kategori "AGENDA", **when** saya menerapkan filter, **then** hanya konten kategori tersebut yang tampil.

---

### 1.7 Modul Dashboard & Monitoring

#### US-DASH-01
**Sebagai** pengurus RW (peran apa pun selain Warga), **saya ingin** melihat ringkasan statistik operasional yang relevan dengan peran saya di satu layar, **sehingga** saya tidak perlu membuka banyak menu untuk memahami kondisi terkini RW.

**Kriteria Penerimaan:**
- [ ] **Given** saya login sebagai Ketua RT, **when** saya membuka dashboard, **then** statistik yang ditampilkan hanya mencakup data wilayah RT saya.
- [ ] **Given** saya login sebagai Ketua RW/Super Admin, **when** saya membuka dashboard, **then** statistik yang ditampilkan mencakup seluruh wilayah RW.

---

## 2. Alur Kerja Pengguna (User Workflows / Journey)

### 2.1 Alur Registrasi Akun Pengurus & Login Pertama Kali

> Warga **tidak** memerlukan akun terdaftar untuk menggunakan layanan dasar (submit surat/laporan bersifat self-service). Alur berikut khusus untuk akun pengurus (RT/RW/Bendahara/Sekretaris/Super Admin).

1. Super Admin login ke sistem menggunakan akun awal yang sudah di-*seed* saat instalasi (initial admin account).
2. Super Admin membuka menu Manajemen Pengguna → **Tambah Pengguna Baru**.
3. Super Admin mengisi data pengurus (nama, email, peran, wilayah RT jika relevan) dan menetapkan password sementara.
4. Sistem membuat akun berstatus `ACTIVE` dan (opsional, fase lanjutan) mengirim email notifikasi ke pengurus baru.
5. Pengurus baru login menggunakan email dan password sementara.
6. *(Rekomendasi peningkatan, tidak wajib di versi awal)* Sistem meminta pengurus mengganti password saat login pertama kali.
7. Pengurus diarahkan ke dashboard sesuai perannya.

---

### 2.2 Alur Utama: Pengajuan Surat Pengantar (End-to-End)

Alur ini merepresentasikan proses bisnis inti sistem, dari sisi warga hingga surat resmi diterbitkan.

```
[Warga] → [Sistem] → [Ketua RT] → [Sistem] → [Sekretaris/Ketua RW] → [Sistem] → [Warga]
```

**Langkah demi langkah:**

1. **Warga** membuka Portal Warga (tanpa login), memilih menu "Ajukan Surat".
2. **Warga** mengisi NIK, memilih jenis surat (Surat Pengantar/Surat Keterangan Domisili), dan menuliskan keperluan.
3. **Sistem** memvalidasi NIK terhadap data kependudukan; jika valid, sistem membuat record `pengajuan_surats` dengan status `SUBMITTED` dan menerbitkan `tracking_code` unik.
4. **Sistem** menampilkan `tracking_code` kepada warga dan mengingatkan untuk menyimpannya.
5. **Ketua RT** (wilayah sesuai domisili warga) login ke dashboard, melihat pengajuan baru pada daftar "Menunggu Verifikasi RT".
6. **Ketua RT** meninjau kelengkapan data, lalu memilih **Setujui** atau **Tolak**.
   - Jika **Setujui** → status berubah `RT_REVIEW`, pengajuan masuk ke antrean Sekretaris/Ketua RW.
   - Jika **Tolak** → status berubah `REJECTED`, disertai alasan; alur berhenti di sini.
7. **Sekretaris RW/Ketua RW** login, melihat pengajuan berstatus `RT_REVIEW` pada daftar "Menunggu Persetujuan RW".
8. **Sekretaris/Ketua RW** meninjau dan memilih **Setujui** atau **Tolak**.
   - Jika **Setujui** → status berubah `COMPLETED`, sistem otomatis men-generate `nomor_surat` resmi.
   - Jika **Tolak** → status berubah `REJECTED`, disertai alasan.
9. **Sistem** mencatat seluruh perubahan status di `audit_logs` dengan timestamp.
10. **Warga** mengecek status kapan saja melalui halaman "Lacak Pengajuan" menggunakan `tracking_code` — melihat status terkini beserta riwayat lengkap.
11. Jika status `COMPLETED`, warga dapat mengambil/mencetak surat sesuai mekanisme yang ditetapkan pengurus (proses fisik pengambilan surat berada di luar cakupan sistem versi awal).

---

### 2.3 Alur Utama: Laporan & Aspirasi Warga dengan Klasifikasi AI

```
[Warga] → [Sistem: simpan + antrean job] → [AI Classification Service] → [Sistem: update status] → [Pengurus RW] → [Warga]
```

**Langkah demi langkah:**

1. **Warga** membuka Portal Warga, memilih menu "Sampaikan Laporan/Aspirasi".
2. **Warga** mengisi judul, deskripsi keluhan, dan lokasi kejadian (NIK opsional bisa diminta untuk verifikasi, tergantung kebijakan privasi yang ditetapkan).
3. **Sistem** menyimpan laporan dengan status `SUBMITTED`, menerbitkan `ticket_number`, dan **mendorong job klasifikasi ke queue** (proses asinkron, tidak menahan response ke warga).
4. **Sistem** menampilkan `ticket_number` ke warga sebagai konfirmasi laporan diterima.
5. **Background job** memanggil layanan AI Classification dengan teks keluhan sebagai input.
6. **Layanan AI** mengembalikan kategori (mis. "Infrastruktur") dan skor prioritas.
7. **Sistem** memperbarui record laporan: status → `CLASSIFIED`, `kategori_ai` dan `skor_prioritas_ai` terisi.
8. **Pengurus RW** (RT/Sekretaris/Ketua RW sesuai wilayah) melihat laporan baru di dashboard, dapat mengurutkan berdasarkan skor prioritas.
9. **Pengurus RW** melakukan disposisi/tindak lanjut, mengubah status menjadi `IN_PROGRESS`.
10. Setelah penanganan selesai di lapangan, **Pengurus RW** mengubah status menjadi `RESOLVED` disertai catatan penyelesaian.
11. **Pengurus RW** (opsional) menutup laporan secara final dengan status `CLOSED` setelah periode konfirmasi tertentu.
12. **Warga** dapat memantau seluruh transisi status ini kapan saja melalui halaman "Lacak Laporan" menggunakan `ticket_number`.

---

### 2.4 Alur Utama: Pencatatan & Verifikasi Iuran Warga

```
[Ketua RT] → [Sistem: status PENDING] → [Bendahara RW] → [Sistem: status APPROVED/REJECTED] → [Rekapitulasi]
```

**Langkah demi langkah:**

1. **Ketua RT** menerima pembayaran iuran dari warga secara langsung (proses fisik di luar sistem).
2. **Ketua RT** login, membuka menu "Catat Iuran", memilih KK pembayar, jenis iuran, nominal, dan periode.
3. **Sistem** menyimpan transaksi dengan status `PENDING`.
4. **Bendahara RW** login, membuka daftar "Transaksi Menunggu Persetujuan".
5. **Bendahara RW** meninjau kesesuaian data (opsional: cocokkan dengan bukti fisik/transfer), lalu memilih **Setujui** atau **Tolak**.
   - Jika **Setujui** → status `APPROVED`, transaksi masuk hitungan rekapitulasi resmi.
   - Jika **Tolak** → status `REJECTED` disertai alasan; Ketua RT dapat mencatat ulang dengan data yang benar.
6. **Bendahara RW/Ketua RW** membuka menu "Rekapitulasi Keuangan", memilih periode, dan melihat total dana diterima serta rincian per jenis iuran (hanya transaksi `APPROVED` yang dihitung).
7. **Sistem** mencatat seluruh transaksi dan perubahan status di `audit_logs` untuk keperluan audit di kemudian hari.

---

## 3. Penanganan Kasus Khusus (Edge Cases & Error Handling)

### 3.1 Autentikasi & Akses

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Percobaan login gagal berulang kali dalam waktu singkat | Sistem memblokir sementara (rate limiting) dengan pesan waktu tunggu yang jelas — **tidak** memblokir permanen tanpa jalur pemulihan. |
| Token akses kedaluwarsa saat pengguna sedang mengisi form panjang | Sistem menampilkan notifikasi sesi berakhir sebelum submit, mengarahkan ke login ulang tanpa kehilangan seluruh input (jika memungkinkan, simpan draft di local state sementara). |
| Pengguna dengan peran tertentu mencoba mengakses data di luar wilayah kewenangannya (mis. Ketua RT RT-01 membuka data RT-02) | Sistem menolak dengan `403 Forbidden` di **level backend**, bukan hanya menyembunyikan menu di UI. |
| Akun dinonaktifkan Super Admin saat pengguna sedang login aktif | Request berikutnya dari sesi tersebut ditolak `401`/`403`; pengguna dipaksa logout pada request selanjutnya. |

### 3.2 Administrasi Surat

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Warga mengajukan surat dengan NIK yang belum terdaftar di data kependudukan RW | Sistem menolak submit dengan pesan validasi jelas, mengarahkan warga menghubungi Ketua RT untuk pendataan terlebih dahulu. |
| Warga mengajukan lebih dari satu permohonan surat jenis sama secara bersamaan (duplikasi) | Sistem tetap menerima (tidak ada larangan bisnis eksplisit dari skripsi), namun pengurus dapat melihat riwayat pengajuan warga tersebut untuk menghindari duplikasi pemrosesan; **rekomendasi pengembangan lanjutan**: sistem memberi peringatan soft-warning jika ada pengajuan jenis sama yang masih aktif. |
| Ketua RT mencoba memverifikasi pengajuan yang sudah `COMPLETED`/`REJECTED` | Sistem menolak dengan `409 Conflict` — transisi status hanya berlaku satu arah maju, tidak bisa mundur atau diulang. |
| Warga kehilangan kode tracking | Karena tracking bersifat anonim/publik by design, sistem versi awal tidak memiliki mekanisme "lupa kode tracking" otomatis — warga disarankan menghubungi Ketua RT untuk verifikasi manual (dicatat sebagai keterbatasan, potensi perbaikan: kaitkan opsional dengan nomor HP terdaftar). |

### 3.3 Laporan & Aspirasi (termasuk Klasifikasi AI)

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Layanan AI Classification tidak tersedia (down/timeout) saat job dijalankan | Job di-retry otomatis sesuai kebijakan queue (mis. exponential backoff); laporan tetap `SUBMITTED` dan tidak hilang. |
| Layanan AI gagal klasifikasi setelah batas retry maksimum | Laporan ditandai untuk **klasifikasi manual** oleh pengurus (fallback), status tidak boleh macet tanpa penanganan. |
| Warga mengisi teks keluhan berisi konten kasar/tidak relevan | Sistem tetap menerima dan menyimpan laporan (sanitasi hanya mencegah injection/XSS, bukan sensor konten); penyaringan konten tidak pantas menjadi keputusan editorial pengurus saat meninjau, bukan validasi otomatis di versi awal. |
| Warga mengirim laporan dengan lokasi kejadian di luar wilayah RW 047 | Sistem tetap menerima laporan (field lokasi bersifat teks bebas); pengurus dapat menolak/menutup laporan tersebut sebagai `CLOSED` dengan catatan bahwa laporan di luar cakupan wilayah. |
| Pengurus mencoba mengubah status laporan yang sudah `CLOSED` | Sistem menolak perubahan status dengan `400 Bad Request` — status `CLOSED` bersifat final. |

### 3.4 Keuangan (Iuran)

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Ketua RT mencatat nominal iuran yang tidak sesuai dengan `default_amount` pada `iuran_types` | Sistem tetap menerima (nominal aktual bisa berbeda, mis. pembulatan atau kelebihan bayar); tidak ada validasi ketat kesesuaian nominal di versi awal, namun perbedaan signifikan dapat menjadi pertimbangan Bendahara saat approval. |
| Bendahara RW menolak transaksi yang sudah lama tercatat (mis. iuran bulan lalu) | Sistem tetap mengizinkan penolakan dengan catatan alasan; transaksi berstatus `REJECTED` tidak masuk hitungan rekapitulasi resmi periode manapun. |
| Ketua RT mencatat transaksi untuk kombinasi KK + jenis iuran + periode yang sama dengan transaksi yang sudah tercatat sebelumnya (potensi duplikasi) | **(Keputusan rebuild v1)** Sistem **menolak** pencatatan duplikat secara otomatis di level database (UNIQUE constraint pada `no_kk` + `iuran_type_id` + `periode_bulan` + `periode_tahun`, lihat DATABASE_SCHEMA.md §3.10) — API mengembalikan `409 Conflict` dengan pesan bahwa iuran periode tersebut sudah tercatat. Transaksi berstatus `REJECTED` dikecualikan dari constraint ini, sehingga Ketua RT tetap dapat mencatat ulang setoran yang sebelumnya ditolak Bendahara dengan data yang benar. |
| Bendahara RW membuka rekapitulasi untuk periode yang belum memiliki transaksi apa pun | Sistem menampilkan hasil kosong/nol dengan jelas, **bukan** error `404`/`500`. |

### 3.5 Data Kependudukan & Privasi

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Pengguna tanpa hak akses mencoba melihat data NIK/No. KK dalam bentuk unmasked | Sistem menolak dengan `403 Forbidden`; percobaan akses tercatat di `audit_logs` terlepas dari berhasil/gagal. |
| Ketua RT memasukkan NIK dengan format tidak valid (bukan 16 digit angka) | Sistem menolak submit dengan pesan validasi `422` spesifik pada field NIK. |
| Data warga berubah status menjadi `PINDAH`/`MENINGGAL` | Data **tidak dihapus** (soft-delete/status update saja) — riwayat surat, laporan, dan iuran warga tersebut tetap dapat ditelusuri untuk keperluan audit historis. |

### 3.6 Ketersediaan & Performa Sistem

| Skenario | Respons Sistem yang Diharapkan |
|---|---|
| Server/database mengalami downtime saat warga sedang submit form publik | Frontend menampilkan pesan error ramah pengguna dengan saran mencoba beberapa saat lagi — **tidak** menampilkan stack trace/pesan error teknis mentah ke pengguna publik. |
| Volume laporan/pengajuan surat melonjak signifikan (mis. musim pengurusan dokumen tahunan) | Job queue klasifikasi AI dan proses verifikasi tetap berjalan asinkron tanpa membebani response time endpoint publik (selaras dengan NFR-02 pada PRD). |

---

## 4. Ringkasan Pemetaan User Story ke Modul & Peran

| Kode Story | Modul | Peran Utama |
|---|---|---|
| US-AUTH-01 s/d US-AUTH-03 | Autentikasi & Manajemen Pengguna | Semua Peran, Super Admin |
| US-KEP-01 s/d US-KEP-03 | Kependudukan | Ketua RT, Sekretaris RW, Ketua RW |
| US-SRT-01 s/d US-SRT-04 | Administrasi Surat | Warga, Ketua RT, Sekretaris/Ketua RW |
| US-LAP-01 s/d US-LAP-04 | Laporan & Aspirasi | Warga, Sistem (AI), Pengurus RW |
| US-KEU-01 s/d US-KEU-03 | Keuangan RW | Ketua RT, Bendahara RW, Ketua RW |
| US-INFO-01, US-INFO-02 | Informasi Publik | Sekretaris RW, Warga |
| US-DASH-01 | Dashboard & Monitoring | Seluruh Peran Pengurus |

> Dokumen ini dirancang agar setiap `US-xxx` dapat langsung ditautkan sebagai *ticket*/*issue* pada backlog sprint, dengan Acceptance Criteria yang sudah dalam format checklist siap divalidasi QA.
