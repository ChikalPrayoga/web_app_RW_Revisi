@extends('layouts.dashboard')

@section('title', 'Pengajuan Surat')
@section('breadcrumb', 'Pengajuan Surat')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    $isKetuaRt = $roleName === 'KETUA_RT';
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Pengajuan Surat
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Daftar pengajuan surat warga untuk diverifikasi
                @if($isKetuaRt && $user?->rt_code)
                    &bull; Wilayah: <span class="font-semibold text-primary">RT {{ $user->rt_code }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('persuratan.public.create') }}" target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Portal Pengajuan Publik
        </a>
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
        <form method="GET" action="{{ route('persuratan.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Filter Status --}}
            <div>
                <label for="filter_status" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Status</label>
                <select id="filter_status" name="current_status"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    @foreach(['SUBMITTED' => 'Menunggu RT', 'RT_REVIEW' => 'Review RT (Menunggu RW)', 'RW_REVIEW' => 'Review Ketua RW', 'COMPLETED' => 'Selesai', 'REJECTED' => 'Ditolak'] as $val => $label)
                        <option value="{{ $val }}" {{ request('current_status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Jenis --}}
            <div>
                <label for="filter_jenis" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Jenis Surat</label>
                <select id="filter_jenis" name="jenis_surat"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Jenis</option>
                    <option value="SURAT_PENGANTAR" {{ request('jenis_surat') === 'SURAT_PENGANTAR' ? 'selected' : '' }}>Surat Pengantar</option>
                    <option value="SURAT_KETERANGAN_DOMISILI" {{ request('jenis_surat') === 'SURAT_KETERANGAN_DOMISILI' ? 'selected' : '' }}>Surat Keterangan Domisili</option>
                </select>
            </div>

            {{-- Filter RT (hanya non-KETUA_RT) --}}
            @if(!$isKetuaRt)
            <div>
                <label for="filter_rt" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Wilayah RT</label>
                <select id="filter_rt" name="rt_code"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua RT</option>
                    @foreach(['001', '002', '003', '004', '005', '006', '007', '008'] as $rt)
                        <option value="{{ $rt }}" {{ request('rt_code') === $rt ? 'selected' : '' }}>RT {{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('persuratan.index') }}" class="px-3 py-2 text-text-secondary hover:text-danger text-sm border border-border rounded-sm hover:border-danger transition-colors min-h-touch flex items-center justify-center" title="Reset filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
        </form>
    </div>

    {{-- Table (Desktop) / Card (Mobile) --}}
    <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-background">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Kode Lacak</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Pemohon</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($pengajuans as $p)
                    @php
                        $statusBadge = [
                            'SUBMITTED' => ['label' => 'Menunggu RT', 'class' => 'bg-warning-light text-warning border-warning/20'],
                            'RT_REVIEW' => ['label' => 'Menunggu RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
                            'RW_REVIEW' => ['label' => 'Review RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
                            'COMPLETED' => ['label' => 'Selesai', 'class' => 'bg-success-light text-success border-success/20'],
                            'REJECTED' => ['label' => 'Ditolak', 'class' => 'bg-danger-light text-danger border-danger/20'],
                        ];
                        $sb = $statusBadge[$p->current_status?->value] ?? ['label' => $p->current_status?->value, 'class' => 'bg-background text-text-secondary border-border'];
                    @endphp
                    <tr class="hover:bg-background/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-medium text-primary">{{ $p->tracking_code }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-text-primary font-medium">{{ optional($p->warga)->nama_lengkap ?? '—' }}</div>
                            <div class="text-xs text-text-secondary">RT {{ optional(optional($p->warga)->kartuKeluarga)->rt_code ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-text-secondary">
                            {{ $p->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Pengantar' : 'Ket. Domisili' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-semibold border {{ $sb['class'] }}">{{ $sb['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs">
                            {{ $p->tanggal_pengajuan?->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('persuratan.show', $p->pengajuan_id) }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary-light rounded-sm transition-colors">
                                Detail
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-text-secondary">
                            <svg class="w-10 h-10 mx-auto mb-3 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="font-medium text-text-primary">Tidak ada pengajuan ditemukan</p>
                            <p class="text-xs mt-1">Coba ubah filter pencarian</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card List --}}
        <div class="sm:hidden divide-y divide-border">
            @forelse($pengajuans as $p)
            @php
                $statusBadge = [
                    'SUBMITTED' => ['label' => 'Menunggu RT', 'class' => 'bg-warning-light text-warning border-warning/20'],
                    'RT_REVIEW' => ['label' => 'Menunggu RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
                    'RW_REVIEW' => ['label' => 'Review RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
                    'COMPLETED' => ['label' => 'Selesai', 'class' => 'bg-success-light text-success border-success/20'],
                    'REJECTED' => ['label' => 'Ditolak', 'class' => 'bg-danger-light text-danger border-danger/20'],
                ];
                $sb = $statusBadge[$p->current_status?->value] ?? ['label' => $p->current_status?->value, 'class' => 'bg-background text-text-secondary border-border'];
            @endphp
            <a href="{{ route('persuratan.show', $p->pengajuan_id) }}" class="block p-4 hover:bg-background/50 transition-colors">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <p class="text-sm font-medium text-text-primary">{{ optional($p->warga)->nama_lengkap ?? '—' }}</p>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $p->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Surat Pengantar' : 'Ket. Domisili' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-semibold border {{ $sb['class'] }} flex-shrink-0">{{ $sb['label'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs text-primary">{{ $p->tracking_code }}</span>
                    <span class="text-xs text-text-secondary">{{ $p->tanggal_pengajuan?->format('d M Y') }}</span>
                </div>
            </a>
            @empty
            <div class="py-12 text-center text-sm text-text-secondary">
                <p>Tidak ada pengajuan ditemukan</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($pengajuans->hasPages())
    <div class="flex justify-center">
        {{ $pengajuans->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
