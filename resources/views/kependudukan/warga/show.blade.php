@extends('layouts.dashboard')

@section('title', 'Detail Warga — ' . $warga->nama_lengkap)
@section('breadcrumb', 'Detail Warga')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? 'WARGA';
    
    // Status ribbon class
    $statusRibbonClass = match($warga->verification_status) {
        'TERVERIFIKASI' => 'status-ribbon-success',
        'DITOLAK' => 'status-ribbon-danger',
        default => 'status-ribbon-warning',
    };

    $canEdit = ($roleName === 'KETUA_RT' && $warga->kartuKeluarga?->rt_code === $user?->rt_code) || in_array($roleName, ['SEKRETARIS_RW', 'SUPER_ADMIN']);
    $canVerify = $roleName === 'SEKRETARIS_RW' && $warga->verification_status === 'MENUNGGU_VERIFIKASI';
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Navigation & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('kependudukan.warga.index') }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-surface border border-border rounded-sm transition-colors min-h-touch min-w-touch flex items-center justify-center" aria-label="Kembali ke Daftar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                    {{ $warga->nama_lengkap }}
                </h1>
                <p class="text-xs sm:text-sm text-text-secondary font-mono mt-0.5">
                    NIK: {{ $warga->nik_masked }} &bull; Wilayah RT {{ $warga->kartuKeluarga?->rt_code ?? '-' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($canVerify)
            <a href="{{ route('kependudukan.warga.verify.form', ['nik_hash' => $warga->nik_hash]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch min-w-touch justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Verifikasi Data</span>
            </a>
            @endif

            @if($canEdit)
            <a href="{{ route('kependudukan.warga.edit', ['nik_hash' => $warga->nik_hash]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface hover:bg-background text-text-primary border border-border text-sm font-medium rounded-sm transition-colors min-h-touch min-w-touch justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                <span>Edit Data</span>
            </a>
            @endif
        </div>
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

    <!-- Main Detail Card with Status Ribbon -->
    <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden {{ $statusRibbonClass }}">
        <!-- Header Ribbon Info -->
        <div class="p-6 sm:p-8 border-b border-border/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-semibold text-text-secondary uppercase tracking-wider block mb-1">Status Verifikasi Kependudukan</span>
                <div class="flex items-center gap-3">
                    <x-verification-badge :status="$warga->verification_status" class="text-sm py-1.5 px-3" />
                    <x-status-badge :status="$warga->status_warga" class="text-sm py-1 px-2.5" />
                </div>
            </div>

            @if($warga->verifiedBy)
            <div class="text-left sm:text-right text-xs text-text-secondary">
                <span>Diverifikasi oleh:</span>
                <p class="font-semibold text-text-primary mt-0.5">{{ $warga->verifiedBy->full_name }}</p>
                <p>{{ $warga->updated_at?->format('d M Y, H:i') }} WIB</p>
            </div>
            @endif
        </div>

        <!-- Rejection Notes Alert if Rejected -->
        @if($warga->verification_status === 'DITOLAK' && $warga->verification_notes)
        <div class="p-6 bg-danger-light/60 border-b border-danger/30 text-danger">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h2 class="text-sm font-bold">Catatan Penolakan Verifikasi:</h2>
                    <p class="text-sm mt-1 leading-relaxed">{{ $warga->verification_notes }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Details Grid -->
        <div class="p-6 sm:p-8 space-y-8">
            <!-- Section 1: Identitas Pribadi -->
            <div>
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wider border-b border-border pb-2 mb-4 font-display">
                    Identitas Pribadi
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-text-secondary text-xs">Nomor Induk Kependudukan (NIK)</dt>
                        <dd class="font-mono font-medium text-text-primary mt-1">{{ $warga->nik_masked }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Nomor Kartu Keluarga (No. KK)</dt>
                        <dd class="font-mono font-medium text-text-primary mt-1">{{ $warga->no_kk_masked }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Nama Lengkap</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->nama_lengkap }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Jenis Kelamin</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Tempat, Tanggal Lahir</dt>
                        <dd class="font-medium text-text-primary mt-1">
                            {{ $warga->tempat_lahir }}, {{ $warga->tanggal_lahir?->isoFormat('D MMMM Y') ?? $warga->tanggal_lahir }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Nomor Telepon / WhatsApp</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->nomor_hp ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Section 2: Kependudukan & Keluarga -->
            <div>
                <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wider border-b border-border pb-2 mb-4 font-display">
                    Informasi Keluarga & Domisili
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-text-secondary text-xs">Hubungan dalam Keluarga</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->status_hubungan_keluarga }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Status Kependudukan</dt>
                        <dd class="mt-1"><x-status-badge :status="$warga->status_warga" /></dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Pekerjaan</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->pekerjaan ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Status Sosio Ekonomi</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->status_sosio_ekonomi ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Wilayah Rukun Tetangga (RT)</dt>
                        <dd class="font-medium text-primary mt-1 font-semibold">
                            RT {{ $warga->kartuKeluarga?->rt_code ?? '-' }} / RW 047
                        </dd>
                    </div>
                    <div>
                        <dt class="text-text-secondary text-xs">Alamat Domisili KK</dt>
                        <dd class="font-medium text-text-primary mt-1">{{ $warga->kartuKeluarga?->alamat_lengkap ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
