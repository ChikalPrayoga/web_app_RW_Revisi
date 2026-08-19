@extends('layouts.dashboard')

@section('title', 'Daftar Iuran Warga')
@section('breadcrumb', 'Iuran Warga')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    $isKetuaRt = $roleName === 'KETUA_RT';
    $isBendahara = $roleName === 'BENDAHARA_RW';
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Iuran Warga
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Daftar pencatatan iuran warga RW 047
                @if($isKetuaRt && $user?->rt_code)
                    &bull; Wilayah: <span class="font-semibold text-primary">RT {{ $user->rt_code }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($isKetuaRt)
            <a href="{{ route('keuangan.iuran.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Catat Iuran Baru
            </a>
            @endif

            @if($isBendahara)
            <a href="{{ route('keuangan.iuran.approval') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface hover:bg-primary-light/50 border border-primary/30 text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Verifikasi Iuran
            </a>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="p-4 rounded-sm bg-success-light border border-success/30 text-success flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Filter Panel --}}
    <div class="bg-surface p-4 sm:p-5 rounded-md border border-border shadow-sm">
        <form method="GET" action="{{ route('keuangan.iuran.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Filter Status --}}
            <div>
                <label for="filter_status" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Status</label>
                <select id="filter_status" name="status"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    @foreach(['PENDING' => 'Menunggu Verifikasi', 'APPROVED' => 'Disetujui', 'REJECTED' => 'Ditolak'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Jenis Iuran --}}
            <div>
                <label for="filter_type" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Jenis Iuran</label>
                <select id="filter_type" name="iuran_type_id"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Jenis</option>
                    @foreach($iuranTypes as $type)
                        <option value="{{ $type->id }}" {{ (string) request('iuran_type_id') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }} ({{ $type->code }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Periode Bulan --}}
            <div>
                <label for="filter_bulan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Bulan</label>
                <select id="filter_bulan" name="periode_bulan"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Bulan</option>
                    @foreach([1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'] as $m => $nama)
                        <option value="{{ $m }}" {{ (string) request('periode_bulan') === (string) $m ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Periode Tahun --}}
            <div>
                <label for="filter_tahun" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Tahun</label>
                <select id="filter_tahun" name="periode_tahun"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Tahun</option>
                    @foreach(range(date('Y') - 1, date('Y') + 1) as $yr)
                        <option value="{{ $yr }}" {{ (string) request('periode_tahun') === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('keuangan.iuran.index') }}" class="px-3 py-2 text-text-secondary hover:text-danger text-sm border border-border rounded-sm hover:border-danger transition-colors min-h-touch flex items-center justify-center" title="Reset filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Data List --}}
    <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-background">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">No. KK (Masked)</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">RT</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis Iuran</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Periode</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Nominal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Dicatat Oleh</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($catatanIurans as $item)
                    <tr class="hover:bg-primary-light/10 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-text-primary">
                            {{ $item->kartuKeluarga?->no_kk_masked ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-primary-light text-primary">
                                RT {{ $item->kartuKeluarga?->rt_code ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-text-primary">
                            {{ $item->iuranType?->name ?? '—' }}
                            <span class="text-xs text-text-secondary block font-normal">({{ $item->iuranType?->code }})</span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary">
                            {{ sprintf('%02d/%04d', $item->periode_bulan, $item->periode_tahun) }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-text-primary">
                            Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($item->status->value === 'APPROVED')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-success-light text-success border border-success/30">
                                    Disetujui
                                </span>
                            @elseif($item->status->value === 'REJECTED')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-danger-light text-danger border border-danger/30" title="{{ $item->rejection_notes }}">
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-warning-light text-warning border border-warning/30">
                                    Menunggu Verifikasi
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-text-secondary">
                            {{ $item->recordedBy?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-text-secondary">
                            {{ $item->tanggal_pembayaran ? $item->tanggal_pembayaran->format('d/m/Y') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-text-secondary">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-border mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="font-medium text-text-primary">Belum ada transaksi iuran</p>
                                <p class="text-xs text-text-secondary mt-1">Tidak ada data transaksi yang sesuai filter yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-border">
            @forelse($catatanIurans as $item)
            <div class="p-4 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs font-semibold text-text-primary">
                        {{ $item->kartuKeluarga?->no_kk_masked ?? '—' }}
                    </span>
                    @if($item->status->value === 'APPROVED')
                        <span class="px-2 py-0.5 rounded-sm text-xs font-medium bg-success-light text-success">Disetujui</span>
                    @elseif($item->status->value === 'REJECTED')
                        <span class="px-2 py-0.5 rounded-sm text-xs font-medium bg-danger-light text-danger">Ditolak</span>
                    @else
                        <span class="px-2 py-0.5 rounded-sm text-xs font-medium bg-warning-light text-warning">Menunggu</span>
                    @endif
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-text-secondary">{{ $item->iuranType?->name }} (Periode {{ sprintf('%02d/%04d', $item->periode_bulan, $item->periode_tahun) }})</span>
                    <span class="font-semibold text-primary">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-text-secondary pt-1 border-t border-border/50">
                    <span>RT {{ $item->kartuKeluarga?->rt_code }} &bull; Oleh: {{ $item->recordedBy?->full_name }}</span>
                    <span>{{ $item->tanggal_pembayaran ? $item->tanggal_pembayaran->format('d/m/Y') : '' }}</span>
                </div>
                @if($item->rejection_notes)
                <div class="text-xs text-danger bg-danger-light/50 p-2 rounded-sm border border-danger/20">
                    <strong>Catatan:</strong> {{ $item->rejection_notes }}
                </div>
                @endif
            </div>
            @empty
            <div class="p-8 text-center text-text-secondary text-sm">
                Belum ada transaksi iuran tercatat.
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($catatanIurans->hasPages())
        <div class="px-4 py-3 border-t border-border bg-background flex items-center justify-between">
            <span class="text-xs text-text-secondary">
                Menampilkan {{ $catatanIurans->firstItem() }} - {{ $catatanIurans->lastItem() }} dari {{ $catatanIurans->total() }} transaksi
            </span>
            {{ $catatanIurans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
