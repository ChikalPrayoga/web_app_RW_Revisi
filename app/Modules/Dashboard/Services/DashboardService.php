<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuanSurat;
use App\Enums\VerificationStatus;
use App\Models\CatatanIuran;
use App\Models\InformasiPublik;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\LaporanAspirasi;
use App\Models\PengajuanSurat;
use App\Models\User;
use App\Models\Warga;

/**
 * Service Layer untuk Modul Dashboard (Read-Only Aggregation Layer).
 *
 * Mengagregasikan data lintas modul (Kependudukan, Persuratan, Keuangan, Laporan, Informasi Publik)
 * secara aman, read-only, role-aware, dan area-scoped untuk Ketua RT.
 *
 * @see docs/API_SPECIFICATION.md §3.8
 * @see docs/USER_STORIES.md §1.7
 * @see docs/UI_UX_SPECIFICATION.md §2.2a
 */
class DashboardService
{
    /**
     * Mengambil ringkasan data statistik dashboard sesuai peran pengguna (API contract).
     * Sesuai API_SPECIFICATION.md §3.8.1.
     *
     * @return array<string, mixed>
     */
    public function getSummary(User $user): array
    {
        $isKetuaRt = $user->hasRole(RoleName::KETUA_RT->value);
        $userRt = $user->rt_code;
        $currentMonth = (int) now()->format('n');
        $currentYear = (int) now()->format('Y');

        // 1. Total Warga (scoped jika KETUA_RT)
        $wargaQuery = Warga::query();
        if ($isKetuaRt && $userRt) {
            $wargaQuery->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt));
        }
        $totalWarga = $wargaQuery->count();

        // 2. Total Kartu Keluarga (scoped jika KETUA_RT)
        $kkQuery = KartuKeluarga::query();
        if ($isKetuaRt && $userRt) {
            $kkQuery->where('rt_code', $userRt);
        }
        $totalKk = $kkQuery->count();

        // 3. Surat Menunggu Verifikasi
        $suratMenungguVerifikasi = $this->countSuratMenungguVerifikasi($user);

        // 4. Laporan Aktif & Distribusi Status Laporan (Kanal bersama RW)
        $laporanSubmitted = LaporanAspirasi::where('current_status', StatusLaporan::SUBMITTED->value)->count();
        $laporanInProgress = LaporanAspirasi::where('current_status', StatusLaporan::IN_PROGRESS->value)->count();
        $laporanResolved = LaporanAspirasi::where('current_status', StatusLaporan::RESOLVED->value)->count();
        $laporanAktif = $laporanSubmitted + $laporanInProgress;

        // 5. Total Iuran Bulan Ini (hanya APPROVED, scoped jika KETUA_RT)
        $iuranQuery = CatatanIuran::query()
            ->where('status', StatusCatatanIuran::APPROVED->value)
            ->where('periode_bulan', $currentMonth)
            ->where('periode_tahun', $currentYear);

        if ($isKetuaRt && $userRt) {
            $iuranQuery->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt));
        }
        $totalIuranBulanIni = (float) $iuranQuery->sum('nominal');

        // 6. Kepatuhan Iuran Persen (KK sudah bayar APPROVED / Total KK)
        $kepatuhanIuranPersen = 0.0;
        if ($totalKk > 0) {
            $kkSudahBayarQuery = CatatanIuran::query()
                ->where('status', StatusCatatanIuran::APPROVED->value)
                ->where('periode_bulan', $currentMonth)
                ->where('periode_tahun', $currentYear);

            if ($isKetuaRt && $userRt) {
                $kkSudahBayarQuery->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt));
            }

            $totalKkSudahBayar = $kkSudahBayarQuery->distinct('kartu_keluarga_id')->count('kartu_keluarga_id');
            $kepatuhanIuranPersen = round(($totalKkSudahBayar / $totalKk) * 100, 1);
        }

        return [
            'total_warga' => $totalWarga,
            'total_kk' => $totalKk,
            'surat_menunggu_verifikasi' => $suratMenungguVerifikasi,
            'laporan_aktif' => $laporanAktif,
            'laporan_berdasarkan_status' => [
                'SUBMITTED' => $laporanSubmitted,
                'IN_PROGRESS' => $laporanInProgress,
                'RESOLVED' => $laporanResolved,
            ],
            'total_iuran_bulan_ini' => $totalIuranBulanIni,
            'kepatuhan_iuran_persen' => $kepatuhanIuranPersen,
        ];
    }

    /**
     * Mengambil dataset lengkap untuk tampilan Web Blade Dashboard pengurus.
     * Mencakup metrik ringkasan, antrean "Butuh Tindakan Anda", distribusi status, dan rincian keuangan.
     *
     * @return array<string, mixed>
     */
    public function getWebDashboardData(User $user): array
    {
        $summary = $this->getSummary($user);
        $isKetuaRt = $user->hasRole(RoleName::KETUA_RT->value);
        $userRt = $user->rt_code;
        $roleName = $user->role?->name ?? 'WARGA';

        // Metrik tambahan kependudukan
        $wargaMenungguQuery = Warga::where('verification_status', VerificationStatus::MENUNGGU_VERIFIKASI->value);
        if ($isKetuaRt && $userRt) {
            $wargaMenungguQuery->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt));
        }
        $wargaMenungguVerifikasi = $wargaMenungguQuery->count();

        // Metrik tambahan keuangan (RW level)
        $iuranMenungguApproval = CatatanIuran::where('status', StatusCatatanIuran::PENDING->value)->count();
        $kasKeluarMenungguApproval = KasKeluar::where('status', StatusKasKeluar::PENDING->value)->count();

        $currentMonth = (int) now()->format('n');
        $currentYear = (int) now()->format('Y');

        $kasKeluarBulanIni = (float) KasKeluar::where('status', StatusKasKeluar::APPROVED->value)
            ->whereMonth('tanggal_pengeluaran', $currentMonth)
            ->whereYear('tanggal_pengeluaran', $currentYear)
            ->sum('nominal');

        $totalPemasukanSepanjangMasa = (float) CatatanIuran::where('status', StatusCatatanIuran::APPROVED->value)->sum('nominal');
        $totalPengeluaranSepanjangMasa = (float) KasKeluar::where('status', StatusKasKeluar::APPROVED->value)->sum('nominal');
        $saldoKasRw = $totalPemasukanSepanjangMasa - $totalPengeluaranSepanjangMasa;

        // Metrik informasi publik & pengguna
        $totalInformasiPublik = InformasiPublik::count();
        $totalUsers = User::count();

        // Distribusi status Laporan & Aspirasi lengkap
        $laporanClosed = LaporanAspirasi::where('current_status', StatusLaporan::CLOSED->value)->count();
        $laporanDistribution = [
            'SUBMITTED' => $summary['laporan_berdasarkan_status']['SUBMITTED'],
            'IN_PROGRESS' => $summary['laporan_berdasarkan_status']['IN_PROGRESS'],
            'RESOLVED' => $summary['laporan_berdasarkan_status']['RESOLVED'],
            'CLOSED' => $laporanClosed,
        ];

        // Item "Butuh Tindakan Anda" (Action Center)
        $actionItems = $this->buildActionItems($user);

        return [
            'summary' => $summary,
            'role_name' => $roleName,
            'role_display_name' => $user->role?->display_name ?? $roleName,
            'rt_code' => $userRt,
            'warga_menunggu_verifikasi' => $wargaMenungguVerifikasi,
            'iuran_menunggu_approval' => $iuranMenungguApproval,
            'kas_keluar_menunggu_approval' => $kasKeluarMenungguApproval,
            'kas_keluar_bulan_ini' => $kasKeluarBulanIni,
            'saldo_kas_rw' => $saldoKasRw,
            'total_informasi_publik' => $totalInformasiPublik,
            'total_users' => $totalUsers,
            'laporan_distribution' => $laporanDistribution,
            'action_items' => $actionItems,
        ];
    }

    /**
     * Menghitung jumlah surat yang sedang menunggu verifikasi sesuai peran pengguna.
     */
    private function countSuratMenungguVerifikasi(User $user): int
    {
        $isKetuaRt = $user->hasRole(RoleName::KETUA_RT->value);
        $userRt = $user->rt_code;

        if ($isKetuaRt) {
            // Ketua RT: hanya pengajuan SUBMITTED dari RT miliknya
            return PengajuanSurat::where('current_status', StatusPengajuanSurat::SUBMITTED->value)
                ->whereHas('warga.kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt))
                ->count();
        }

        if ($user->hasRole(RoleName::SEKRETARIS_RW->value)) {
            // Sekretaris RW: pengajuan yang telah disetujui RT (RT_REVIEW)
            return PengajuanSurat::where('current_status', StatusPengajuanSurat::RT_REVIEW->value)->count();
        }

        if ($user->hasRole(RoleName::KETUA_RW->value)) {
            // Ketua RW: pengajuan siap verifikasi final (RW_REVIEW) + RT_REVIEW
            return PengajuanSurat::whereIn('current_status', [
                StatusPengajuanSurat::RW_REVIEW->value,
                StatusPengajuanSurat::RT_REVIEW->value,
            ])->count();
        }

        // SUPER_ADMIN / BENDAHARA_RW / peran lain: total surat aktif yang belum selesai
        return PengajuanSurat::whereIn('current_status', [
            StatusPengajuanSurat::SUBMITTED->value,
            StatusPengajuanSurat::RT_REVIEW->value,
            StatusPengajuanSurat::RW_REVIEW->value,
        ])->count();
    }

    /**
     * Membangun daftar item "Butuh Tindakan Anda" yang diprioritaskan per peran.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildActionItems(User $user): array
    {
        $items = [];
        $isKetuaRt = $user->hasRole(RoleName::KETUA_RT->value);
        $userRt = $user->rt_code;

        // 1. Aksi KETUA_RT
        if ($isKetuaRt) {
            // Pengajuan Surat SUBMITTED di wilayah RT
            $suratPending = PengajuanSurat::with(['warga.kartuKeluarga'])
                ->where('current_status', StatusPengajuanSurat::SUBMITTED->value)
                ->whereHas('warga.kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt))
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($suratPending as $surat) {
                $suratKey = $surat->getKey();
                $items[] = [
                    'id' => 'surat-'.$suratKey,
                    'module' => 'persuratan',
                    'title' => 'Review Pengajuan Surat: '.$surat->jenis_surat->label(),
                    'subtitle' => 'Pemohon: '.($surat->warga?->nama_lengkap ?? 'Warga').' ('.$surat->tracking_code.') &bull; Keperluan: '.$surat->keperluan,
                    'badge_label' => 'Menunggu Review RT',
                    'badge_class' => 'bg-warning-light text-warning border-warning/30',
                    'action_url' => route('persuratan.show', $suratKey),
                    'action_label' => 'Tinjau Surat',
                    'created_at' => $surat->tanggal_pengajuan ?? $surat->created_at,
                    'priority' => 'high',
                ];
            }

            // Warga MENUNGGU_VERIFIKASI di wilayah RT
            $wargaPending = Warga::with('kartuKeluarga')
                ->where('verification_status', VerificationStatus::MENUNGGU_VERIFIKASI->value)
                ->whereHas('kartuKeluarga', fn ($q) => $q->where('rt_code', $userRt))
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($wargaPending as $warga) {
                $items[] = [
                    'id' => 'warga-'.$warga->id,
                    'module' => 'kependudukan',
                    'title' => 'Data Warga Baru Menunggu Verifikasi',
                    'subtitle' => $warga->nama_lengkap.' &bull; RT '.$userRt.' &bull; Terdaftar: '.$warga->created_at?->translatedFormat('d M Y'),
                    'badge_label' => 'Menunggu Verifikasi',
                    'badge_class' => 'bg-info-light text-info border-info/30',
                    'action_url' => route('kependudukan.warga.show', $warga->nik_hash),
                    'action_label' => 'Lihat Data',
                    'created_at' => $warga->created_at,
                    'priority' => 'medium',
                ];
            }
        }

        // 2. Aksi SEKRETARIS_RW
        if ($user->hasRole(RoleName::SEKRETARIS_RW->value)) {
            // Warga MENUNGGU_VERIFIKASI (Sekretaris berwenang verifikasi data warga)
            $wargaPending = Warga::with('kartuKeluarga')
                ->where('verification_status', VerificationStatus::MENUNGGU_VERIFIKASI->value)
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($wargaPending as $warga) {
                $items[] = [
                    'id' => 'warga-'.$warga->id,
                    'module' => 'kependudukan',
                    'title' => 'Verifikasi Data Warga Baru',
                    'subtitle' => $warga->nama_lengkap.' &bull; RT '.($warga->kartuKeluarga?->rt_code ?? '-'),
                    'badge_label' => 'Menunggu Verifikasi RW',
                    'badge_class' => 'bg-info-light text-info border-info/30',
                    'action_url' => route('kependudukan.warga.verify.form', $warga->nik_hash),
                    'action_label' => 'Verifikasi Warga',
                    'created_at' => $warga->created_at,
                    'priority' => 'high',
                ];
            }

            // Surat RT_REVIEW
            $suratPending = PengajuanSurat::with(['warga.kartuKeluarga'])
                ->where('current_status', StatusPengajuanSurat::RT_REVIEW->value)
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($suratPending as $surat) {
                $suratKey = $surat->getKey();
                $items[] = [
                    'id' => 'surat-'.$suratKey,
                    'module' => 'persuratan',
                    'title' => 'Verifikasi Surat Disetujui RT: '.$surat->jenis_surat->label(),
                    'subtitle' => 'Pemohon: '.($surat->warga?->nama_lengkap ?? 'Warga').' ('.$surat->tracking_code.') &bull; RT '.($surat->warga?->kartuKeluarga?->rt_code ?? '-'),
                    'badge_label' => 'Verifikasi RW',
                    'badge_class' => 'bg-primary-light text-primary border-primary/30',
                    'action_url' => route('persuratan.show', $suratKey),
                    'action_label' => 'Proses Surat',
                    'created_at' => $surat->tanggal_pengajuan ?? $surat->created_at,
                    'priority' => 'high',
                ];
            }

            // Laporan SUBMITTED baru masuk
            $laporanBaru = LaporanAspirasi::where('current_status', StatusLaporan::SUBMITTED->value)
                ->orderBy('submitted_at', 'asc')
                ->limit(3)
                ->get();

            foreach ($laporanBaru as $laporan) {
                $items[] = [
                    'id' => 'laporan-'.$laporan->id,
                    'module' => 'laporan',
                    'title' => 'Laporan Baru Masuk: '.$laporan->judul_laporan,
                    'subtitle' => 'Tiket: '.$laporan->ticket_number.' &bull; Lokasi: '.($laporan->lokasi_kejadian ?? 'RW 047'),
                    'badge_label' => 'Laporan Masuk',
                    'badge_class' => 'bg-warning-light text-warning border-warning/30',
                    'action_url' => route('laporan-aspirasi.show', $laporan->id),
                    'action_label' => 'Tindak Lanjuti',
                    'created_at' => $laporan->submitted_at ?? $laporan->created_at,
                    'priority' => 'medium',
                ];
            }
        }

        // 3. Aksi BENDAHARA_RW
        if ($user->hasRole(RoleName::BENDAHARA_RW->value)) {
            // Iuran PENDING menunggu approval
            $iuranPending = CatatanIuran::with(['kartuKeluarga', 'iuranType', 'recordedBy'])
                ->where('status', StatusCatatanIuran::PENDING->value)
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($iuranPending as $iuran) {
                $items[] = [
                    'id' => 'iuran-'.$iuran->id,
                    'module' => 'keuangan',
                    'title' => 'Persetujuan Iuran: '.($iuran->iuranType?->name ?? 'Iuran'),
                    'subtitle' => 'RT '.($iuran->kartuKeluarga?->rt_code ?? '-').' &bull; Periode: '.sprintf('%02d/%04d', $iuran->periode_bulan, $iuran->periode_tahun).' &bull; Rp '.number_format((float) $iuran->nominal, 0, ',', '.'),
                    'badge_label' => 'Menunggu Approval Bendahara',
                    'badge_class' => 'bg-warning-light text-warning border-warning/30',
                    'action_url' => route('keuangan.iuran.approval'),
                    'action_label' => 'Buka Antrean Approval',
                    'created_at' => $iuran->created_at,
                    'priority' => 'high',
                ];
            }
        }

        // 4. Aksi KETUA_RW
        if ($user->hasRole(RoleName::KETUA_RW->value)) {
            // Kas Keluar PENDING menunggu approval Ketua RW (Dual-Control)
            $kasPending = KasKeluar::with('recordedBy')
                ->where('status', StatusKasKeluar::PENDING->value)
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($kasPending as $kas) {
                $items[] = [
                    'id' => 'kas-'.$kas->id,
                    'module' => 'keuangan',
                    'title' => 'Persetujuan Pengeluaran Kas: '.($kas->kategori ?? 'Operasional'),
                    'subtitle' => ($kas->keterangan ?? 'Pengeluaran Kas').' &bull; Nominal: Rp '.number_format((float) $kas->nominal, 0, ',', '.').' &bull; Dicatat: '.($kas->recordedBy?->full_name ?? 'Bendahara'),
                    'badge_label' => 'Approval Kas Keluar',
                    'badge_class' => 'bg-danger-light text-danger border-danger/30',
                    'action_url' => route('keuangan.kas-keluar.approval'),
                    'action_label' => 'Tinjau Pengeluaran',
                    'created_at' => $kas->created_at,
                    'priority' => 'high',
                ];
            }

            // Surat RW_REVIEW / RT_REVIEW
            $suratRw = PengajuanSurat::with(['warga.kartuKeluarga'])
                ->whereIn('current_status', [
                    StatusPengajuanSurat::RW_REVIEW->value,
                    StatusPengajuanSurat::RT_REVIEW->value,
                ])
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

            foreach ($suratRw as $surat) {
                $suratKey = $surat->getKey();
                $statusLabel = $surat->current_status === StatusPengajuanSurat::RW_REVIEW ? 'Persetujuan Final RW' : 'Verifikasi RW';
                $items[] = [
                    'id' => 'surat-'.$suratKey,
                    'module' => 'persuratan',
                    'title' => 'Persetujuan Surat: '.$surat->jenis_surat->label(),
                    'subtitle' => 'Pemohon: '.($surat->warga?->nama_lengkap ?? 'Warga').' ('.$surat->tracking_code.') &bull; RT '.($surat->warga?->kartuKeluarga?->rt_code ?? '-'),
                    'badge_label' => $statusLabel,
                    'badge_class' => 'bg-primary-light text-primary border-primary/30',
                    'action_url' => route('persuratan.show', $suratKey),
                    'action_label' => 'Selesaikan Surat',
                    'created_at' => $surat->tanggal_pengajuan ?? $surat->created_at,
                    'priority' => 'high',
                ];
            }

            // Laporan SUBMITTED baru masuk
            $laporanBaru = LaporanAspirasi::where('current_status', StatusLaporan::SUBMITTED->value)
                ->orderBy('submitted_at', 'asc')
                ->limit(3)
                ->get();

            foreach ($laporanBaru as $laporan) {
                $items[] = [
                    'id' => 'laporan-'.$laporan->id,
                    'module' => 'laporan',
                    'title' => 'Laporan Warga Baru: '.$laporan->judul_laporan,
                    'subtitle' => 'Tiket: '.$laporan->ticket_number.' &bull; Lokasi: '.($laporan->lokasi_kejadian ?? 'RW 047'),
                    'badge_label' => 'Laporan Masuk',
                    'badge_class' => 'bg-warning-light text-warning border-warning/30',
                    'action_url' => route('laporan-aspirasi.show', $laporan->id),
                    'action_label' => 'Tinjau Laporan',
                    'created_at' => $laporan->submitted_at ?? $laporan->created_at,
                    'priority' => 'medium',
                ];
            }
        }

        // 5. Aksi SUPER_ADMIN (Monitoring Terpadu)
        if ($user->hasRole(RoleName::SUPER_ADMIN->value)) {
            // Verifikasi warga pending
            $wargaPendingCount = Warga::where('verification_status', VerificationStatus::MENUNGGU_VERIFIKASI->value)->count();
            if ($wargaPendingCount > 0) {
                $items[] = [
                    'id' => 'admin-warga-pending',
                    'module' => 'kependudukan',
                    'title' => 'Terdapat '.$wargaPendingCount.' Data Warga Menunggu Verifikasi',
                    'subtitle' => 'Verifikasi kependudukan dilakukan oleh Sekretaris RW.',
                    'badge_label' => 'Monitoring Kependudukan',
                    'badge_class' => 'bg-info-light text-info border-info/30',
                    'action_url' => route('kependudukan.warga.index'),
                    'action_label' => 'Buka Data Warga',
                    'created_at' => now(),
                    'priority' => 'medium',
                ];
            }

            // Surat pending
            $suratPendingCount = PengajuanSurat::whereIn('current_status', [
                StatusPengajuanSurat::SUBMITTED->value,
                StatusPengajuanSurat::RT_REVIEW->value,
                StatusPengajuanSurat::RW_REVIEW->value,
            ])->count();
            if ($suratPendingCount > 0) {
                $items[] = [
                    'id' => 'admin-surat-pending',
                    'module' => 'persuratan',
                    'title' => 'Terdapat '.$suratPendingCount.' Pengajuan Surat dalam Antrean',
                    'subtitle' => 'Pengajuan surat berjenjang sedang dalam proses verifikasi RT/RW.',
                    'badge_label' => 'Monitoring Surat',
                    'badge_class' => 'bg-primary-light text-primary border-primary/30',
                    'action_url' => route('persuratan.index'),
                    'action_label' => 'Buka Persuratan',
                    'created_at' => now(),
                    'priority' => 'medium',
                ];
            }

            // Iuran pending approval
            $iuranPendingCount = CatatanIuran::where('status', StatusCatatanIuran::PENDING->value)->count();
            if ($iuranPendingCount > 0) {
                $items[] = [
                    'id' => 'admin-iuran-pending',
                    'module' => 'keuangan',
                    'title' => 'Terdapat '.$iuranPendingCount.' Transaksi Iuran Menunggu Approval Bendahara',
                    'subtitle' => 'Persetujuan iuran dilakukan oleh Bendahara RW.',
                    'badge_label' => 'Monitoring Keuangan',
                    'badge_class' => 'bg-warning-light text-warning border-warning/30',
                    'action_url' => route('keuangan.iuran.index'),
                    'action_label' => 'Buka Iuran',
                    'created_at' => now(),
                    'priority' => 'low',
                ];
            }
        }

        return $items;
    }
}
