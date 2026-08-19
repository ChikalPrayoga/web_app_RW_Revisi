@extends('layouts.dashboard')

@section('title', 'Manajemen Informasi Publik')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-text-primary">Informasi Publik</h1>
            <p class="text-xs text-text-secondary mt-1">Kelola pengumuman, berita lingkungan, dan agenda kegiatan RW 047</p>
        </div>

        @can('create', App\Models\InformasiPublik::class)
        <a href="{{ route('informasi-publik.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm shadow-xs transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Tambah Informasi</span>
        </a>
        @endcan
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="p-4 bg-success-light border border-success/30 rounded-md flex items-center gap-3 text-success text-xs font-medium">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="bg-surface p-4 rounded-md border border-border shadow-xs">
        <form method="GET" action="{{ route('informasi-publik.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">Status Publikasi</label>
                <select name="status" class="w-full text-xs bg-background border border-border rounded-sm px-2.5 py-1.5 focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                        {{ $st->label() }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">Kategori</label>
                <select name="kategori" class="w-full text-xs bg-background border border-border rounded-sm px-2.5 py-1.5 focus:ring-1 focus:ring-primary focus:border-primary">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoris as $kat)
                    <option value="{{ $kat->value }}" {{ request('kategori') === $kat->value ? 'selected' : '' }}>
                        {{ $kat->label() }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-text-secondary mb-1">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul / konten..."
                    class="w-full text-xs bg-background border border-border rounded-sm px-2.5 py-1.5 focus:ring-1 focus:ring-primary focus:border-primary" />
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-1.5 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm transition-colors min-h-touch">
                    Filter
                </button>
                @if(request()->hasAny(['status', 'kategori', 'search']))
                <a href="{{ route('informasi-publik.index') }}" class="px-3 py-1.5 text-xs text-text-secondary hover:text-danger border border-border rounded-sm transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table / Card Data --}}
    <div class="bg-surface rounded-md border border-border shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-background border-b border-border text-text-secondary font-semibold">
                        <th class="py-3 px-4">Judul & Tanggal</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Tanggal Agenda</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Dipublikasikan Oleh</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($informasiList as $item)
                    <tr class="hover:bg-background/50 transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-text-primary max-w-xs sm:max-w-md truncate">
                                {{ $item->judul }}
                            </div>
                            <div class="text-[11px] text-text-secondary mt-0.5">
                                Publikasi: {{ $item->tanggal_publikasi ? $item->tanggal_publikasi->translatedFormat('d M Y') : '-' }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $item->kategori->badgeClass() }}">
                                {{ $item->kategori->label() }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-text-secondary">
                            @if($item->tanggal_agenda)
                            <span class="font-medium text-warning flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $item->tanggal_agenda->translatedFormat('d M Y') }}
                            </span>
                            @else
                            <span class="text-text-secondary">-</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $item->status->badgeClass() }}">
                                {{ $item->status->label() }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-text-secondary">
                            {{ $item->publishedBy?->full_name ?? 'Pengurus RW' }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('update', $item)
                                <a href="{{ route('informasi-publik.edit', $item->id) }}"
                                    class="p-1.5 text-text-secondary hover:text-primary rounded-sm hover:bg-background transition-colors"
                                    title="Edit Informasi">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endcan

                                @can('delete', $item)
                                <form method="POST" action="{{ route('informasi-publik.destroy', $item->id) }}"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus informasi ini?');"
                                    class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-text-secondary hover:text-danger rounded-sm hover:bg-background transition-colors"
                                        title="Hapus Informasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-text-secondary">
                            Belum ada data informasi publik.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($informasiList->hasPages())
        <div class="p-4 border-t border-border bg-background/50">
            {{ $informasiList->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
