<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal Warga') — SIM Layanan Warga RW 047</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans text-text-primary antialiased flex flex-col">
    {{-- Header / Navbar Publik --}}
    <header class="bg-surface border-b border-border sticky top-0 z-40 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            {{-- Branding --}}
            <a href="{{ route('portal.home') }}" class="flex items-center gap-3 group">
                <div class="w-9 h-9 rounded-sm bg-primary flex items-center justify-center text-white shadow-xs group-hover:bg-primary-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <span class="font-display font-bold text-base text-primary block leading-tight">SIM Warga</span>
                    <span class="text-xs text-text-secondary block">RW 047 Kelurahan Bahagia</span>
                </div>
            </a>

            {{-- Desktop Navigation --}}
            <nav class="hidden md:flex items-center gap-1.5">
                <a href="{{ route('portal.home') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('portal.home') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Beranda
                </a>
                <a href="{{ route('portal.informasi.index') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('portal.informasi.*') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Informasi & Agenda
                </a>
                <a href="{{ route('persuratan.public.create') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('persuratan.public.create') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Ajukan Surat
                </a>
                <a href="{{ route('persuratan.public.track') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('persuratan.public.track*') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Lacak Surat
                </a>
                <a href="{{ route('portal.laporan.create') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('portal.laporan.create') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Laporan Warga
                </a>
                <a href="{{ route('portal.laporan.track') }}"
                    class="px-3 py-2 text-sm font-medium rounded-sm transition-colors {{ request()->routeIs('portal.laporan.track*') ? 'bg-primary-light text-primary font-semibold' : 'text-text-secondary hover:text-text-primary hover:bg-background' }}">
                    Lacak Laporan
                </a>
            </nav>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-sm bg-primary text-white hover:bg-primary-dark shadow-xs transition-colors min-h-touch">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-sm text-text-secondary hover:text-text-primary border border-border hover:border-primary transition-colors min-h-touch">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Masuk Pengurus</span>
                    </a>
                @endauth
            </div>
        </div>

        {{-- Mobile Navigation Bar --}}
        <div class="md:hidden border-t border-border bg-surface px-4 py-2 flex items-center justify-around overflow-x-auto text-xs">
            <a href="{{ route('portal.home') }}" class="px-2.5 py-1.5 rounded-sm {{ request()->routeIs('portal.home') ? 'font-semibold text-primary bg-primary-light' : 'text-text-secondary' }}">
                Beranda
            </a>
            <a href="{{ route('portal.informasi.index') }}" class="px-2.5 py-1.5 rounded-sm {{ request()->routeIs('portal.informasi.*') ? 'font-semibold text-primary bg-primary-light' : 'text-text-secondary' }}">
                Informasi
            </a>
            <a href="{{ route('persuratan.public.create') }}" class="px-2.5 py-1.5 rounded-sm {{ request()->routeIs('persuratan.public.create') ? 'font-semibold text-primary bg-primary-light' : 'text-text-secondary' }}">
                Surat
            </a>
            <a href="{{ route('portal.laporan.create') }}" class="px-2.5 py-1.5 rounded-sm {{ request()->routeIs('portal.laporan.create') ? 'font-semibold text-primary bg-primary-light' : 'text-text-secondary' }}">
                Laporan
            </a>
            <a href="{{ route('portal.laporan.track') }}" class="px-2.5 py-1.5 rounded-sm {{ request()->routeIs('portal.laporan.track*') ? 'font-semibold text-primary bg-primary-light' : 'text-text-secondary' }}">
                Lacak
            </a>
        </div>
    </header>

    {{-- Main Body --}}
    <main class="flex-1">
        @yield('public-content')
    </main>

    {{-- Footer Publik --}}
    <footer class="border-t border-border bg-surface mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <h4 class="font-display font-semibold text-primary text-base mb-2">SIM Layanan Warga RW 047</h4>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Sistem Informasi Manajemen Layanan Warga RW 047. Memberikan kemudahan pelayanan administrasi kependudukan, transparansi persuratan, dan keterbukaan informasi publik bagi seluruh warga.
                    </p>
                </div>
                <div>
                    <h4 class="font-display font-semibold text-text-primary text-sm mb-2">Tautan Cepat</h4>
                    <ul class="space-y-1.5 text-xs text-text-secondary">
                        <li><a href="{{ route('portal.informasi.index', ['kategori' => 'PENGUMUMAN']) }}" class="hover:text-primary transition-colors">Pengumuman Warga</a></li>
                        <li><a href="{{ route('portal.informasi.index', ['kategori' => 'AGENDA']) }}" class="hover:text-primary transition-colors">Agenda Kegiatan RW</a></li>
                        <li><a href="{{ route('persuratan.public.create') }}" class="hover:text-primary transition-colors">Permohonan Surat Pengantar</a></li>
                        <li><a href="{{ route('persuratan.public.track') }}" class="hover:text-primary transition-colors">Pelacakan Status Surat</a></li>
                        <li><a href="{{ route('portal.laporan.create') }}" class="hover:text-primary transition-colors">Pengaduan & Aspirasi Warga</a></li>
                        <li><a href="{{ route('portal.laporan.track') }}" class="hover:text-primary transition-colors">Lacak Status Laporan</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-display font-semibold text-text-primary text-sm mb-2">Sekretariat RW 047</h4>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Balai Warga RW 047, Kelurahan Bahagia.<br>
                        Pelayanan Surat Pengantar: Setiap Hari Kerja.<br>
                        Konsultasi Pengurus: Melalui Ketua RT masing-masing.
                    </p>
                </div>
            </div>
            <div class="pt-6 mt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between text-xs text-text-secondary gap-2">
                <span>© {{ date('Y') }} Rukun Warga 047. Hak Cipta Dilindungi.</span>
                <span>Portal Warga Digital Terintegrasi</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
