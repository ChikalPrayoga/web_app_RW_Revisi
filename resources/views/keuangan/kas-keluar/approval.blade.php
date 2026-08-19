@extends('layouts.dashboard')

@section('title', 'Verifikasi Pengeluaran Kas')
@section('breadcrumb', 'Verifikasi Kas Keluar')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Verifikasi Pengeluaran Kas RW
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Daftar pengajuan kas keluar yang menunggu persetujuan Ketua RW (Dual-Control)
            </p>
        </div>
        <a href="{{ route('keuangan.kas-keluar.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm text-text-secondary hover:text-text-primary bg-surface border border-border rounded-sm hover:bg-background transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Semua Riwayat Pengeluaran
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="p-4 rounded-sm bg-success-light border border-success/30 text-success flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Data List --}}
    <div class="bg-surface rounded-md border border-border shadow-sm overflow-hidden">
        {{-- Desktop Table --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-background">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Tanggal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Kategori</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Keterangan</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Nominal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Pencatat</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($pendingKasKeluars as $item)
                    <tr class="hover:bg-primary-light/10 transition-colors">
                        <td class="px-4 py-3 font-medium text-text-secondary text-xs">
                            {{ $item->tanggal_pengeluaran ? $item->tanggal_pengeluaran->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-secondary-light text-secondary">
                                {{ $item->kategori }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text-primary max-w-sm">
                            {{ $item->keterangan }}
                            @if($item->bukti_path)
                                <span class="text-xs text-text-secondary block font-normal mt-0.5">Bukti: {{ $item->bukti_path }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-danger">
                            Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-text-secondary">
                            {{ $item->recordedBy?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                {{-- Form Setujui --}}
                                <form method="POST" action="{{ route('keuangan.kas-keluar.approve', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pengeluaran kas sebesar Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}?')">
                                    @csrf
                                    <input type="hidden" name="action" value="APPROVE">
                                    <button type="submit"
                                        class="px-3 py-1.5 bg-success hover:bg-success/90 text-white text-xs font-semibold rounded-sm transition-colors min-h-touch flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Setujui
                                    </button>
                                </form>

                                {{-- Tombol Tolak (Buka Modal) --}}
                                <button type="button" onclick="openRejectModal({{ $item->id }}, '{{ $item->kategori }}', '{{ number_format((float) $item->nominal, 0, ',', '.') }}')"
                                    class="px-3 py-1.5 bg-danger-light hover:bg-danger text-danger hover:text-white border border-danger/30 text-xs font-semibold rounded-sm transition-colors min-h-touch flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-text-secondary">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-success/50 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="font-medium text-text-primary">Tidak ada antrean persetujuan kas keluar</p>
                                <p class="text-xs text-text-secondary mt-1">Seluruh transaksi pengeluaran kas telah diverifikasi oleh Ketua RW.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-border">
            @forelse($pendingKasKeluars as $item)
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-secondary bg-secondary-light px-2 py-0.5 rounded-sm">
                        {{ $item->kategori }}
                    </span>
                    <span class="font-semibold text-danger">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-text-primary font-medium">{{ $item->keterangan }}</p>
                <div class="text-xs text-text-secondary">
                    Dicatat oleh: <span class="font-medium text-text-primary">{{ $item->recordedBy?->full_name }}</span> &bull; {{ $item->tanggal_pengeluaran ? $item->tanggal_pengeluaran->format('d/m/Y') : '' }}
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-border/50">
                    <form method="POST" action="{{ route('keuangan.kas-keluar.approve', $item->id) }}" class="flex-1" onsubmit="return confirm('Setujui transaksi ini?')">
                        @csrf
                        <input type="hidden" name="action" value="APPROVE">
                        <button type="submit" class="w-full py-2 bg-success text-white text-xs font-semibold rounded-sm">
                            Setujui
                        </button>
                    </form>
                    <button type="button" onclick="openRejectModal({{ $item->id }}, '{{ $item->kategori }}', '{{ number_format((float) $item->nominal, 0, ',', '.') }}')"
                        class="flex-1 py-2 bg-danger-light text-danger border border-danger/30 text-xs font-semibold rounded-sm">
                        Tolak
                    </button>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-text-secondary text-sm">
                Tidak ada transaksi pengeluaran kas yang menunggu persetujuan.
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Modal Penolakan --}}
<div id="reject-modal" class="fixed inset-0 z-50 bg-text-primary/50 hidden flex items-center justify-center p-4">
    <div class="bg-surface rounded-md border border-border max-w-md w-full p-6 shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-semibold text-lg text-text-primary">Tolak Pengeluaran Kas</h3>
            <button type="button" onclick="closeRejectModal()" class="text-text-secondary hover:text-text-primary p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <p class="text-xs text-text-secondary">
            Menolak pengeluaran <strong id="modal-kas-desc" class="text-text-primary"></strong>. Transaksi ini tidak akan memotong saldo kas RW.
        </p>

        <form id="reject-form" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="action" value="REJECT">

            <div>
                <label for="rejection_notes" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Alasan Penolakan <span class="text-danger">*</span>
                </label>
                <textarea id="rejection_notes" name="rejection_notes" rows="3" required minlength="5"
                    placeholder="Contoh: Pengeluaran ini belum dikoordinasikan dalam rapat pengurus RW"
                    class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-danger focus:border-danger"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-sm text-text-secondary hover:text-text-primary bg-background border border-border rounded-sm">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-danger hover:bg-danger/90 text-white text-sm font-semibold rounded-sm">
                    Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openRejectModal(id, kategori, nominal) {
        const form = document.getElementById('reject-form');
        form.action = `/keuangan/kas-keluar/${id}/approve`;
        document.getElementById('modal-kas-desc').textContent = `${kategori} (Rp ${nominal})`;
        document.getElementById('reject-modal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
