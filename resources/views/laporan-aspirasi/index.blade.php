@extends('layouts.dashboard')

@section('title', 'Laporan & Aspirasi Warga')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-text-primary">Laporan & Aspirasi Warga</h1>
            <p class="text-xs text-text-secondary mt-1">Daftar pengaduan, keluhan lingkungan, dan aspirasi yang disampaikan oleh warga RW 047</p>
        </div>
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

    {{-- Filter Toolbar --}}
    <div class="bg-surface p-4 rounded-md border border-border shadow-xs">
        <form method="GET" action="{{ route('laporan-aspirasi.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">Status Penanganan</label>
                <select name="current_status" class="w-full text-xs bg-background border border-border rounded-sm px-2.5 py-1.5 focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    @foreach(App\Enums\StatusLaporan::cases() as $st)
                    <option value="{{ $st->value }}" {{ request('current_status') === $st->value ? 'selected' : '' }}>
                        {{ $st->label() }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">Tampilkan per Halaman</label>
                <select name="per_page" class="w-full text-xs bg-background border border-border rounded-sm px-2.5 py-1.5 focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="15" {{ request('per_page') == '15' ? 'selected' : '' }}>15 data</option>
                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 data</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 data</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full sm:w-auto px-4 py-1.5 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm transition-colors min-h-touch">
                    Terapkan Filter
                </button>
                @if(request()->hasAny(['current_status', 'per_page']))
                <a href="{{ route('laporan-aspirasi.index') }}" class="px-3 py-1.5 bg-surface hover:bg-background border border-border text-text-secondary text-xs font-medium rounded-sm transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Data Table (Desktop) & Cards (Mobile) --}}
    <div class="bg-surface rounded-md border border-border shadow-xs overflow-hidden">
        {{-- Desktop View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-border bg-background/50 text-[11px] font-semibold text-text-secondary uppercase tracking-wider">
                        <th class="py-3 px-4">No. Tiket</th>
                        <th class="py-3 px-4">Judul & Lokasi</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Waktu Masuk</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border text-xs">
                    @forelse($laporan as $item)
                    <tr class="hover:bg-background/40 transition-colors">
                        <td class="py-3.5 px-4 font-mono font-bold text-primary">
                            {{ $item->ticket_number }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-text-primary">{{ $item->judul_laporan }}</div>
                            @if($item->lokasi_kejadian)
                            <div class="text-[11px] text-text-secondary mt-0.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-text-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>{{ $item->lokasi_kejadian }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $item->current_status->badgeClass() }}">
                                {{ $item->current_status->label() }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-text-secondary">
                            {{ $item->submitted_at->translatedFormat('d M Y, H:i') }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('laporan-aspirasi.show', $item->aspirasi_id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-surface hover:bg-background border border-border text-primary hover:text-primary-dark font-medium rounded-sm text-xs transition-colors">
                                <span>Detail</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-text-muted">
                            <svg class="w-12 h-12 mx-auto text-text-muted/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="font-medium text-xs">Belum ada laporan atau aspirasi warga.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden divide-y divide-border">
            @forelse($laporan as $item)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="font-mono text-xs font-bold text-primary">{{ $item->ticket_number }}</span>
                        <h3 class="font-medium text-text-primary text-sm mt-0.5">{{ $item->judul_laporan }}</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $item->current_status->badgeClass() }}">
                        {{ $item->current_status->label() }}
                    </span>
                </div>

                @if($item->lokasi_kejadian)
                <p class="text-xs text-text-secondary flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-text-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>{{ $item->lokasi_kejadian }}</span>
                </p>
                @endif

                <div class="flex items-center justify-between pt-2 border-t border-border/60 text-xs">
                    <span class="text-text-muted text-[11px]">{{ $item->submitted_at->translatedFormat('d M Y, H:i') }}</span>
                    <a href="{{ route('laporan-aspirasi.show', $item->aspirasi_id) }}" class="inline-flex items-center gap-1 text-primary font-semibold hover:underline">
                        <span>Lihat Detail</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-text-muted text-xs">
                Belum ada laporan atau aspirasi warga.
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($laporan->hasPages())
        <div class="p-4 border-t border-border bg-background/30">
            {{ $laporan->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
