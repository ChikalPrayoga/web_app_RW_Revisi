@extends('layouts.public')

@section('public-content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
    {{-- Success Banner --}}
    <div class="p-6 rounded-md bg-success-light border border-success/30 flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-success flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <div>
            <h1 class="font-display font-semibold text-xl text-success">Pengajuan Berhasil Dikirim!</h1>
            <p class="mt-1 text-sm text-text-secondary">Simpan kode pelacakan Anda untuk memantau status pengajuan surat.</p>
        </div>
    </div>

    {{-- Tracking Code Card --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8 text-center">
        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Kode Pelacakan Anda</p>
        <div class="inline-block bg-background border-2 border-primary/30 rounded-sm px-6 py-3 mb-4">
            <span class="font-mono text-2xl font-semibold text-primary tracking-widest">{{ $pengajuan->tracking_code }}</span>
        </div>
        <p class="text-xs text-text-secondary mb-6">Catat atau screenshot kode ini. Kode digunakan untuk melacak status pengajuan Anda.</p>

        {{-- Detail Pengajuan --}}
        <div class="text-left bg-background rounded-sm border border-border p-4 space-y-3 mb-6">
            <div class="flex items-center justify-between text-sm">
                <span class="text-text-secondary">Jenis Surat</span>
                <span class="font-medium text-text-primary">
                    {{ $pengajuan->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Surat Pengantar' : 'Surat Keterangan Domisili' }}
                </span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-text-secondary">Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-semibold bg-warning-light text-warning border border-warning/20">
                    Menunggu Review RT
                </span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-text-secondary">Tanggal Pengajuan</span>
                <span class="font-medium text-text-primary">{{ $pengajuan->tanggal_pengajuan?->format('d M Y, H:i') }} WIB</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('persuratan.public.track_result', ['tracking_code' => $pengajuan->tracking_code]) }}"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Lacak Status Sekarang
            </a>
            <a href="{{ route('persuratan.public.create') }}"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
                Ajukan Surat Lain
            </a>
        </div>
    </div>
</div>
@endsection
