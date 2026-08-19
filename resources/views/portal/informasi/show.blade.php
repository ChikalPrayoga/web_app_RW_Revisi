@extends('layouts.public')

@section('title', $informasi->judul . ' — Informasi Publik')

@section('public-content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-8 pb-16">
    {{-- Breadcrumbs & Back Navigation --}}
    <div class="flex items-center justify-between gap-4 border-b border-border pb-4">
        <nav class="flex items-center gap-2 text-xs text-text-secondary">
            <a href="{{ route('portal.home') }}" class="hover:text-primary transition-colors">Beranda</a>
            <span>/</span>
            <a href="{{ route('portal.informasi.index') }}" class="hover:text-primary transition-colors">Informasi & Agenda</a>
            <span>/</span>
            <span class="text-text-primary font-medium truncate max-w-xs">{{ $informasi->judul }}</span>
        </nav>

        <a href="{{ route('portal.informasi.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-secondary hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kembali</span>
        </a>
    </div>

    {{-- Article Header --}}
    <article class="bg-surface rounded-md border border-border shadow-xs p-6 sm:p-8 space-y-6">
        <header class="space-y-4 border-b border-border pb-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-sm text-xs font-semibold border {{ $informasi->kategori->badgeClass() }}">
                    {{ $informasi->kategori->label() }}
                </span>
                <span class="text-xs text-text-secondary">
                    Dipublikasikan: <strong class="text-text-primary">{{ $informasi->tanggal_publikasi ? $informasi->tanggal_publikasi->translatedFormat('l, d F Y') : '-' }}</strong>
                </span>
                <span class="text-xs text-text-secondary">
                    Oleh: <strong class="text-text-primary">{{ $informasi->publishedBy?->full_name ?? 'Sekretariat RW 047' }}</strong>
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-display font-bold text-text-primary leading-tight">
                {{ $informasi->judul }}
            </h1>
        </header>

        {{-- Special Callout for Agenda --}}
        @if($informasi->isAgenda() && $informasi->tanggal_agenda)
        <div class="bg-warning-light/50 border border-warning/40 rounded-md p-4 sm:p-5 flex items-start gap-4">
            <div class="w-12 h-12 rounded-sm bg-warning text-white flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div class="space-y-1">
                <h3 class="font-display font-bold text-sm text-text-primary">
                    Jadwal Pelaksanaan Kegiatan
                </h3>
                <p class="text-xs sm:text-sm font-semibold text-warning">
                    {{ $informasi->tanggal_agenda->translatedFormat('l, d F Y') }}
                </p>
                <p class="text-xs text-text-secondary">
                    Seluruh warga RW 047 diharapkan dapat berpartisipasi atau memperhatikan waktu pelaksanaan agenda ini.
                </p>
            </div>
        </div>
        @endif

        {{-- Main Article Content --}}
        <div class="prose prose-sm max-w-none text-text-primary text-sm sm:text-base leading-relaxed space-y-4 whitespace-pre-line font-sans">
            {{ $informasi->konten }}
        </div>

        {{-- Article Footer --}}
        <footer class="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-text-secondary">
                Informasi resmi diterbitkan oleh Pengurus RW 047 Kelurahan Bahagia.
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('portal.informasi.index') }}"
                    class="px-4 py-2 text-xs font-semibold bg-background hover:bg-surface border border-border rounded-sm transition-colors">
                    Lihat Informasi Lain
                </a>
            </div>
        </footer>
    </article>

    {{-- Related Information --}}
    @if($relatedInformasi->count() > 0)
    <section class="space-y-4 pt-6">
        <h3 class="font-display font-bold text-xl text-text-primary">
            Informasi Terkait Lainnya
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($relatedInformasi->where('id', '!=', $informasi->id)->take(3) as $related)
            <a href="{{ route('portal.informasi.show', $related->id) }}"
                class="bg-surface p-4 rounded-md border border-border shadow-xs hover:border-primary/50 transition-colors space-y-2 block group">
                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-semibold border {{ $related->kategori->badgeClass() }}">
                    {{ $related->kategori->label() }}
                </span>
                <h4 class="font-display font-semibold text-sm text-text-primary group-hover:text-primary transition-colors line-clamp-2">
                    {{ $related->judul }}
                </h4>
                <p class="text-[11px] text-text-secondary">
                    {{ $related->tanggal_publikasi ? $related->tanggal_publikasi->translatedFormat('d M Y') : '' }}
                </p>
            </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
