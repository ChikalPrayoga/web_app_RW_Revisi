@extends('layouts.dashboard')

@section('title', 'Daftar Kartu Keluarga')
@section('breadcrumb', 'Kartu Keluarga')

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
                Daftar Kartu Keluarga
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Master data Kartu Keluarga RW 047
                @if($isKetuaRt && $user?->rt_code)
                    &bull; Wilayah Terisolasi: <span class="font-semibold text-primary">RT {{ $user->rt_code }}</span>
                @endif
            </p>
        </div>

        @if($canCreate)
        <div>
            <a href="{{ route('kependudukan.kk.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch min-w-touch justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tambah Kartu Keluarga</span>
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
        <form method="GET" action="{{ route('kependudukan.kk.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Search Query -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">Pencarian Alamat / Blok / No. Rumah</label>
                <div class="relative">
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari blok, nomor rumah, atau status rumah..."
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

            <!-- Action Buttons for Filter -->
            <div class="sm:col-span-2 lg:col-span-3 flex items-center justify-end gap-2 pt-2 border-t border-border/50">
                <a href="{{ route('kependudukan.kk.index') }}" class="px-3.5 py-2 text-xs font-medium text-text-secondary hover:text-text-primary bg-background border border-border rounded-sm min-h-touch flex items-center justify-center">
                    Reset Filter
                </a>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-dark rounded-sm min-h-touch flex items-center justify-center transition-colors">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table (Desktop) / Cards (Mobile) -->
    @if($kartuKeluargas->isEmpty())
        <x-empty-state 
            title="Belum Ada Data Kartu Keluarga" 
            description="Tidak ditemukan data Kartu Keluarga yang sesuai dengan kriteria filter yang diterapkan."
            :actionUrl="$canCreate ? route('kependudukan.kk.create') : null"
            actionLabel="Tambah Kartu Keluarga Baru"
        />
    @else
        <!-- Desktop Table View -->
        <div class="hidden md:block bg-surface rounded-md border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-background border-b border-border text-xs font-semibold text-text-secondary uppercase tracking-wider">
                            <th scope="col" class="py-3 px-4">No. KK (Masked)</th>
                            <th scope="col" class="py-3 px-4">Wilayah RT</th>
                            <th scope="col" class="py-3 px-4">Alamat Lengkap</th>
                            <th scope="col" class="py-3 px-4">Blok / No. Rumah</th>
                            <th scope="col" class="py-3 px-4">Status Kepemilikan</th>
                            <th scope="col" class="py-3 px-4 text-center">Anggota</th>
                            <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($kartuKeluargas as $kk)
                        <tr class="hover:bg-primary-light/30 transition-colors">
                            <td class="py-3 px-4 font-mono font-medium text-text-primary text-xs">
                                {{ $kk->no_kk_masked }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-sm bg-primary-light text-primary border border-primary/20 text-xs font-semibold">
                                    RT {{ $kk->rt_code }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-text-secondary max-w-xs truncate">
                                {{ $kk->alamat_lengkap }}
                            </td>
                            <td class="py-3 px-4 text-xs text-text-secondary">
                                {{ $kk->blok ? 'Blok ' . $kk->blok : '' }} {{ $kk->nomor_rumah ? 'No. ' . $kk->nomor_rumah : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-sm bg-background border border-border text-xs font-medium">
                                    {{ $kk->status_kepemilikan_rumah }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-text-primary">
                                {{ $kk->wargas_count }} orang
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('kependudukan.warga.index', ['no_kk_hash' => $kk->no_kk_hash]) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-primary hover:bg-primary-light rounded-sm min-h-touch" title="Lihat Anggota Keluarga">
                                    <span>Lihat Anggota</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
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
            @foreach($kartuKeluargas as $kk)
            <div class="bg-surface p-4 rounded-md border border-border shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs text-text-secondary block">No. KK (Masked):</span>
                        <h2 class="font-mono font-bold text-text-primary text-sm">{{ $kk->no_kk_masked }}</h2>
                    </div>
                    <span class="px-2 py-0.5 rounded-sm bg-primary-light text-primary border border-primary/20 text-xs font-semibold">
                        RT {{ $kk->rt_code }}
                    </span>
                </div>

                <div class="text-xs text-text-secondary space-y-1">
                    <p><strong class="text-text-primary">Alamat:</strong> {{ $kk->alamat_lengkap }}</p>
                    <p><strong class="text-text-primary">Status Rumah:</strong> {{ $kk->status_kepemilikan_rumah }}</p>
                    <p><strong class="text-text-primary">Anggota:</strong> {{ $kk->wargas_count }} orang terdaftar</p>
                </div>

                <div class="pt-2 border-t border-border/60 flex justify-end">
                    <a href="{{ route('kependudukan.warga.index', ['no_kk_hash' => $kk->no_kk_hash]) }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1 min-h-touch">
                        <span>Lihat Anggota Keluarga ({{ $kk->wargas_count }})</span>
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
            {{ $kartuKeluargas->links() }}
        </div>
    @endif
</div>
@endsection
