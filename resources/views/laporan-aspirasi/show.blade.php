@extends('layouts.dashboard')

@section('title', 'Detail Laporan #' . $laporan->ticket_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-text-secondary">
            <a href="{{ route('laporan-aspirasi.index') }}" class="hover:text-primary transition-colors">Laporan & Aspirasi</a>
            <span>/</span>
            <span class="text-text-primary font-medium">Tiket {{ $laporan->ticket_number }}</span>
        </div>

        <a href="{{ route('laporan-aspirasi.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-xs font-medium rounded-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kembali ke Daftar</span>
        </a>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-md flex items-center gap-3 text-emerald-800 text-xs font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-rose-50 border border-rose-200 rounded-md flex items-center gap-3 text-rose-800 text-xs font-medium">
        <svg class="w-5 h-5 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Main Report Card --}}
    <div class="bg-surface rounded-md border border-border shadow-xs overflow-hidden">
        {{-- Status Ribbon --}}
        <div class="h-1.5 w-full {{ $laporan->current_status === App\Enums\StatusLaporan::RESOLVED ? 'bg-emerald-500' : ($laporan->current_status === App\Enums\StatusLaporan::IN_PROGRESS ? 'bg-blue-500' : ($laporan->current_status === App\Enums\StatusLaporan::CLOSED ? 'bg-gray-400' : 'bg-amber-500')) }}"></div>

        <div class="p-6 space-y-6">
            {{-- Title & Status Header --}}
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-border">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm font-bold text-primary">{{ $laporan->ticket_number }}</span>
                        <span class="text-xs text-text-muted">•</span>
                        <span class="text-xs text-text-secondary">{{ $laporan->submitted_at->translatedFormat('d F Y, H:i') }} WIB</span>
                    </div>
                    <h1 class="text-xl font-display font-bold text-text-primary">{{ $laporan->judul_laporan }}</h1>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $laporan->current_status->badgeClass() }}">
                        {{ $laporan->current_status->label() }}
                    </span>
                </div>
            </div>

            {{-- Metadata Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-background/50 p-4 rounded-md border border-border/80 text-xs">
                <div>
                    <span class="text-text-muted font-medium block">Lokasi / Alamat Kejadian:</span>
                    <span class="text-text-primary font-medium mt-0.5 block">
                        {{ $laporan->lokasi_kejadian ?: 'Tidak ditentukan' }}
                    </span>
                </div>
                <div>
                    <span class="text-text-muted font-medium block">Identitas Pelapor:</span>
                    <span class="text-text-primary font-medium mt-0.5 block">
                        @if($laporan->warga)
                            {{ $laporan->warga->nama_lengkap }} (Warga Terverifikasi)
                        @else
                            Publik / Anonim
                        @endif
                    </span>
                </div>
            </div>

            {{-- Description Text --}}
            <div class="space-y-2">
                <h2 class="text-xs font-bold text-text-secondary uppercase tracking-wider">Isi Laporan / Keluhan:</h2>
                <div class="p-4 bg-background rounded-md border border-border text-xs text-text-primary whitespace-pre-line leading-relaxed">
                    {{ $laporan->teks_keluhan }}
                </div>
            </div>

            {{-- Follow-up Notes (if exists) --}}
            @if($laporan->catatan_tindak_lanjut)
            <div class="space-y-2 p-4 bg-blue-50/50 border border-blue-200/60 rounded-md">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span>Catatan Tindak Lanjut Pengurus</span>
                    </h2>
                    @if($laporan->resolved_at)
                    <span class="text-[11px] text-blue-700">Diselesaikan pada: {{ $laporan->resolved_at->translatedFormat('d M Y, H:i') }} WIB</span>
                    @endif
                </div>
                <p class="text-xs text-blue-950 whitespace-pre-line leading-relaxed">
                    {{ $laporan->catatan_tindak_lanjut }}
                </p>
            </div>
            @endif
        </div>
    </div>

    {{-- Status Timeline --}}
    <div class="bg-surface rounded-md border border-border p-6 shadow-xs space-y-4">
        <h2 class="text-sm font-display font-bold text-text-primary">Alur Penanganan Laporan</h2>
        <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-border">
            {{-- Stage 1: Submitted --}}
            <div class="relative flex items-start gap-3">
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">
                    ✓
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-text-primary">Laporan Diterima</h3>
                    <p class="text-[11px] text-text-secondary">{{ $laporan->submitted_at->translatedFormat('d F Y, H:i') }} WIB</p>
                </div>
            </div>

            {{-- Stage 2: In Progress --}}
            <div class="relative flex items-start gap-3">
                @if($laporan->current_status === App\Enums\StatusLaporan::IN_PROGRESS || $laporan->current_status === App\Enums\StatusLaporan::RESOLVED || $laporan->current_status === App\Enums\StatusLaporan::CLOSED)
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">
                    ✓
                </div>
                @else
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-border text-text-muted flex items-center justify-center text-[10px]">
                    2
                </div>
                @endif
                <div>
                    <h3 class="text-xs font-semibold {{ $laporan->current_status === App\Enums\StatusLaporan::IN_PROGRESS ? 'text-primary' : 'text-text-primary' }}">
                        Proses Penanganan
                    </h3>
                    <p class="text-[11px] text-text-secondary">Dikoordinasikan dan ditindaklanjuti oleh pengurus RT/RW</p>
                </div>
            </div>

            {{-- Stage 3: Resolved --}}
            <div class="relative flex items-start gap-3">
                @if($laporan->current_status === App\Enums\StatusLaporan::RESOLVED || $laporan->current_status === App\Enums\StatusLaporan::CLOSED)
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">
                    ✓
                </div>
                @else
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-border text-text-muted flex items-center justify-center text-[10px]">
                    3
                </div>
                @endif
                <div>
                    <h3 class="text-xs font-semibold {{ $laporan->current_status === App\Enums\StatusLaporan::RESOLVED ? 'text-emerald-700' : 'text-text-primary' }}">
                        Selesai Ditangani
                    </h3>
                    <p class="text-[11px] text-text-secondary">
                        @if($laporan->resolved_at)
                            {{ $laporan->resolved_at->translatedFormat('d F Y, H:i') }} WIB
                        @else
                            Penanganan di lapangan telah diselesaikan dan dicatat
                        @endif
                    </p>
                </div>
            </div>

            {{-- Stage 4: Closed --}}
            <div class="relative flex items-start gap-3">
                @if($laporan->current_status === App\Enums\StatusLaporan::CLOSED)
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-gray-600 text-white flex items-center justify-center text-[10px] font-bold">
                    ✓
                </div>
                @else
                <div class="absolute -left-6 mt-0.5 w-5 h-5 rounded-full bg-border text-text-muted flex items-center justify-center text-[10px]">
                    4
                </div>
                @endif
                <div>
                    <h3 class="text-xs font-semibold text-text-primary">Ditutup (Final)</h3>
                    <p class="text-[11px] text-text-secondary">Laporan telah dikonfirmasi dan ditutup secara resmi</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Update Status Action Form --}}
    @can('updateStatus', $laporan)
    @if(!$laporan->isClosed() && count($laporan->current_status->allowedTransitions()) > 0)
    <div class="bg-surface rounded-md border border-primary/30 p-6 shadow-xs space-y-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <h2 class="text-sm font-display font-bold text-text-primary">Tindak Lanjut & Perbarui Status</h2>
        </div>

        <form method="POST" action="{{ route('laporan-aspirasi.status.update', $laporan->aspirasi_id) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Pilih Status Baru <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($laporan->current_status->allowedTransitions() as $next)
                    <label class="flex items-center gap-3 p-3 bg-background border border-border hover:border-primary rounded-md cursor-pointer transition-colors">
                        <input type="radio" name="current_status" value="{{ $next->value }}" class="text-primary focus:ring-primary" {{ old('current_status') === $next->value ? 'checked' : '' }} required>
                        <div>
                            <span class="text-xs font-bold text-text-primary block">{{ $next->label() }}</span>
                            <span class="text-[11px] text-text-secondary block mt-0.5">
                                @if($next === App\Enums\StatusLaporan::IN_PROGRESS)
                                    Mulai proses penanganan di lapangan.
                                @elseif($next === App\Enums\StatusLaporan::RESOLVED)
                                    Tandai penanganan keluhan telah selesai.
                                @elseif($next === App\Enums\StatusLaporan::CLOSED)
                                    Tutup tiket secara permanen.
                                @endif
                            </span>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('current_status')
                <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">
                    Catatan Tindak Lanjut
                    <span class="text-[11px] text-text-muted font-normal">(Wajib diisi jika status diubah menjadi Selesai Ditangani)</span>
                </label>
                <textarea name="catatan_tindak_lanjut" rows="3" class="w-full text-xs bg-background border border-border rounded-sm p-3 focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Tuliskan tindakan yang telah dilakukan atau konfirmasi penyelesaian...">{{ old('catatan_tindak_lanjut', $laporan->catatan_tindak_lanjut) }}</textarea>
                @error('catatan_tindak_lanjut')
                <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm shadow-xs transition-colors min-h-touch">
                    Simpan Perubahan Status
                </button>
            </div>
        </form>
    </div>
    @endif
    @endcan
</div>
@endsection
