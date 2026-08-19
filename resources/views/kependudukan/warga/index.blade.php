@extends('layouts.dashboard')

@section('title', 'Daftar Warga')
@section('breadcrumb', 'Data Warga')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    $canCreate = in_array($roleName, ['SUPER_ADMIN', 'SEKRETARIS_RW', 'KETUA_RT']);
    $isKetuaRt = $roleName === 'KETUA_RT';
@endphp

<div class="space-y-6">
    <!-- Header Page & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Daftar Data Warga
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Master data kependudukan warga RW 047
                @if($isKetuaRt && $user?->rt_code)
                    &bull; Wilayah Terisolasi: <span class="font-semibold text-primary">RT {{ $user->rt_code }}</span>
                @endif
            </p>
        </div>

        @if($canCreate)
        <div>
            <a href="{{ route('kependudukan.warga.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch min-w-touch justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Warga Baru</span>
            </a>
        </div>
        @endif
    </div>

    <!-- Flash Notification Messages -->
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

    <!-- Filter & Search Panel -->
    <div class="bg-surface p-4 sm:p-5 rounded-md border border-border shadow-sm">
        <form method="GET" action="{{ route('kependudukan.warga.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Query -->
            <div class="lg:col-span-1">
                <label for="search" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Pencarian Nama</label>
                <div class="relative">
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari nama lengkap..."
                        class="w-full px-3.5 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <!-- RT Filter (Hidden/Disabled for KETUA_RT) -->
            @if(!$isKetuaRt)
            <div>
                <label for="rt_code" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Wilayah RT</label>
                <select id="rt_code" name="rt_code"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua RT (RW 047)</option>
                    @foreach(['001', '002', '003', '004', '005', '006', '007', '008'] as $rt)
                        <option value="{{ $rt }}" {{ request('rt_code') === $rt ? 'selected' : '' }}>RT {{ $rt }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Status Verifikasi Filter -->
            <div>
                <label for="verification_status" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Status Verifikasi</label>
                <select id="verification_status" name="verification_status"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="MENUNGGU_VERIFIKASI" {{ request('verification_status') === 'MENUNGGU_VERIFIKASI' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="TERVERIFIKASI" {{ request('verification_status') === 'TERVERIFIKASI' ? 'selected' : '' }}>Terverifikasi</option>
                    <option value="DITOLAK" {{ request('verification_status') === 'DITOLAK' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <!-- Status Warga Filter -->
            <div>
                <label for="status_warga" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Status Kependudukan</label>
                <select id="status_warga" name="status_warga"
                    class="w-full px-3 py-2 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Semua Kependudukan</option>
                    <option value="TETAP" {{ request('status_warga') === 'TETAP' ? 'selected' : '' }}>Warga Tetap</option>
                    <option value="KONTRAK" {{ request('status_warga') === 'KONTRAK' ? 'selected' : '' }}>Warga Kontrak</option>
                    <option value="PINDAH" {{ request('status_warga') === 'PINDAH' ? 'selected' : '' }}>Pindah</option>
                    <option value="MENINGGAL" {{ request('status_warga') === 'MENINGGAL' ? 'selected' : '' }}>Meninggal</option>
                </select>
            </div>

            <!-- Action Buttons for Filter -->
            <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2 border-t border-border/50">
                <a href="{{ route('kependudukan.warga.index') }}" class="px-3.5 py-2 text-xs font-medium text-text-secondary hover:text-text-primary bg-background border border-border rounded-sm min-h-touch flex items-center justify-center">
                    Reset Filter
                </a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-dark rounded-sm min-h-touch flex items-center justify-center transition-colors">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table (Desktop) / Cards (Mobile) -->
    @if($wargas->isEmpty())
        <x-empty-state 
            title="Belum Ada Data Warga" 
            description="Tidak ditemukan data warga yang sesuai dengan kriteria filter yang diterapkan."
            :actionUrl="$canCreate ? route('kependudukan.warga.create') : null"
            actionLabel="Tambah Warga Baru"
        />
    @else
        <!-- Desktop Table View -->
        <div class="hidden md:block bg-surface rounded-md border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-background border-b border-border text-xs font-semibold text-text-secondary uppercase tracking-wider">
                            <th scope="col" class="py-3 px-4">Nama Lengkap</th>
                            <th scope="col" class="py-3 px-4">NIK (Masked)</th>
                            <th scope="col" class="py-3 px-4">No. KK (Masked)</th>
                            <th scope="col" class="py-3 px-4">RT</th>
                            <th scope="col" class="py-3 px-4">Status Hubungan</th>
                            <th scope="col" class="py-3 px-4">Status Warga</th>
                            <th scope="col" class="py-3 px-4">Verifikasi</th>
                            <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($wargas as $warga)
                        <tr class="hover:bg-primary-light/30 transition-colors cursor-pointer" onclick="window.location='{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}'">
                            <td class="py-3 px-4 font-semibold text-text-primary">
                                {{ $warga->nama_lengkap }}
                                <div class="text-xs text-text-secondary font-normal font-sans">
                                    {{ $warga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }} &bull; {{ $warga->tanggal_lahir?->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-text-secondary">
                                {{ $warga->nik_masked }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-text-secondary">
                                {{ $warga->no_kk_masked }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-sm bg-background border border-border text-xs font-medium">
                                    RT {{ $warga->kartuKeluarga?->rt_code ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-text-secondary">
                                {{ $warga->status_hubungan_keluarga }}
                            </td>
                            <td class="py-3 px-4">
                                <x-status-badge :status="$warga->status_warga" />
                            </td>
                            <td class="py-3 px-4">
                                <x-verification-badge :status="$warga->verification_status" />
                            </td>
                            <td class="py-3 px-4 text-right" onclick="event.stopPropagation()">
                                <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="inline-flex items-center justify-center p-2 text-primary hover:bg-primary-light rounded-sm min-w-touch min-h-touch" title="Lihat Detail">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-3">
            @foreach($wargas as $warga)
            <div class="bg-surface p-4 rounded-md border border-border shadow-sm space-y-3 cursor-pointer hover:border-primary/50 transition-colors" onclick="window.location='{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}'">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-text-primary text-base">{{ $warga->nama_lengkap }}</h2>
                        <p class="text-xs text-text-secondary mt-0.5">
                            {{ $warga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }} &bull; RT {{ $warga->kartuKeluarga?->rt_code ?? '-' }}
                        </p>
                    </div>
                    <x-verification-badge :status="$warga->verification_status" />
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-border/60">
                    <div>
                        <span class="text-text-secondary block">NIK (Masked):</span>
                        <span class="font-mono text-text-primary">{{ $warga->nik_masked }}</span>
                    </div>
                    <div>
                        <span class="text-text-secondary block">No. KK (Masked):</span>
                        <span class="font-mono text-text-primary">{{ $warga->no_kk_masked }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-border/60">
                    <x-status-badge :status="$warga->status_warga" />
                    <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1 min-h-touch items-center">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination Links -->
        <div class="mt-4">
            {{ $wargas->links() }}
        </div>
    @endif
</div>
@endsection
