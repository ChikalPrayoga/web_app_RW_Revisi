@extends('layouts.public')

@section('public-content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-semibold text-text-primary">Status Pengajuan</h1>
            <p class="mt-0.5 text-sm text-text-secondary">Detail dan riwayat status pengajuan surat Anda</p>
        </div>
        <a href="{{ route('persuratan.public.track') }}" class="text-xs font-medium text-text-secondary hover:text-primary flex items-center gap-1 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Lacak Kode Lain
        </a>
    </div>

    {{-- Tracking Code Badge --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Kode Pelacakan</p>
                <span class="font-mono text-xl font-semibold text-primary tracking-widest">{{ $pengajuan->tracking_code }}</span>
            </div>
            @php
                $statusConfig = [
                    'SUBMITTED' => ['label' => 'Menunggu Review RT', 'class' => 'bg-warning-light text-warning border-warning/20'],
                    'RT_REVIEW' => ['label' => 'Disetujui RT — Menunggu RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
                    'RW_REVIEW' => ['label' => 'Review RW Ketua', 'class' => 'bg-primary-light text-primary border-primary/20'],
                    'COMPLETED' => ['label' => 'Selesai', 'class' => 'bg-success-light text-success border-success/20'],
                    'REJECTED' => ['label' => 'Ditolak', 'class' => 'bg-danger-light text-danger border-danger/20'],
                ];
                $currentStatus = $pengajuan->current_status?->value ?? 'SUBMITTED';
                $config = $statusConfig[$currentStatus] ?? ['label' => $currentStatus, 'class' => 'bg-background text-text-secondary border-border'];
            @endphp
            <span class="inline-flex items-center px-3 py-1.5 rounded-sm text-sm font-semibold border {{ $config['class'] }}">
                {{ $config['label'] }}
            </span>
        </div>

        {{-- Detail Info --}}
        <div class="mt-4 pt-4 border-t border-border grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Jenis Surat</p>
                <p class="text-sm font-medium text-text-primary">
                    {{ $pengajuan->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Surat Pengantar' : 'Surat Keterangan Domisili' }}
                </p>
            </div>
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Tanggal Pengajuan</p>
                <p class="text-sm font-medium text-text-primary">{{ $pengajuan->tanggal_pengajuan?->format('d M Y') }}</p>
            </div>
            @if($pengajuan->tanggal_selesai)
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Tanggal Selesai</p>
                <p class="text-sm font-medium text-text-primary">{{ $pengajuan->tanggal_selesai?->format('d M Y') }}</p>
            </div>
            @endif
        </div>

        {{-- Nomor Surat (jika COMPLETED) --}}
        @if($pengajuan->nomor_surat)
        <div class="mt-4 pt-4 border-t border-border">
            <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1">Nomor Surat Resmi</p>
            <span class="font-mono text-base font-semibold text-success">{{ $pengajuan->nomor_surat }}</span>
            <p class="mt-1 text-xs text-text-secondary">Hubungi pengurus RW 047 untuk pengambilan surat.</p>
        </div>
        @endif

        {{-- Catatan Penolakan (jika REJECTED) --}}
        @if($currentStatus === 'REJECTED' && $pengajuan->catatan_penolakan)
        <div class="mt-4 p-4 bg-danger-light border border-danger/20 rounded-sm">
            <p class="text-xs font-semibold text-danger uppercase tracking-wider mb-1.5">Alasan Penolakan</p>
            <p class="text-sm text-text-primary">{{ $pengajuan->catatan_penolakan }}</p>
            <p class="mt-2 text-xs text-text-secondary">Anda dapat mengajukan surat kembali setelah memenuhi persyaratan yang diminta.</p>
        </div>
        @endif
    </div>

    {{-- Status Timeline --}}
    @if($riwayat->count() > 0)
    <div class="bg-surface rounded-md border border-border shadow-sm p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-text-primary mb-4">Riwayat Status</h2>
        <div class="space-y-0">
            @foreach($riwayat as $index => $log)
            @php
                $actionLabels = [
                    'SUBMIT_PENGAJUAN_SURAT' => 'Pengajuan Diterima',
                    'STATUS_CHANGE_RT_REVIEW' => 'Disetujui Ketua RT',
                    'STATUS_CHANGE_RW_REVIEW' => 'Diteruskan ke Ketua RW',
                    'STATUS_CHANGE_COMPLETED' => 'Surat Selesai Diterbitkan',
                    'STATUS_CHANGE_REJECTED' => 'Pengajuan Ditolak',
                ];
                $label = $actionLabels[$log->action] ?? $log->action;
                $isLast = $index === $riwayat->count() - 1;
                $isSuccess = str_contains($log->action, 'COMPLETED');
                $isReject = str_contains($log->action, 'REJECTED');
                $dotClass = $isSuccess ? 'bg-success' : ($isReject ? 'bg-danger' : 'bg-primary');
            @endphp
            <div class="flex gap-4 {{ !$isLast ? 'pb-4' : '' }}">
                <div class="flex flex-col items-center">
                    <div class="w-3 h-3 rounded-full {{ $dotClass }} flex-shrink-0 mt-0.5 ring-2 ring-{{ $isSuccess ? 'success' : ($isReject ? 'danger' : 'primary') }}/20"></div>
                    @if(!$isLast)
                    <div class="w-0.5 flex-1 bg-border mt-1"></div>
                    @endif
                </div>
                <div class="flex-1 pb-1">
                    <p class="text-sm font-medium text-text-primary">{{ $label }}</p>
                    @if($log->created_at)
                    <p class="text-xs text-text-secondary mt-0.5">{{ $log->created_at->format('d M Y, H:i') }} WIB</p>
                    @endif
                    @if(data_get($log->new_values, 'catatan_penolakan'))
                    <p class="mt-1 text-xs text-danger italic">{{ data_get($log->new_values, 'catatan_penolakan') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('persuratan.public.create') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
            Ajukan Surat Baru
        </a>
        <a href="{{ route('persuratan.public.track') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
            Lacak Kode Lain
        </a>
    </div>
</div>
@endsection
