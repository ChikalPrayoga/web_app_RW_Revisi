@extends('layouts.public')

@section('title', 'Lacak Status Laporan — Portal Warga RW 047')

@section('public-content')
<div class="max-w-xl mx-auto px-4 sm:px-6 space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">Lacak Laporan & Aspirasi</h1>
        <p class="mt-1 text-sm text-text-secondary">Masukkan nomor tiket laporan yang Anda terima saat menyampaikan pengaduan.</p>
    </div>

    @if(session('error'))
    <div class="p-4 rounded-sm bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-xs">{{ session('error') }}</p>
    </div>
    @endif

    <div class="bg-surface rounded-md border border-border shadow-xs p-6 sm:p-8">
        <form method="GET" action="{{ route('portal.laporan.track_result', '') }}" id="form-lacak-laporan" onsubmit="event.preventDefault(); var ticket = document.getElementById('ticket_number_input').value.trim(); if(ticket) { window.location.href = '{{ url('laporan-aspirasi/lacak') }}/' + encodeURIComponent(ticket); }" class="space-y-5">
            <div>
                <label for="ticket_number_input" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Nomor Tiket Laporan <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="ticket_number_input"
                    name="ticket_number"
                    placeholder="Contoh: LPR-20260818-00001"
                    autocomplete="off"
                    required
                    class="w-full px-3.5 py-2.5 bg-background border border-border rounded-sm text-sm font-mono text-text-primary placeholder-text-muted focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors uppercase"
                >
                <p class="mt-1.5 text-xs text-text-secondary">Format: LPR-YYYYMMDD-XXXXX (contoh: LPR-20260818-00001)</p>
            </div>

            <button
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Cek Status Laporan</span>
            </button>
        </form>
    </div>

    <div class="text-center">
        <p class="text-xs text-text-secondary">Ingin menyampaikan keluhan baru? <a href="{{ route('portal.laporan.create') }}" class="text-primary font-semibold hover:underline">Kirim laporan sekarang</a></p>
    </div>
</div>
@endsection
