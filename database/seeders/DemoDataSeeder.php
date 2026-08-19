<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\JenisSurat;
use App\Enums\KategoriInformasi;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusInformasi;
use App\Enums\StatusKasKeluar;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuanSurat;
use App\Enums\VerificationStatus;
use App\Models\CatatanIuran;
use App\Models\InformasiPublik;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\LaporanAspirasi;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Warga;
use App\Support\Security\DataEncryptionService;
use Illuminate\Database\Seeder;

/**
 * Seeder data operasional dummy untuk Rehearsal Presentasi Demo (Local Environment Only).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@rw047.id')->first();
        $sekretaris = User::where('email', 'sekretaris.rw@rw047.id')->first();
        $bendahara = User::where('email', 'bendahara.rw@rw047.id')->first();
        $ketuaRt01 = User::where('email', 'ketua.rt01@rw047.id')->first();
        $ketuaRt02 = User::where('email', 'ketua.rt02@rw047.id')->first();

        // 1. Kartu Keluarga
        $noKk01 = '3216010101230001';
        $noKk02 = '3216010101230002';

        $kk01 = KartuKeluarga::where('no_kk_hash', DataEncryptionService::deterministicHash($noKk01))->first();
        if (! $kk01) {
            $kk01 = KartuKeluarga::create([
                'no_kk' => $noKk01,
                'rt_code' => '001',
                'alamat_lengkap' => 'Jl. Mawar Indah Blok A1 No. 10 RT 001',
                'status_kepemilikan_rumah' => 'Milik Sendiri',
            ]);
        }

        $kk02 = KartuKeluarga::where('no_kk_hash', DataEncryptionService::deterministicHash($noKk02))->first();
        if (! $kk02) {
            $kk02 = KartuKeluarga::create([
                'no_kk' => $noKk02,
                'rt_code' => '002',
                'alamat_lengkap' => 'Jl. Melati Raya Blok B2 No. 05 RT 002',
                'status_kepemilikan_rumah' => 'Sewa/Kontrak',
            ]);
        }

        // 2. Warga
        $nik01 = '3216011204850001';
        $nik02 = '3216015508880002';
        $nik03 = '3216011909930003';

        $warga01 = Warga::where('nik_hash', DataEncryptionService::deterministicHash($nik01))->first();
        if (! $warga01) {
            $warga01 = Warga::create([
                'kartu_keluarga_id' => $kk01->id,
                'nik' => $nik01,
                'no_kk' => $noKk01,
                'nama_lengkap' => 'Budi Hendrawan',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1985-04-12',
                'status_hubungan_keluarga' => 'Kepala Keluarga',
                'status_warga' => 'TETAP',
                'verification_status' => VerificationStatus::TERVERIFIKASI->value,
            ]);
        }

        $warga02 = Warga::where('nik_hash', DataEncryptionService::deterministicHash($nik02))->first();
        if (! $warga02) {
            $warga02 = Warga::create([
                'kartu_keluarga_id' => $kk01->id,
                'nik' => $nik02,
                'no_kk' => $noKk01,
                'nama_lengkap' => 'Dewi Lestari',
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1988-08-15',
                'status_hubungan_keluarga' => 'Istri',
                'status_warga' => 'TETAP',
                'verification_status' => VerificationStatus::TERVERIFIKASI->value,
            ]);
        }

        $warga03 = Warga::where('nik_hash', DataEncryptionService::deterministicHash($nik03))->first();
        if (! $warga03) {
            $warga03 = Warga::create([
                'kartu_keluarga_id' => $kk02->id,
                'nik' => $nik03,
                'no_kk' => $noKk02,
                'nama_lengkap' => 'Rian Pratama',
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1993-09-19',
                'status_hubungan_keluarga' => 'Kepala Keluarga',
                'status_warga' => 'KONTRAK',
                'verification_status' => VerificationStatus::MENUNGGU_VERIFIKASI->value,
            ]);
        }

        // 3. Persuratan
        PengajuanSurat::firstOrCreate(
            ['tracking_code' => 'SRT-20260819-0001'],
            [
                'warga_id' => $warga01->id,
                'nomor_surat' => null,
                'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
                'keperluan' => 'Permohonan Pembuatan KTP Baru',
                'current_status' => StatusPengajuanSurat::SUBMITTED->value,
                'tanggal_pengajuan' => now()->subHours(3),
            ]
        );

        PengajuanSurat::firstOrCreate(
            ['tracking_code' => 'SRT-20260819-0002'],
            [
                'warga_id' => $warga02->id,
                'nomor_surat' => null,
                'jenis_surat' => JenisSurat::SURAT_KETERANGAN_DOMISILI->value,
                'keperluan' => 'Pengurusan Berkas Beasiswa Anak',
                'current_status' => StatusPengajuanSurat::RT_REVIEW->value,
                'tanggal_pengajuan' => now()->subDay(),
            ]
        );

        PengajuanSurat::firstOrCreate(
            ['tracking_code' => 'SRT-20260819-0003'],
            [
                'warga_id' => $warga01->id,
                'nomor_surat' => '47/SP/08/2026',
                'jenis_surat' => JenisSurat::SURAT_PENGANTAR->value,
                'keperluan' => 'Pengantar SKCK Kepolisian',
                'current_status' => StatusPengajuanSurat::COMPLETED->value,
                'tanggal_pengajuan' => now()->subDays(2),
                'tanggal_selesai' => now()->subDay(),
            ]
        );

        // 4. Keuangan
        $iuranType = IuranType::first();
        if ($iuranType) {
            CatatanIuran::firstOrCreate(
                [
                    'kartu_keluarga_id' => $kk01->id,
                    'iuran_type_id' => $iuranType->id,
                    'periode_bulan' => (int) now()->format('n'),
                    'periode_tahun' => (int) now()->format('Y'),
                ],
                [
                    'nominal' => 100000.00,
                    'status' => StatusCatatanIuran::APPROVED->value,
                    'recorded_by_user_id' => $ketuaRt01?->id ?? 1,
                    'approved_by_user_id' => $bendahara?->id ?? 1,
                    'approved_at' => now()->subDays(3),
                ]
            );

            CatatanIuran::firstOrCreate(
                [
                    'kartu_keluarga_id' => $kk02->id,
                    'iuran_type_id' => $iuranType->id,
                    'periode_bulan' => (int) now()->format('n'),
                    'periode_tahun' => (int) now()->format('Y'),
                ],
                [
                    'nominal' => 100000.00,
                    'status' => StatusCatatanIuran::PENDING->value,
                    'recorded_by_user_id' => $ketuaRt02?->id ?? 1,
                ]
            );
        }

        KasKeluar::firstOrCreate(
            ['keterangan' => 'Pembayaran jasa kebersihan lingkungan RW 047'],
            [
                'kategori' => 'OPERASIONAL',
                'nominal' => 450000.00,
                'tanggal_pengeluaran' => now()->format('Y-m-d'),
                'status' => StatusKasKeluar::APPROVED->value,
                'recorded_by_user_id' => $bendahara?->id ?? 1,
                'approved_by_user_id' => $admin?->id ?? 1,
                'approved_at' => now()->subDay(),
            ]
        );

        KasKeluar::firstOrCreate(
            ['keterangan' => 'Penggantian 3 titik lampu LED jalan utama'],
            [
                'kategori' => 'PEMELIHARAAN',
                'nominal' => 250000.00,
                'tanggal_pengeluaran' => now()->format('Y-m-d'),
                'status' => StatusKasKeluar::PENDING->value,
                'recorded_by_user_id' => $bendahara?->id ?? 1,
            ]
        );

        // 5. Informasi Publik
        InformasiPublik::firstOrCreate(
            ['judul' => 'Kerja Bakti Massal & Fogging RW 047'],
            [
                'kategori' => KategoriInformasi::PENGUMUMAN->value,
                'konten' => 'Dihimbau kepada seluruh warga RW 047 untuk berpartisipasi dalam kegiatan kerja bakti lingkungan dan pencegahan DBD.',
                'status' => StatusInformasi::PUBLISHED->value,
                'tanggal_publikasi' => now()->subDays(2),
                'published_by_user_id' => $sekretaris?->id ?? 1,
            ]
        );

        InformasiPublik::firstOrCreate(
            ['judul' => 'Jadwal Posyandu Balita & Lansia Bulan Agustus 2026'],
            [
                'kategori' => KategoriInformasi::AGENDA->value,
                'konten' => 'Pelayanan imunisasi balita dan cek tensi/gula darah gratis untuk lansia mulai pukul 08.00 WIB di Balai Pertemuan RW.',
                'tanggal_agenda' => now()->addDays(5),
                'status' => StatusInformasi::PUBLISHED->value,
                'tanggal_publikasi' => now()->subDay(),
                'published_by_user_id' => $sekretaris?->id ?? 1,
            ]
        );

        // 6. Laporan & Aspirasi
        LaporanAspirasi::firstOrCreate(
            ['ticket_number' => 'LPR-20260819-00001'],
            [
                'warga_id' => $warga01->id,
                'judul_laporan' => 'Saluran Air Tersumbat di Dekat Pos RT 001',
                'teks_keluhan' => 'Terdapat tumpukan sampah ranting pohon yang menyumbat aliran selokan saat hujan.',
                'lokasi_kejadian' => 'Jl. Mawar Depan Pos RT 001',
                'current_status' => StatusLaporan::SUBMITTED->value,
                'submitted_at' => now()->subHours(5),
            ]
        );

        LaporanAspirasi::firstOrCreate(
            ['ticket_number' => 'LPR-20260819-00002'],
            [
                'warga_id' => $warga03->id,
                'judul_laporan' => 'Usulan Pemasangan Cermin Tikungan',
                'teks_keluhan' => 'Mohon dipasang cermin cembung di pertigaan blok B karena sering terjadi blind spot kendaraan.',
                'lokasi_kejadian' => 'Pertigaan Blok B RT 002',
                'current_status' => StatusLaporan::IN_PROGRESS->value,
                'submitted_at' => now()->subDays(1),
            ]
        );
    }
}
