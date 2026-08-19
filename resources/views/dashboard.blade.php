@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb', 'Beranda')

@section('content')
@php
    $user = Auth::user();
    $roleName = $role_name ?? ($user?->role?->name ?? 'WARGA');
    $roleDisplayName = $role_display_name ?? ($user?->role?->display_name ?? 'Pengurus');
    $rtCode = $rt_code ?? $user?->rt_code;
@endphp

<div class="space-y-6">
    <!-- 1. Header Greeting & Context Section -->
    <div class="bg-surface p-6 rounded-md border border-border shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-sm bg-primary-light text-primary border border-primary/20 uppercase tracking-wider">
                    {{ $roleDisplayName }}
                </span>
                @if($rtCode)
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-sm bg-primary text-white">
                        Wilayah RT {{ $rtCode }}
                    </span>
                @endif
                <span class="text-xs text-text-secondary font-medium">
                    &bull; RW 047
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary leading-tight">
                Selamat datang, {{ $user?->full_name ?? 'Pengurus' }}
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                {{ now()->translatedFormat('l, d F Y') }} &bull; Sistem Informasi Layanan Warga RW 047
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-sm text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                Sesi Aktif
            </span>
        </div>
    </div>

    <!-- 2. Primary Stat Cards Grid (4 Kolom Responsif) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Card 1: Total Warga -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        @if($rtCode) Warga RT {{ $rtCode }} @else Total Warga RW @endif
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold font-display text-text-primary mt-2">
                        {{ number_format($summary['total_warga'] ?? 0) }}
                    </h2>
                </div>
                <div class="w-11 h-11 rounded-sm bg-primary-light text-primary flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between text-xs text-text-secondary">
                <span>
                    @if(($warga_menunggu_verifikasi ?? 0) > 0)
                        <strong class="text-warning font-semibold">{{ $warga_menunggu_verifikasi }}</strong> butuh verifikasi
                    @else
                        Semua data terverifikasi
                    @endif
                </span>
                <a href="{{ route('kependudukan.warga.index') }}" class="text-primary hover:underline font-medium">Lihat &rarr;</a>
            </div>
        </div>

        <!-- Card 2: Total KK -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        @if($rtCode) KK RT {{ $rtCode }} @else Total Kartu Keluarga @endif
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold font-display text-text-primary mt-2">
                        {{ number_format($summary['total_kk'] ?? 0) }}
                    </h2>
                </div>
                <div class="w-11 h-11 rounded-sm bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between text-xs text-text-secondary">
                <span>Terdaftar resmi di sistem</span>
                <a href="{{ route('kependudukan.kk.index') }}" class="text-primary hover:underline font-medium">Lihat &rarr;</a>
            </div>
        </div>

        <!-- Card 3: Surat Menunggu Tindakan -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Surat Butuh Tindakan
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold font-display text-text-primary mt-2">
                        {{ number_format($summary['surat_menunggu_verifikasi'] ?? 0) }}
                    </h2>
                </div>
                <div class="w-11 h-11 rounded-sm bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between text-xs text-text-secondary">
                <span>
                    @if(($summary['surat_menunggu_verifikasi'] ?? 0) > 0)
                        <span class="text-amber-700 font-semibold">Ada antrean verifikasi</span>
                    @else
                        Tidak ada antrean
                    @endif
                </span>
                <a href="{{ route('persuratan.index') }}" class="text-primary hover:underline font-medium">Lihat &rarr;</a>
            </div>
        </div>

        <!-- Card 4: Laporan Aktif -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Laporan Warga Aktif
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold font-display text-text-primary mt-2">
                        {{ number_format($summary['laporan_aktif'] ?? 0) }}
                    </h2>
                </div>
                <div class="w-11 h-11 rounded-sm bg-sky-50 text-sky-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-border/60 flex items-center justify-between text-xs text-text-secondary">
                <span>
                    {{ $summary['laporan_berdasarkan_status']['SUBMITTED'] ?? 0 }} baru &bull; {{ $summary['laporan_berdasarkan_status']['IN_PROGRESS'] ?? 0 }} proses
                </span>
                <a href="{{ route('laporan-aspirasi.index') }}" class="text-primary hover:underline font-medium">Lihat &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 3. Financial Overview Cards (Tampil untuk Role Keuangan / Pimpinan / RT) -->
    @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'BENDAHARA_RW', 'KETUA_RT']))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        <!-- Penerimaan Iuran Bulan Ini -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                    @if($rtCode) Iuran RT {{ $rtCode }} @else Penerimaan Iuran RW @endif (Bulan Ini)
                </span>
                <span class="px-2 py-0.5 rounded-sm bg-emerald-50 text-emerald-700 text-xs font-semibold">
                    Kepatuhan {{ $summary['kepatuhan_iuran_persen'] ?? 0 }}%
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-bold font-display text-text-primary mt-2">
                Rp {{ number_format($summary['total_iuran_bulan_ini'] ?? 0, 0, ',', '.') }}
            </p>
            <div class="mt-3 w-full bg-border rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(max((float)($summary['kepatuhan_iuran_persen'] ?? 0), 0), 100) }}%"></div>
            </div>
            <div class="mt-2 flex justify-between text-xs text-text-secondary">
                <span>Status transaksi: <strong>APPROVED</strong></span>
                <a href="{{ route('keuangan.iuran.index') }}" class="text-primary hover:underline font-medium">Detail &rarr;</a>
            </div>
        </div>

        @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'BENDAHARA_RW']))
        <!-- Saldo Kas RW -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                    Saldo Kas RW 047
                </span>
                <span class="px-2 py-0.5 rounded-sm bg-primary-light text-primary text-xs font-semibold">
                    Total Bersih
                </span>
            </div>
            <p class="text-xl sm:text-2xl font-bold font-display text-text-primary mt-2">
                Rp {{ number_format($saldo_kas_rw ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-3 text-xs text-text-secondary">
                Pengeluaran bulan ini: <strong class="text-text-primary">Rp {{ number_format($kas_keluar_bulan_ini ?? 0, 0, ',', '.') }}</strong>
            </p>
            <div class="mt-2 pt-2 border-t border-border/60 flex justify-between text-xs">
                <span class="text-text-secondary">Iuran Masuk - Kas Keluar</span>
                <a href="{{ route('keuangan.rekap.index') }}" class="text-primary hover:underline font-medium">Rekapitulasi &rarr;</a>
            </div>
        </div>

        <!-- Antrean Approval Keuangan -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                    Persetujuan Keuangan
                </span>
                <span class="w-2 h-2 rounded-full {{ (($iuran_menunggu_approval ?? 0) > 0 || ($kas_keluar_menunggu_approval ?? 0) > 0) ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500' }}"></span>
            </div>
            <div class="mt-2 space-y-1.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary text-xs">Iuran Pending Approval:</span>
                    <strong class="font-semibold {{ ($iuran_menunggu_approval ?? 0) > 0 ? 'text-amber-700' : 'text-text-primary' }}">
                        {{ $iuran_menunggu_approval ?? 0 }} transaksi
                    </strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-text-secondary text-xs">Kas Keluar Pending:</span>
                    <strong class="font-semibold {{ ($kas_keluar_menunggu_approval ?? 0) > 0 ? 'text-red-700' : 'text-text-primary' }}">
                        {{ $kas_keluar_menunggu_approval ?? 0 }} usulan
                    </strong>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-border/60 flex justify-between text-xs">
                @if($roleName === 'BENDAHARA_RW')
                    <a href="{{ route('keuangan.iuran.approval') }}" class="text-primary hover:underline font-semibold">Proses Approval Iuran &rarr;</a>
                @elseif($roleName === 'KETUA_RW')
                    <a href="{{ route('keuangan.kas-keluar.approval') }}" class="text-primary hover:underline font-semibold">Approval Kas Keluar &rarr;</a>
                @else
                    <a href="{{ route('keuangan.iuran.index') }}" class="text-primary hover:underline font-semibold">Kelola Keuangan &rarr;</a>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- 4. Action Center: "Butuh Tindakan Anda" (UI_UX_SPECIFICATION.md §2.2a) -->
    <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden">
        <div class="p-5 border-b border-border bg-surface flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-sm bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                    ⚡
                </div>
                <div>
                    <h2 class="text-base font-semibold text-text-primary">
                        Butuh Tindakan Anda
                    </h2>
                    <p class="text-xs text-text-secondary">
                        Antrean verifikasi dan persetujuan mendesak yang memerlukan keputusan peran Anda
                    </p>
                </div>
            </div>
            <div>
                <span class="px-2.5 py-1 rounded-sm text-xs font-semibold {{ count($action_items ?? []) > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                    {{ count($action_items ?? []) }} Tugas Menunggu
                </span>
            </div>
        </div>

        <div class="divide-y divide-border">
            @forelse($action_items ?? [] as $item)
                <div class="p-4 hover:bg-background/60 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $item['badge_class'] ?? 'bg-background text-text-secondary' }}">
                                {{ $item['badge_label'] ?? 'Tindakan' }}
                            </span>
                            <h3 class="text-sm font-semibold text-text-primary">
                                {{ $item['title'] }}
                            </h3>
                        </div>
                        <p class="text-xs text-text-secondary">
                            {!! $item['subtitle'] !!}
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="{{ $item['action_url'] }}" class="inline-flex items-center justify-center px-3.5 py-1.5 rounded-sm bg-primary text-white text-xs font-semibold hover:bg-primary-hover shadow-sm transition-colors min-h-touch">
                            {{ $item['action_label'] }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-text-primary">
                        Semua Tugas Selesai!
                    </h3>
                    <p class="text-xs text-text-secondary max-w-md mx-auto">
                        Tidak ada pengajuan surat, verifikasi warga, atau transaksi keuangan yang sedang menunggu tindakan Anda saat ini.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 5. Secondary Layout: Distribusi Status & Akses Cepat -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Distribusi Status Laporan & Aspirasi -->
        <div class="lg:col-span-2 bg-surface p-5 rounded-md border border-border shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-3">
                <div>
                    <h3 class="text-sm font-semibold text-text-primary">
                        Distribusi Penanganan Laporan & Aspirasi Warga
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Rekap status tindak lanjut seluruh laporan masuk di RW 047
                    </p>
                </div>
                <a href="{{ route('laporan-aspirasi.index') }}" class="text-xs text-primary hover:underline font-semibold">
                    Semua Laporan &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 rounded-sm bg-yellow-50/70 border border-yellow-200">
                    <span class="text-[11px] font-semibold text-yellow-800 uppercase tracking-wider">SUBMITTED</span>
                    <p class="text-xl font-bold font-display text-yellow-900 mt-1">
                        {{ $laporan_distribution['SUBMITTED'] ?? 0 }}
                    </p>
                    <span class="text-[10px] text-yellow-700">Baru Masuk</span>
                </div>
                <div class="p-3 rounded-sm bg-blue-50/70 border border-blue-200">
                    <span class="text-[11px] font-semibold text-blue-800 uppercase tracking-wider">IN PROGRESS</span>
                    <p class="text-xl font-bold font-display text-blue-900 mt-1">
                        {{ $laporan_distribution['IN_PROGRESS'] ?? 0 }}
                    </p>
                    <span class="text-[10px] text-blue-700">Sedang Ditangani</span>
                </div>
                <div class="p-3 rounded-sm bg-green-50/70 border border-green-200">
                    <span class="text-[11px] font-semibold text-green-800 uppercase tracking-wider">RESOLVED</span>
                    <p class="text-xl font-bold font-display text-green-900 mt-1">
                        {{ $laporan_distribution['RESOLVED'] ?? 0 }}
                    </p>
                    <span class="text-[10px] text-green-700">Selesai Ditindak</span>
                </div>
                <div class="p-3 rounded-sm bg-gray-50 border border-gray-200">
                    <span class="text-[11px] font-semibold text-gray-700 uppercase tracking-wider">CLOSED</span>
                    <p class="text-xl font-bold font-display text-gray-800 mt-1">
                        {{ $laporan_distribution['CLOSED'] ?? 0 }}
                    </p>
                    <span class="text-[10px] text-gray-600">Tiket Ditutup</span>
                </div>
            </div>
        </div>

        <!-- Right Col: Akses Cepat Operasional (Quick Shortcuts) -->
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm space-y-4">
            <div class="border-b border-border pb-3">
                <h3 class="text-sm font-semibold text-text-primary">
                    Pintasan Tindakan Cepat
                </h3>
                <p class="text-xs text-text-secondary">
                    Akses langsung ke formulir operasional
                </p>
            </div>

            <div class="space-y-2">
                @if(in_array($roleName, ['KETUA_RT']))
                    <a href="{{ route('persuratan.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Review Pengajuan Surat</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('kependudukan.warga.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Tambah Data Warga Baru</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('kependudukan.kk.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Daftarkan KK Baru</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('keuangan.iuran.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Catat Pembayaran Iuran</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                @elseif(in_array($roleName, ['SEKRETARIS_RW']))
                    <a href="{{ route('kependudukan.warga.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Verifikasi Data Kependudukan</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('persuratan.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Proses Verifikasi Persuratan</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('informasi-publik.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Buat Pengumuman / Berita Baru</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                @elseif(in_array($roleName, ['BENDAHARA_RW']))
                    <a href="{{ route('keuangan.iuran.approval') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Antrean Approval Iuran</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('keuangan.kas-keluar.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Catat Pengeluaran Kas Baru</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('keuangan.rekap.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Laporan Rekapitulasi Kas</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                @elseif(in_array($roleName, ['KETUA_RW', 'SUPER_ADMIN']))
                    <a href="{{ route('keuangan.kas-keluar.approval') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Approval Pengeluaran Kas RW</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('persuratan.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Persetujuan Surat Pengantar Final</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('laporan-aspirasi.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Monitoring Laporan Warga</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('keuangan.rekap.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Rekapitulasi Keuangan RW</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                @else
                    <a href="{{ route('persuratan.public.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Ajukan Surat Pengantar / SKD</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('persuratan.public.track') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Lacak Status Surat</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('portal.laporan.create') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Sampaikan Laporan / Keluhan</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                    <a href="{{ route('portal.informasi.index') }}" class="flex items-center justify-between p-2.5 rounded-sm bg-background hover:bg-primary-light/50 border border-border hover:border-primary/30 transition-colors text-xs font-medium text-text-primary">
                        <span>Katalog Informasi & Agenda RW</span>
                        <span class="text-primary font-semibold">&rarr;</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
