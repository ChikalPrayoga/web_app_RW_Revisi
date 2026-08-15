@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb', 'Beranda')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    $roleDisplayName = $user?->role?->display_name ?? 'Warga';
@endphp

<div class="space-y-6">
    <!-- Header Greeting Section -->
    <div class="bg-surface p-6 rounded-md border border-border shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary leading-tight">
                Selamat datang, {{ $user?->full_name ?? 'Pengguna' }}
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                {{ $roleDisplayName }}
                @if($user?->rt_code)
                    &bull; Wilayah RT {{ $user->rt_code }}
                @endif
                &bull; RW 047
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-sm text-xs font-semibold bg-primary-light text-primary border border-primary/20">
                <span class="w-2 h-2 rounded-full bg-primary mr-1.5 animate-pulse"></span>
                Sesi Aktif
            </span>
        </div>
    </div>

    <!-- Placeholder Status Notice / Signature Elements -->
    <div class="bg-surface p-6 rounded-md border border-border shadow-sm status-ribbon-info">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-sm bg-info-light text-info flex items-center justify-center flex-shrink-0 mt-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="space-y-2 flex-1">
                <h2 class="text-base font-semibold text-text-primary">
                    Kerangka Dashboard Pengurus & Warga
                </h2>
                <p class="text-sm text-text-secondary leading-relaxed">
                    Halaman ini merupakan kerangka kerja (dashboard shell) awal hasil implementasi Sprint 3. Modul statistik ringkasan, pusat tindakan <em>"Butuh Tindakan Anda"</em>, serta integrasi data per modul (Kependudukan, Persuratan, Keuangan) akan diaktifkan secara bertahap pada sprint modul terkait.
                </p>
                <div class="pt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-sm bg-background border border-border text-text-secondary font-mono">
                        Peran: {{ $roleName }}
                    </span>
                    <span class="px-2.5 py-1 rounded-sm bg-background border border-border text-text-secondary font-mono">
                        Email: {{ $user?->email }}
                    </span>
                    @if($user?->rt_code)
                    <span class="px-2.5 py-1 rounded-sm bg-background border border-border text-text-secondary font-mono">
                        Kode RT: {{ $user->rt_code }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
