@extends('layouts.public')

@section('title', 'Informasi & Agenda Publik')

@section('public-content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-8 pb-12">
    {{-- Header Page --}}
    <div class="space-y-2 border-b border-border pb-6">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-text-primary">
            Informasi, Berita & Agenda RW 047
        </h1>
        <p class="text-xs sm:text-sm text-text-secondary">
            Katalog pengumuman resmi, kabar lingkungan, dan jadwal kegiatan warga RW 047 Kelurahan Bahagia.
        </p>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        {{-- Category Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <a href="{{ route('portal.informasi.index', request()->only('search')) }}"
                class="px-3 py-1.5 text-xs font-semibold rounded-sm whitespace-nowrap transition-colors {{ empty($activeKategori) ? 'bg-primary text-white shadow-xs' : 'bg-surface text-text-secondary hover:text-text-primary border border-border' }}">
                Semua Kategori
            </a>
            @foreach($kategoris as $kat)
            <a href="{{ route('portal.informasi.index', array_merge(request()->only('search'), ['kategori' => $kat->value])) }}"
                class="px-3 py-1.5 text-xs font-semibold rounded-sm whitespace-nowrap transition-colors {{ $activeKategori === $kat->value ? 'bg-primary text-white shadow-xs' : 'bg-surface text-text-secondary hover:text-text-primary border border-border' }}">
                {{ $kat->label() }}
            </a>
            @endforeach
        </div>

        {{-- Search Box --}}
        <form method="GET" action="{{ route('portal.informasi.index') }}" class="flex items-center gap-2">
            @if(!empty($activeKategori))
                <input type="hidden" name="kategori" value="{{ $activeKategori }}" />
            @endif
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari judul / isi informasi..."
                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-surface border border-border rounded-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" />
                <svg class="w-4 h-4 text-text-secondary absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-sm hover:bg-primary-dark transition-colors">
                Cari
            </button>
            @if(!empty($search) || !empty($activeKategori))
            <a href="{{ route('portal.informasi.index') }}" class="px-2.5 py-1.5 text-xs text-text-secondary hover:text-danger border border-border rounded-sm hover:border-danger/30 transition-colors">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($informasiList as $item)
        <article class="bg-surface rounded-md border border-border shadow-xs hover:border-primary/50 hover:shadow-md transition-all flex flex-col justify-between overflow-hidden">
            <div class="p-5 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $item->kategori->badgeClass() }}">
                        {{ $item->kategori->label() }}
                    </span>
                    <span class="text-[11px] text-text-secondary">
                        {{ $item->tanggal_publikasi ? $item->tanggal_publikasi->translatedFormat('d M Y') : '' }}
                    </span>
                </div>

                <h2 class="font-display font-semibold text-lg text-text-primary hover:text-primary transition-colors leading-snug">
                    <a href="{{ route('portal.informasi.show', $item->id) }}">
                        {{ $item->judul }}
                    </a>
                </h2>

                @if($item->isAgenda() && $item->tanggal_agenda)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-warning-light text-warning text-xs font-medium rounded-sm border border-warning/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Jadwal: {{ $item->tanggal_agenda->translatedFormat('l, d F Y') }}</span>
                </div>
                @endif

                <p class="text-xs text-text-secondary leading-relaxed line-clamp-3">
                    {{ Str::limit(strip_tags($item->konten), 160) }}
                </p>
            </div>

            <div class="px-5 py-3 bg-background border-t border-border flex items-center justify-between text-xs">
                <span class="text-[11px] text-text-secondary">
                    {{ $item->publishedBy?->full_name ?? 'Sekretariat RW' }}
                </span>
                <a href="{{ route('portal.informasi.show', $item->id) }}" class="font-semibold text-primary hover:underline flex items-center gap-1">
                    <span>Baca</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </article>
        @empty
        <div class="col-span-full py-16 text-center space-y-3 bg-surface rounded-md border border-border">
            <div class="w-12 h-12 rounded-sm bg-background text-text-secondary mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="font-display font-semibold text-base text-text-primary">Tidak Ada Informasi Ditemukan</h3>
            <p class="text-xs text-text-secondary max-w-sm mx-auto">
                Tidak ada data informasi publik yang sesuai dengan filter atau kata kunci pencarian Anda.
            </p>
            <a href="{{ route('portal.informasi.index') }}" class="inline-flex items-center text-xs font-semibold text-primary hover:underline">
                Lihat Semua Informasi &rarr;
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($informasiList->hasPages())
    <div class="pt-4 border-t border-border">
        {{ $informasiList->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
