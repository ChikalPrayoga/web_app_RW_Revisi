<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — SIM Layanan Warga RW 047</title>

    <!-- Google Fonts: Fraunces & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-text-primary antialiased bg-background flex flex-col min-h-screen">
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none sm:px-0 sm:w-80"></div>

    @php
        $user = Auth::user();
        $roleName = $user?->role?->name ?? 'WARGA';
        $roleDisplayName = $user?->role?->display_name ?? 'Warga';
        $currentRoute = Route::currentRouteName();
    @endphp

    <div class="flex h-screen overflow-hidden bg-background">
        <!-- Backdrop Mobile Drawer -->
        <div id="mobile-sidebar-backdrop" class="fixed inset-0 z-40 bg-text-primary/50 hidden lg:hidden transition-opacity duration-300 opacity-0" onclick="toggleMobileSidebar()"></div>

        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-surface border-r border-border flex flex-col transition-transform duration-300 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-auto lg:z-auto">
            <!-- Sidebar Header / Logo -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-border bg-surface">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-sm bg-primary flex items-center justify-center text-white shadow-sm flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="font-display font-semibold text-base leading-tight text-primary">SIM Warga</h1>
                        <span class="text-xs text-text-secondary font-medium tracking-wide">RW 047</span>
                    </div>
                </a>

                <!-- Mobile Close Button -->
                <button type="button" class="lg:hidden text-text-secondary hover:text-text-primary p-2 min-w-touch min-h-touch flex items-center justify-center rounded-sm" onclick="toggleMobileSidebar()" aria-label="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Role Badge in Sidebar -->
            <div class="px-6 py-3 bg-primary-light/50 border-b border-border/50 flex items-center justify-between">
                <span class="text-xs font-semibold text-primary uppercase tracking-wider">{{ $roleDisplayName }}</span>
                @if($user?->rt_code)
                    <span class="text-xs px-2 py-0.5 rounded-sm bg-primary text-white font-medium">RT {{ $user->rt_code }}</span>
                @endif
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Menu Utama">
                <!-- 1. Dashboard (Semua Role) -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary-light text-primary font-semibold border-l-4 border-primary' : 'text-text-secondary hover:bg-primary-light/50 hover:text-primary' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- 2. Data Warga (SUPER_ADMIN, KETUA_RW, SEKRETARIS_RW, KETUA_RT) -->
                @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'SEKRETARIS_RW', 'KETUA_RT']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Data Kependudukan</span>
                </a>
                @endif

                <!-- 3. Persuratan (SUPER_ADMIN, KETUA_RW, SEKRETARIS_RW, KETUA_RT, WARGA) -->
                @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'SEKRETARIS_RW', 'KETUA_RT', 'WARGA']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Pengajuan Surat</span>
                </a>
                @endif

                <!-- 4. Laporan & Aspirasi (SUPER_ADMIN, KETUA_RW, SEKRETARIS_RW, KETUA_RT, WARGA) -->
                @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'SEKRETARIS_RW', 'KETUA_RT', 'WARGA']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    <span>Laporan & Aspirasi</span>
                </a>
                @endif

                <!-- 5. Keuangan / Iuran (SUPER_ADMIN, KETUA_RW, BENDAHARA_RW, KETUA_RT) -->
                @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW', 'BENDAHARA_RW', 'KETUA_RT']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Iuran & Keuangan</span>
                </a>
                @endif

                <!-- 6. Informasi Publik (Semua Role) -->
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    <span>Informasi Publik</span>
                </a>

                <!-- 7. Manajemen Pengguna (Hanya SUPER_ADMIN) -->
                @if($roleName === 'SUPER_ADMIN')
                <div class="pt-3 mt-3 border-t border-border">
                    <span class="px-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Administrasi</span>
                </div>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Kelola Pengguna</span>
                </a>
                @endif

                <!-- 8. Log Audit (SUPER_ADMIN, KETUA_RW) -->
                @if(in_array($roleName, ['SUPER_ADMIN', 'KETUA_RW']))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-sm text-sm text-text-secondary hover:bg-primary-light/50 hover:text-primary transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Log Aktivitas</span>
                </a>
                @endif
            </nav>

            <!-- Bottom User Profile Section -->
            <div class="p-4 border-t border-border bg-surface">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-sm flex-shrink-0">
                            {{ strtoupper(substr($user?->full_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-semibold text-text-primary truncate">{{ $user?->full_name ?? 'Pengguna' }}</p>
                            <p class="text-xs text-text-secondary truncate">{{ $user?->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <button type="submit" class="p-2 text-text-secondary hover:text-danger hover:bg-danger-light rounded-sm transition-colors min-w-touch min-h-touch flex items-center justify-center" title="Keluar dari Sistem" aria-label="Keluar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Global Header -->
            <header class="h-16 bg-surface border-b border-border flex items-center justify-between px-4 sm:px-6 lg:px-8 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <!-- Hamburger button for mobile/tablet -->
                    <button type="button" class="lg:hidden p-2 text-text-secondary hover:text-text-primary rounded-sm min-w-touch min-h-touch flex items-center justify-center" onclick="toggleMobileSidebar()" aria-label="Buka Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Breadcrumbs -->
                    <nav class="flex items-center space-x-2 text-sm text-text-secondary" aria-label="Breadcrumb">
                        <span class="font-medium text-text-secondary">Dashboard</span>
                        <svg class="w-4 h-4 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="font-semibold text-primary">@yield('breadcrumb', 'Beranda')</span>
                    </nav>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-3">
                    <!-- Notifikasi Bell -->
                    <button type="button" class="relative p-2 text-text-secondary hover:text-primary hover:bg-primary-light rounded-sm transition-colors min-w-touch min-h-touch flex items-center justify-center" title="Notifikasi" aria-label="Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </button>

                    <!-- User Initials / Profile Header -->
                    <div class="flex items-center gap-2 pl-2 border-l border-border">
                        <div class="w-8 h-8 rounded-full bg-primary-light text-primary font-bold text-xs flex items-center justify-center">
                            {{ strtoupper(substr($user?->full_name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="hidden sm:inline text-sm font-medium text-text-primary">{{ $user?->full_name ?? 'Pengguna' }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto bg-background p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Mobile Drawer Script -->
    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobile-sidebar-backdrop');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                backdrop.classList.remove('hidden');
                setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0');
                setTimeout(() => backdrop.classList.add('hidden'), 300);
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
