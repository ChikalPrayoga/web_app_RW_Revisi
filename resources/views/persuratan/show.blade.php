@extends('layouts.dashboard')

@section('title', 'Detail Pengajuan Surat')
@section('breadcrumb', 'Detail Pengajuan')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? '';
    $canVerify = !$pengajuan->isFinal() && in_array($roleName, ['KETUA_RT', 'SEKRETARIS_RW', 'KETUA_RW']);

    $statusConfig = [
        'SUBMITTED' => ['label' => 'Menunggu Review RT', 'class' => 'bg-warning-light text-warning border-warning/20', 'dot' => 'bg-warning'],
        'RT_REVIEW' => ['label' => 'Disetujui RT — Menunggu RW', 'class' => 'bg-primary-light text-primary border-primary/20', 'dot' => 'bg-primary'],
        'RW_REVIEW' => ['label' => 'Review Ketua RW', 'class' => 'bg-primary-light text-primary border-primary/20', 'dot' => 'bg-primary'],
        'COMPLETED' => ['label' => 'Selesai — Surat Diterbitkan', 'class' => 'bg-success-light text-success border-success/20', 'dot' => 'bg-success'],
        'REJECTED' => ['label' => 'Ditolak', 'class' => 'bg-danger-light text-danger border-danger/20', 'dot' => 'bg-danger'],
    ];
    $currentStatus = $pengajuan->current_status?->value ?? 'SUBMITTED';
    $sc = $statusConfig[$currentStatus] ?? ['label' => $currentStatus, 'class' => 'bg-background text-text-secondary border-border', 'dot' => 'bg-text-secondary'];
@endphp

<div class="space-y-6">
    {{-- Breadcrumb back --}}
    <div>
        <a href="{{ route('persuratan.index') }}" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="p-4 rounded-sm bg-success-light border border-success/30 text-success flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Status Card --}}
            <div class="bg-surface rounded-md border border-border shadow-sm p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Kode Pelacakan</p>
                        <span class="font-mono text-lg font-semibold text-primary tracking-widest">{{ $pengajuan->tracking_code }}</span>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-sm text-sm font-semibold border {{ $sc['class'] }} self-start">
                        {{ $sc['label'] }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 gap-4 pt-5 border-t border-border">
                    <div>
                        <p class="text-xs text-text-secondary mb-0.5">Jenis Surat</p>
                        <p class="text-sm font-medium text-text-primary">
                            {{ $pengajuan->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Surat Pengantar' : 'Surat Keterangan Domisili' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary mb-0.5">Tanggal Pengajuan</p>
                        <p class="text-sm font-medium text-text-primary">{{ $pengajuan->tanggal_pengajuan?->format('d M Y, H:i') }}</p>
                    </div>
                    @if($pengajuan->tanggal_selesai)
                    <div>
                        <p class="text-xs text-text-secondary mb-0.5">Tanggal Selesai</p>
                        <p class="text-sm font-medium text-text-primary">{{ $pengajuan->tanggal_selesai?->format('d M Y, H:i') }}</p>
                    </div>
                    @endif
                    @if($pengajuan->nomor_surat)
                    <div class="col-span-2 sm:col-span-3">
                        <p class="text-xs text-text-secondary mb-0.5">Nomor Surat Resmi</p>
                        <span class="font-mono text-sm font-semibold text-success">{{ $pengajuan->nomor_surat }}</span>
                    </div>
                    @endif
                </div>

                {{-- Keperluan --}}
                <div class="mt-5 pt-5 border-t border-border">
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Keperluan / Tujuan</p>
                    <p class="text-sm text-text-primary leading-relaxed">{{ $pengajuan->keperluan }}</p>
                </div>

                {{-- Catatan Penolakan --}}
                @if($pengajuan->catatan_penolakan)
                <div class="mt-5 pt-5 border-t border-border">
                    <p class="text-xs font-semibold text-danger uppercase tracking-wider mb-2">Catatan Penolakan</p>
                    <p class="text-sm text-text-primary leading-relaxed italic">{{ $pengajuan->catatan_penolakan }}</p>
                </div>
                @endif
            </div>

            {{-- Pemohon Info --}}
            @if($pengajuan->warga)
            <div class="bg-surface rounded-md border border-border shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-text-primary mb-4">Informasi Pemohon</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-text-secondary mb-0.5">Nama Lengkap</p>
                        <p class="text-sm font-medium text-text-primary">{{ $pengajuan->warga->nama_lengkap }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-text-secondary mb-0.5">Wilayah RT</p>
                        <p class="text-sm font-medium text-text-primary">RT {{ optional($pengajuan->warga->kartuKeluarga)->rt_code ?? '—' }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar: Aksi + Timeline --}}
        <div class="space-y-5">
            {{-- Action Button --}}
            @if($canVerify)
            <div class="bg-surface rounded-md border border-border shadow-sm p-5">
                <h2 class="text-sm font-semibold text-text-primary mb-3">Aksi Verifikasi</h2>
                <a href="{{ route('persuratan.verify.form', $pengajuan->pengajuan_id) }}"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Verifikasi Pengajuan
                </a>
                <p class="mt-2 text-xs text-text-secondary text-center">Anda dapat menyetujui atau menolak pengajuan ini.</p>
            </div>
            @endif

            {{-- Status Timeline --}}
            <div class="bg-surface rounded-md border border-border shadow-sm p-5">
                <h2 class="text-sm font-semibold text-text-primary mb-4">Riwayat Status</h2>
                @if($riwayat->count() > 0)
                <div class="space-y-0">
                    @foreach($riwayat as $index => $log)
                    @php
                        $actionLabels = [
                            'SUBMIT_PENGAJUAN_SURAT' => 'Pengajuan Dikirim',
                            'STATUS_CHANGE_RT_REVIEW' => 'Disetujui Ketua RT',
                            'STATUS_CHANGE_RW_REVIEW' => 'Diteruskan ke Ketua RW',
                            'STATUS_CHANGE_COMPLETED' => 'Surat Diterbitkan',
                            'STATUS_CHANGE_REJECTED' => 'Pengajuan Ditolak',
                        ];
                        $label = $actionLabels[$log->action] ?? $log->action;
                        $isLast = $index === $riwayat->count() - 1;
                        $dotClass = str_contains($log->action, 'COMPLETED') ? 'bg-success' : (str_contains($log->action, 'REJECTED') ? 'bg-danger' : 'bg-primary');
                    @endphp
                    <div class="flex gap-3 {{ !$isLast ? 'pb-4' : '' }}">
                        <div class="flex flex-col items-center">
                            <div class="w-2.5 h-2.5 rounded-full {{ $dotClass }} flex-shrink-0 mt-0.5"></div>
                            @if(!$isLast)
                            <div class="w-0.5 flex-1 bg-border mt-1"></div>
                            @endif
                        </div>
                        <div class="flex-1 pb-0.5">
                            <p class="text-xs font-medium text-text-primary">{{ $label }}</p>
                            @if($log->created_at)
                            <p class="text-[11px] text-text-secondary mt-0.5">{{ $log->created_at->format('d M Y, H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-text-secondary">Belum ada riwayat status.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
