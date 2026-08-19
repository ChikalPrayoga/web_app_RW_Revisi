@extends('layouts.dashboard')

@section('title', 'Verifikasi Iuran Warga')
@section('breadcrumb', 'Verifikasi Iuran')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Verifikasi Iuran Warga
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Daftar transaksi iuran yang menunggu persetujuan Bendahara RW (Dual-Control)
            </p>
        </div>
        <a href="{{ route('keuangan.iuran.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm text-text-secondary hover:text-text-primary bg-surface border border-border rounded-sm hover:bg-background transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Semua Riwayat Iuran
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
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">No. KK (Masked)</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">RT</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis Iuran</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Periode</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Nominal</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Pencatat</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Aksi Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($pendingIurans as $item)
                    <tr class="hover:bg-primary-light/10 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-text-primary">
                            {{ $item->kartuKeluarga?->no_kk_masked ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-primary-light text-primary">
                                RT {{ $item->kartuKeluarga?->rt_code ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-text-primary">
                            {{ $item->iuranType?->name ?? '—' }}
                            <span class="text-xs text-text-secondary block font-normal">{{ $item->iuranType?->code }}</span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary">
                            {{ sprintf('%02d/%04d', $item->periode_bulan, $item->periode_tahun) }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-text-primary">
                            Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-text-secondary">
                            {{ $item->recordedBy?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                {{-- Form Setujui --}}
                                <form method="POST" action="{{ route('keuangan.iuran.approve', $item->iuran_id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui transaksi iuran ini?')">
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
                                <button type="button" onclick="openRejectModal({{ $item->iuran_id }}, '{{ $item->kartuKeluarga?->no_kk_masked }}', '{{ $item->iuranType?->name }}')"
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
                        <td colspan="7" class="px-4 py-12 text-center text-text-secondary">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-success/50 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="font-medium text-text-primary">Tidak ada antrean verifikasi</p>
                                <p class="text-xs text-text-secondary mt-1">Seluruh transaksi iuran telah diproses oleh Bendahara RW.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="sm:hidden divide-y divide-border">
            @forelse($pendingIurans as $item)
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-xs font-semibold text-text-primary">
                        {{ $item->kartuKeluarga?->no_kk_masked ?? '—' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-sm text-xs font-medium bg-primary-light text-primary">
                        RT {{ $item->kartuKeluarga?->rt_code ?? '—' }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-text-secondary">{{ $item->iuranType?->name }} ({{ sprintf('%02d/%04d', $item->periode_bulan, $item->periode_tahun) }})</span>
                    <span class="font-semibold text-primary">Rp {{ number_format((float) $item->nominal, 0, ',', '.') }}</span>
                </div>
                <div class="text-xs text-text-secondary">
                    Dicatat oleh: <span class="font-medium text-text-primary">{{ $item->recordedBy?->full_name }}</span>
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-border/50">
                    <form method="POST" action="{{ route('keuangan.iuran.approve', $item->iuran_id) }}" class="flex-1" onsubmit="return confirm('Setujui transaksi ini?')">
                        @csrf
                        <input type="hidden" name="action" value="APPROVE">
                        <button type="submit" class="w-full py-2 bg-success text-white text-xs font-semibold rounded-sm">
                            Setujui
                        </button>
                    </form>
                    <button type="button" onclick="openRejectModal({{ $item->iuran_id }}, '{{ $item->kartuKeluarga?->no_kk_masked }}', '{{ $item->iuranType?->name }}')"
                        class="flex-1 py-2 bg-danger-light text-danger border border-danger/30 text-xs font-semibold rounded-sm">
                        Tolak
                    </button>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-text-secondary text-sm">
                Tidak ada transaksi iuran yang menunggu verifikasi.
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Modal Penolakan --}}
<div id="reject-modal" class="fixed inset-0 z-50 bg-text-primary/50 hidden flex items-center justify-center p-4">
    <div class="bg-surface rounded-md border border-border max-w-md w-full p-6 shadow-lg space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-display font-semibold text-lg text-text-primary">Tolak Transaksi Iuran</h3>
            <button type="button" onclick="closeRejectModal()" class="text-text-secondary hover:text-text-primary p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <p class="text-xs text-text-secondary">
            Menolak iuran <span id="modal-iuran-desc" class="font-semibold text-text-primary"></span>. Ketua RT dapat melihat catatan alasan penolakan ini untuk mencatat ulang.
        </p>

        <form id="reject-form" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="action" value="REJECT">

            <div>
                <label for="rejection_notes" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Alasan Penolakan <span class="text-danger">*</span>
                </label>
                <textarea id="rejection_notes" name="rejection_notes" rows="3" required minlength="5"
                    placeholder="Contoh: Nominal tidak sesuai dengan bukti setoran tunai yang diserahkan"
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
    function openRejectModal(id, noKk, iuranName) {
        const form = document.getElementById('reject-form');
        form.action = `/keuangan/iuran/${id}/approve`;
        document.getElementById('modal-iuran-desc').textContent = `${iuranName} (${noKk})`;
        document.getElementById('reject-modal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('reject-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
