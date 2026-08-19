@extends('layouts.dashboard')

@section('title', 'Catat Pengeluaran Kas')
@section('breadcrumb', 'Catat Kas Keluar')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Catat Pengeluaran Kas RW
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Pencatatan pengeluaran dana operasional RW 047 oleh Bendahara RW
            </p>
        </div>
        <a href="{{ route('keuangan.kas-keluar.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm text-text-secondary hover:text-text-primary bg-surface border border-border rounded-sm hover:bg-background transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        {{-- Dual Control Notice --}}
        <div class="mb-6 p-4 rounded-sm bg-warning-light/60 border border-warning/30 flex items-start gap-3">
            <svg class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-xs text-text-primary leading-relaxed">
                <p class="font-semibold text-text-primary">Prinsip Kontrol Ganda (Dual-Control)</p>
                <p class="mt-0.5 text-text-secondary">Pengeluaran kas yang dicatat akan berstatus <strong class="text-warning font-semibold">MENUNGGU VERIFIKASI</strong> sampai ditinjau dan disetujui secara resmi oleh Ketua RW sebelum memotong saldo kas riil.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('keuangan.kas-keluar.store') }}" class="space-y-6">
            @csrf

            {{-- 1. Kategori & Tanggal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kategori" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Kategori Pengeluaran <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="kategori" name="kategori" value="{{ old('kategori') }}" required
                        placeholder="Contoh: Kebersihan Lingkungan, Keamanan, ATK"
                        class="w-full px-3.5 py-2.5 bg-surface border @error('kategori') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    @error('kategori')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_pengeluaran" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Tanggal Pengeluaran <span class="text-danger">*</span>
                    </label>
                    <input type="date" id="tanggal_pengeluaran" name="tanggal_pengeluaran" value="{{ old('tanggal_pengeluaran', date('Y-m-d')) }}" required
                        class="w-full px-3.5 py-2.5 bg-surface border @error('tanggal_pengeluaran') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    @error('tanggal_pengeluaran')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 2. Nominal --}}
            <div>
                <label for="nominal" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Nominal Pengeluaran (Rp) <span class="text-danger">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-semibold text-text-secondary pointer-events-none">Rp</span>
                    <input type="number" step="1000" min="1" id="nominal" name="nominal" value="{{ old('nominal') }}" required
                        placeholder="350000"
                        class="w-full pl-10 pr-3.5 py-2.5 bg-surface border @error('nominal') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono" />
                </div>
                @error('nominal')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- 3. Keterangan --}}
            <div>
                <label for="keterangan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Rincian Keterangan Pengeluaran <span class="text-danger">*</span>
                </label>
                <textarea id="keterangan" name="keterangan" rows="3" required minlength="10"
                    placeholder="Contoh: Pembelian kantong sampah besar dan peralatan kerja bakti RW 047"
                    class="w-full px-3.5 py-2.5 bg-surface border @error('keterangan') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-text-secondary">Jelaskan tujuan dan peruntukan pengeluaran dana secara rinci (minimal 10 karakter).</p>
            </div>

            {{-- 4. Bukti Path --}}
            <div>
                <label for="bukti_path" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Referensi / Bukti Nota / Kuitansi (Opsional)
                </label>
                <input type="text" id="bukti_path" name="bukti_path" value="{{ old('bukti_path') }}"
                    placeholder="Contoh: uploads/bukti/kuitansi-sampah-20260815.jpg atau No. Nota #0482"
                    class="w-full px-3.5 py-2.5 bg-surface border @error('bukti_path') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                @error('bukti_path')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                <a href="{{ route('keuangan.kas-keluar.index') }}"
                    class="px-5 py-2.5 text-sm font-medium text-text-secondary hover:text-text-primary bg-background border border-border rounded-sm hover:bg-surface transition-colors min-h-touch flex items-center">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-sm shadow-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Simpan Pengeluaran Kas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
