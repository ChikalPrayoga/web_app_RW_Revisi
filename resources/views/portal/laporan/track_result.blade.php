@extends('layouts.public')

@section('title', 'Status Laporan #' . $laporan->ticket_number . ' — Portal Warga RW 047')

@section('public-content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
    {{-- Back & Header --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('portal.laporan.track') }}" class="inline-flex items-center gap-1 text-xs text-text-secondary hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Cari Tiket Lain</span>
        </a>
        <span class="text-xs text-text-muted">Status Terakhir: {{ $laporan->updated_at->translatedFormat('d M Y, H:i') }} WIB</span>
    </div>

    {{-- Main Status Card --}}
    <div class="bg-surface rounded-md border border-border shadow-xs overflow-hidden">
        {{-- Status Ribbon --}}
        <div class="h-1.5 w-full {{ $laporan->current_status === App\Enums\StatusLaporan::RESOLVED ? 'bg-emerald-500' : ($laporan->current_status === App\Enums\StatusLaporan::IN_PROGRESS ? 'bg-blue-500' : ($laporan->current_status === App\Enums\StatusLaporan::CLOSED ? 'bg-gray-400' : 'bg-amber-500')) }}"></div>

        <div class="p-6 sm:p-8 space-y-6">
            {{-- Top Info --}}
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-border">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Nomor Tiket:</p>
                    <p class="font-mono text-xl font-extrabold text-primary">{{ $laporan->ticket_number }}</p>
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $laporan->current_status->badgeClass() }}">
                        {{ $laporan->current_status->label() }}
                    </span>
                </div>
            </div>

            {{-- Report Summary --}}
            <div class="space-y-3">
                <div>
                    <h2 class="text-xs font-medium text-text-secondary">Judul Laporan:</h2>
                    <p class="text-base font-bold text-text-primary mt-0.5">{{ $laporan->judul_laporan }}</p>
                </div>

                @if($laporan->lokasi_kejadian)
                <div>
                    <h2 class="text-xs font-medium text-text-secondary">Lokasi Kejadian:</h2>
                    <p class="text-xs text-text-primary mt-0.5 flex items-center gap-1">
                        <svg class="w-4 h-4 text-text-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ $laporan->lokasi_kejadian }}</span>
                    </p>
                </div>
                @endif
            </div>

            {{-- Follow up notes --}}
            @if($laporan->catatan_tindak_lanjut)
            <div class="p-4 bg-blue-50/60 border border-blue-200 rounded-md space-y-1">
                <h3 class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Catatan Tindak Lanjut dari Pengurus RW</span>
                </h3>
                <p class="text-xs text-blue-950 whitespace-pre-line leading-relaxed">
                    {{ $laporan->catatan_tindak_lanjut }}
                </p>
                @if($laporan->resolved_at)
                <p class="text-[11px] text-blue-700 pt-1">
                    Diselesaikan pada: {{ $laporan->resolved_at->translatedFormat('d F Y, H:i') }} WIB
                </p>
                @endif
            </div>
            @endif

            {{-- Timeline Progress --}}
            <div class="pt-4 border-t border-border space-y-4">
                <h3 class="text-xs font-bold text-text-secondary uppercase tracking-wider">Tahapan Penanganan:</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    {{-- 1: SUBMITTED --}}
                    <div class="p-3 rounded-sm border {{ $laporan->current_status !== null ? 'bg-emerald-50/60 border-emerald-300' : 'bg-background border-border' }}">
                        <div class="text-[10px] font-bold {{ $laporan->current_status !== null ? 'text-emerald-800' : 'text-text-muted' }}">1. Diterima</div>
                        <div class="text-[11px] text-text-secondary mt-0.5">Laporan tersimpan di sistem</div>
                    </div>

                    {{-- 2: IN_PROGRESS --}}
                    <div class="p-3 rounded-sm border {{ in_array($laporan->current_status, [App\Enums\StatusLaporan::IN_PROGRESS, App\Enums\StatusLaporan::RESOLVED, App\Enums\StatusLaporan::CLOSED]) ? 'bg-blue-50/60 border-blue-300' : 'bg-background border-border opacity-60' }}">
                        <div class="text-[10px] font-bold {{ in_array($laporan->current_status, [App\Enums\StatusLaporan::IN_PROGRESS, App\Enums\StatusLaporan::RESOLVED, App\Enums\StatusLaporan::CLOSED]) ? 'text-blue-800' : 'text-text-muted' }}">2. Ditangani</div>
                        <div class="text-[11px] text-text-secondary mt-0.5">Koordinasi pengurus RT/RW</div>
                    </div>

                    {{-- 3: RESOLVED --}}
                    <div class="p-3 rounded-sm border {{ in_array($laporan->current_status, [App\Enums\StatusLaporan::RESOLVED, App\Enums\StatusLaporan::CLOSED]) ? 'bg-emerald-50/60 border-emerald-300' : 'bg-background border-border opacity-60' }}">
                        <div class="text-[10px] font-bold {{ in_array($laporan->current_status, [App\Enums\StatusLaporan::RESOLVED, App\Enums\StatusLaporan::CLOSED]) ? 'text-emerald-800' : 'text-text-muted' }}">3. Selesai</div>
                        <div class="text-[11px] text-text-secondary mt-0.5">Penanganan selesai</div>
                    </div>

                    {{-- 4: CLOSED --}}
                    <div class="p-3 rounded-sm border {{ $laporan->current_status === App\Enums\StatusLaporan::CLOSED ? 'bg-gray-100 border-gray-400' : 'bg-background border-border opacity-60' }}">
                        <div class="text-[10px] font-bold {{ $laporan->current_status === App\Enums\StatusLaporan::CLOSED ? 'text-gray-900' : 'text-text-muted' }}">4. Ditutup</div>
                        <div class="text-[11px] text-text-secondary mt-0.5">Tiket ditutup final</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
