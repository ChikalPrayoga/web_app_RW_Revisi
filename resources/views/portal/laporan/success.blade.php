@extends('layouts.public')

@section('title', 'Laporan Berhasil Terkirim — Portal Warga RW 047')

@section('public-content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-8 text-center space-y-6">
    {{-- Success Icon --}}
    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-xs">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
    </div>

    {{-- Header Message --}}
    <div class="space-y-2">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-text-primary">
            Laporan Anda Berhasil Terkirim!
        </h1>
        <p class="text-sm text-text-secondary max-w-md mx-auto">
            Terima kasih atas kepedulian Anda. Laporan telah tercatat di sistem dan segera ditindaklanjuti oleh pengurus RW 047.
        </p>
    </div>

    {{-- Ticket Box --}}
    <div class="bg-surface rounded-md border-2 border-primary/20 p-6 space-y-3 shadow-xs max-w-md mx-auto">
        <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Nomor Tiket Laporan Anda:</p>
        <div class="flex items-center justify-center gap-2">
            <span class="font-mono text-xl sm:text-2xl font-extrabold text-primary tracking-wide">
                {{ $ticket_number }}
            </span>
        </div>
        <p class="text-[11px] text-text-muted">
            Simpan nomor tiket ini untuk melacak status dan catatan penanganan laporan Anda kapan saja tanpa login.
        </p>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4 max-w-md mx-auto">
        <a
            href="{{ route('portal.laporan.track_result', ['ticket_number' => $ticket_number]) }}"
            class="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm shadow-xs transition-colors text-center"
        >
            Lacak Status Laporan
        </a>
        <a
            href="{{ route('portal.home') }}"
            class="w-full sm:w-auto px-6 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary text-xs font-medium rounded-sm transition-colors text-center"
        >
            Kembali ke Beranda
        </a>
    </div>
</div>
@endsection
