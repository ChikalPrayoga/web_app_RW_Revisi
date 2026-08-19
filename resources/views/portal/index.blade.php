@extends('layouts.public')

@section('title', 'Beranda Portal Warga')

@section('public-content')
<div class="space-y-12 pb-12">
    {{-- Hero Section --}}
    <section class="bg-gradient-to-b from-primary-light/40 to-background py-10 sm:py-16 border-b border-border">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="max-w-3xl space-y-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-sm text-xs font-semibold bg-primary text-white shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Portal Resmi Layanan Warga
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-display font-bold text-text-primary tracking-tight leading-tight">
                    Layanan Warga RW 047 Lebih Cepat, Transparan, dan Terbuka
                </h1>
                <p class="text-base sm:text-lg text-text-secondary leading-relaxed">
                    Ajukan permohonan surat pengantar, pantau proses secara real-time, dan akses seluruh pengumuman resmi lingkungan RW 047 tanpa perlu antre di balai warga.
                </p>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="{{ route('persuratan.public.create') }}"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-sm shadow-sm transition-colors min-h-touch">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Ajukan Surat Pengantar
                    </a>
                    <a href="{{ route('persuratan.public.track') }}"
                        class="inline-flex items-center gap-2 px-5 py-3 bg-surface hover:bg-background text-text-primary text-sm font-semibold border border-border hover:border-primary rounded-sm transition-colors min-h-touch">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Lacak Status Pengajuan
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Layanan Cepat / Fitur Utama --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Card 1: Pengajuan Surat --}}
            <a href="{{ route('persuratan.public.create') }}"
                class="bg-surface p-6 rounded-md border border-border shadow-xs hover:shadow-md hover:border-primary/50 transition-all group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-sm bg-primary-light text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-display font-semibold text-lg text-text-primary group-hover:text-primary transition-colors">
                        Surat Pengantar
                    </h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Permohonan surat pengantar RT/RW untuk pembuatan KTP, KK, SKCK, dan keperluan administrasi lainnya.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center text-xs font-semibold text-primary">
                    <span>Mulai Ajukan</span>
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            {{-- Card 2: Lacak Surat --}}
            <a href="{{ route('persuratan.public.track') }}"
                class="bg-surface p-6 rounded-md border border-border shadow-xs hover:shadow-md hover:border-secondary/50 transition-all group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-sm bg-secondary-light text-secondary flex items-center justify-center group-hover:bg-secondary group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="font-display font-semibold text-lg text-text-primary group-hover:text-secondary transition-colors">
                        Lacak Pengajuan
                    </h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Pantau status persetujuan surat secara langsung menggunakan kode tracking yang diberikan saat pengajuan.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center text-xs font-semibold text-secondary">
                    <span>Lacak Dokumen</span>
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            {{-- Card 3: Pengumuman Warga --}}
            <a href="{{ route('portal.informasi.index', ['kategori' => 'PENGUMUMAN']) }}"
                class="bg-surface p-6 rounded-md border border-border shadow-xs hover:shadow-md hover:border-primary/50 transition-all group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-sm bg-primary-light text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                        </svg>
                    </div>
                    <h3 class="font-display font-semibold text-lg text-text-primary group-hover:text-primary transition-colors">
                        Pengumuman Resmi
                    </h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Informasi terkini mengenai edaran RT/RW, keamanan lingkungan, dan pemberitahuan penting pengurus.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center text-xs font-semibold text-primary">
                    <span>Baca Pengumuman</span>
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            {{-- Card 4: Agenda Lingkungan --}}
            <a href="{{ route('portal.informasi.index', ['kategori' => 'AGENDA']) }}"
                class="bg-surface p-6 rounded-md border border-border shadow-xs hover:shadow-md hover:border-warning/50 transition-all group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-sm bg-warning-light text-warning flex items-center justify-center group-hover:bg-warning group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-display font-semibold text-lg text-text-primary group-hover:text-warning transition-colors">
                        Agenda Kegiatan
                    </h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Jadwal kegiatan kerja bakti, posyandu lansia & balita, rapat warga, dan perayaan hari besar di RW 047.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-border flex items-center text-xs font-semibold text-warning">
                    <span>Lihat Jadwal</span>
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
        </div>
    </section>

    {{-- Main Content Grid: Informasi Terbaru & Agenda Mendatang --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Kolom Kiri: Informasi & Berita Terbaru (2 Kolom) --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-display font-bold text-2xl text-text-primary">Informasi & Pengumuman Terbaru</h2>
                        <p class="text-xs text-text-secondary mt-0.5">Kabar dan edaran resmi dari Pengurus RW 047</p>
                    </div>
                    <a href="{{ route('portal.informasi.index') }}"
                        class="text-xs font-semibold text-primary hover:text-primary-dark transition-colors flex items-center gap-1">
                        Lihat Semua
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($latestInformasi as $item)
                    <article class="bg-surface p-5 rounded-md border border-border shadow-xs hover:border-primary/40 transition-colors space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $item->kategori->badgeClass() }}">
                                {{ $item->kategori->label() }}
                            </span>
                            <span class="text-xs text-text-secondary">
                                {{ $item->tanggal_publikasi ? $item->tanggal_publikasi->translatedFormat('d F Y') : '' }}
                            </span>
                        </div>

                        <h3 class="font-display font-semibold text-lg text-text-primary hover:text-primary transition-colors">
                            <a href="{{ route('portal.informasi.show', $item->id) }}">
                                {{ $item->judul }}
                            </a>
                        </h3>

                        <p class="text-xs text-text-secondary leading-relaxed line-clamp-2">
                            {{ Str::limit(strip_tags($item->konten), 180) }}
                        </p>

                        <div class="pt-2 flex items-center justify-between text-xs border-t border-border/50">
                            <span class="text-[11px] text-text-secondary">
                                Oleh: <span class="font-medium text-text-primary">{{ $item->publishedBy?->full_name ?? 'Pengurus RW' }}</span>
                            </span>
                            <a href="{{ route('portal.informasi.show', $item->id) }}" class="font-semibold text-primary hover:underline">
                                Selengkapnya &rarr;
                            </a>
                        </div>
                    </article>
                    @empty
                    <div class="bg-surface p-8 rounded-md border border-border text-center text-text-secondary text-sm">
                        Belum ada pengumuman yang dipublikasikan saat ini.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Kolom Kanan: Agenda Kegiatan & Fast Track (1 Kolom) --}}
            <div class="space-y-6">
                {{-- Agenda Kegiatan --}}
                <div class="bg-surface rounded-md border border-border shadow-xs p-5 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-border">
                        <h3 class="font-display font-semibold text-base text-text-primary flex items-center gap-2">
                            <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Agenda RW Terdekat
                        </h3>
                        <a href="{{ route('portal.informasi.index', ['kategori' => 'AGENDA']) }}" class="text-xs font-medium text-primary hover:underline">
                            Semua
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($upcomingAgendas as $agenda)
                        <a href="{{ route('portal.informasi.show', $agenda->id) }}" class="block p-3 rounded-sm bg-background border border-border hover:border-warning/50 transition-colors group">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-sm bg-warning-light text-warning flex flex-col items-center justify-center flex-shrink-0 font-display">
                                    <span class="text-[10px] font-bold uppercase leading-none">
                                        {{ $agenda->tanggal_agenda ? $agenda->tanggal_agenda->format('M') : 'AGEN' }}
                                    </span>
                                    <span class="text-sm font-bold leading-none mt-0.5">
                                        {{ $agenda->tanggal_agenda ? $agenda->tanggal_agenda->format('d') : '-' }}
                                    </span>
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-semibold text-text-primary group-hover:text-warning transition-colors truncate">
                                        {{ $agenda->judul }}
                                    </h4>
                                    <p class="text-[11px] text-text-secondary mt-0.5">
                                        {{ $agenda->tanggal_agenda ? $agenda->tanggal_agenda->translatedFormat('l, d F Y') : 'Jadwal Menyusul' }}
                                    </p>
                                </div>
                            </div>
                        </a>
                        @empty
                        <p class="text-xs text-text-secondary text-center py-4">
                            Tidak ada agenda kegiatan terdekat.
                        </p>
                        @endforelse
                    </div>
                </div>

                {{-- Fast Track Surat Box --}}
                <div class="bg-primary-light/50 rounded-md border border-primary/30 p-5 space-y-3">
                    <h3 class="font-display font-semibold text-sm text-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Lacak Surat Pengantar
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Sudah mengajukan surat? Masukkan kode tracking untuk mengetahui proses verifikasi Ketua RT & RW.
                    </p>
                    <form method="GET" action="{{ route('persuratan.public.track') }}" class="space-y-2">
                        <input type="text" name="tracking_code" placeholder="Contoh: SRT-20260818-0001"
                            class="w-full px-3 py-2 text-xs bg-surface border border-border rounded-sm font-mono focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" />
                        <button type="submit"
                            class="w-full py-2 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm transition-colors">
                            Cari Status Pengajuan
                        </button>
                    </form>
                </div>

                {{-- Fast Track Laporan Box --}}
                <div class="bg-blue-50/70 rounded-md border border-blue-200/80 p-5 space-y-3">
                    <h3 class="font-display font-semibold text-sm text-blue-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        Lacak Laporan & Aspirasi
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Pantau status pengaduan dan catatan tindak lanjut pengurus menggunakan nomor tiket.
                    </p>
                    <form method="GET" action="{{ route('portal.laporan.track_result', '') }}" onsubmit="event.preventDefault(); var t = this.ticket_number.value.trim(); if(t) { window.location.href = '{{ url('laporan-aspirasi/lacak') }}/' + encodeURIComponent(t); }" class="space-y-2">
                        <input type="text" name="ticket_number" placeholder="Contoh: LPR-20260818-00001"
                            class="w-full px-3 py-2 text-xs bg-surface border border-border rounded-sm font-mono focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary" />
                        <button type="submit"
                            class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm transition-colors">
                            Cari Tiket Laporan
                        </button>
                    </form>
                    <div class="pt-1 text-center">
                        <a href="{{ route('portal.laporan.create') }}" class="text-[11px] font-semibold text-primary hover:underline">
                            + Sampaikan Laporan Baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
