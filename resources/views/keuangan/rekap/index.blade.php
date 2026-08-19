@extends('layouts.dashboard')

@section('title', 'Rekapitulasi Keuangan')
@section('breadcrumb', 'Rekapitulasi Keuangan')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    $isKetuaRt = $roleName === 'KETUA_RT';
    $saldoAkhir = (float) $rekapGabungan['saldo_akhir'];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Rekapitulasi Keuangan RW
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Laporan realisasi pemasukan iuran warga, pengeluaran kas, dan posisi saldo akhir kas RW 047
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-sm bg-primary-light text-primary border border-primary/20">
                Periode: {{ date('F Y', mktime(0, 0, 0, $periodeBulan, 1, $periodeTahun)) }}
            </span>
        </div>
    </div>

    {{-- Filter Periode Form --}}
    <div class="bg-surface p-4 sm:p-5 rounded-md border border-border shadow-sm">
        <form method="GET" action="{{ route('keuangan.rekap.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="periode_bulan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Bulan Periode</label>
                <select id="periode_bulan" name="periode_bulan"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @foreach([1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'] as $m => $nama)
                        <option value="{{ $m }}" {{ $periodeBulan === $m ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="periode_tahun" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Tahun Periode</label>
                <select id="periode_tahun" name="periode_tahun"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @foreach(range(date('Y') - 2, date('Y') + 2) as $yr)
                        <option value="{{ $yr }}" {{ $periodeTahun === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            @if(!$isKetuaRt)
            <div>
                <label for="rt_code" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Filter Wilayah RT (Iuran)</label>
                <select id="rt_code" name="rt_code"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Seluruh RW 047</option>
                    @foreach(['001', '002', '003', '004', '005', '006', '007', '008'] as $rt)
                        <option value="{{ $rt }}" {{ $rtCode === $rt ? 'selected' : '' }}>RT {{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-end">
                <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Tampilkan Rekapitulasi
                </button>
            </div>
        </form>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        {{-- Total Pemasukan --}}
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-sm bg-success-light text-success flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Total Pemasukan Iuran</p>
                <p class="text-xl sm:text-2xl font-bold text-success mt-0.5">
                    Rp {{ number_format((float) $rekapGabungan['total_pemasukan'], 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-text-secondary mt-0.5">Hanya transaksi APPROVED</p>
            </div>
        </div>

        {{-- Total Pengeluaran --}}
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-sm bg-danger-light text-danger flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Total Pengeluaran Kas</p>
                <p class="text-xl sm:text-2xl font-bold text-danger mt-0.5">
                    Rp {{ number_format((float) $rekapGabungan['total_pengeluaran'], 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-text-secondary mt-0.5">Hanya kas keluar APPROVED</p>
            </div>
        </div>

        {{-- Saldo Kas Akhir --}}
        <div class="bg-surface p-5 rounded-md border border-border shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-sm {{ $saldoAkhir >= 0 ? 'bg-primary-light text-primary' : 'bg-danger-light text-danger' }} flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Posisi Saldo Riil</p>
                <p class="text-xl sm:text-2xl font-bold {{ $saldoAkhir >= 0 ? 'text-primary' : 'text-danger' }} mt-0.5">
                    Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                </p>
                <p class="text-[11px] text-text-secondary mt-0.5">Pemasukan &minus; Pengeluaran</p>
            </div>
        </div>
    </div>

    {{-- Detail Breakdown Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Rincian Pemasukan Iuran --}}
        <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border bg-background flex items-center justify-between">
                <h3 class="font-display font-semibold text-sm text-text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Rincian Pemasukan Iuran Warga
                </h3>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface text-xs text-text-secondary">
                            <th class="text-left px-4 py-2.5 font-semibold">Jenis Iuran</th>
                            <th class="text-center px-4 py-2.5 font-semibold">Transaksi</th>
                            <th class="text-right px-4 py-2.5 font-semibold">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($rekapGabungan['rincian_pemasukan_iuran'] as $item)
                        <tr class="hover:bg-primary-light/5">
                            <td class="px-4 py-3 font-medium text-text-primary">
                                {{ $item['jenis_iuran'] }}
                                <span class="text-xs text-text-secondary block font-normal">({{ $item['code'] }})</span>
                            </td>
                            <td class="px-4 py-3 text-center text-text-secondary text-xs">
                                {{ $item['jumlah_transaksi'] }} KK
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-success">
                                Rp {{ number_format((float) $item['total_nominal'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-text-secondary text-xs">
                                Tidak ada data iuran pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rincian Pengeluaran Kas --}}
        <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-border bg-background flex items-center justify-between">
                <h3 class="font-display font-semibold text-sm text-text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                    </svg>
                    Rincian Pengeluaran Kas RW
                </h3>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface text-xs text-text-secondary">
                            <th class="text-left px-4 py-2.5 font-semibold">Kategori Pos</th>
                            <th class="text-center px-4 py-2.5 font-semibold">Frekuensi</th>
                            <th class="text-right px-4 py-2.5 font-semibold">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($rekapGabungan['rincian_pengeluaran_kas'] as $item)
                        <tr class="hover:bg-primary-light/5">
                            <td class="px-4 py-3 font-medium text-text-primary">
                                {{ $item['kategori'] }}
                            </td>
                            <td class="px-4 py-3 text-center text-text-secondary text-xs">
                                {{ $item['jumlah_transaksi'] }} transaksi
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-danger">
                                Rp {{ number_format((float) $item['total_nominal'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-text-secondary text-xs">
                                Tidak ada data pengeluaran kas pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Statistik Kepatuhan Warga (Iuran Recap Card) --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 space-y-4">
        <h3 class="font-display font-semibold text-base text-text-primary flex items-center gap-2">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Statistik Kepatuhan Pembayaran Iuran @if($rtCode) (RT {{ $rtCode }}) @else (Seluruh RW) @endif
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div class="p-4 bg-background rounded-sm border border-border">
                <p class="text-xs text-text-secondary font-medium uppercase">Total KK Terdaftar</p>
                <p class="text-xl font-bold text-text-primary mt-1">{{ $rekapIuran['total_kk_wajib_bayar'] }} KK</p>
            </div>

            <div class="p-4 bg-background rounded-sm border border-border">
                <p class="text-xs text-text-secondary font-medium uppercase">Total KK Sudah Bayar</p>
                <p class="text-xl font-bold text-success mt-1">{{ $rekapIuran['total_kk_sudah_bayar'] }} KK</p>
            </div>

            <div class="p-4 bg-background rounded-sm border border-border">
                <p class="text-xs text-text-secondary font-medium uppercase">Tingkat Partisipasi</p>
                @php
                    $persen = $rekapIuran['total_kk_wajib_bayar'] > 0 
                        ? round(($rekapIuran['total_kk_sudah_bayar'] / $rekapIuran['total_kk_wajib_bayar']) * 100, 1) 
                        : 0;
                @endphp
                <p class="text-xl font-bold text-primary mt-1">{{ $persen }}%</p>
            </div>
        </div>
    </div>
</div>
@endsection
