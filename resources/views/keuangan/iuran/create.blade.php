@extends('layouts.dashboard')

@section('title', 'Catat Iuran Warga')
@section('breadcrumb', 'Catat Iuran Baru')

@section('content')
@php
    $user = Auth::user();
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Catat Iuran Warga
            </h1>
            <p class="mt-1 text-sm text-text-secondary">
                Pencatatan setoran iuran warga @if($user?->rt_code) <span class="font-semibold text-primary">RT {{ $user->rt_code }}</span> @endif
            </p>
        </div>
        <a href="{{ route('keuangan.iuran.index') }}"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-sm text-text-secondary hover:text-text-primary bg-surface border border-border rounded-sm hover:bg-background transition-colors min-h-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        {{-- Security Notice --}}
        <div class="mb-6 p-4 rounded-sm bg-primary-light/50 border border-primary/20 flex items-start gap-3">
            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <div class="text-xs text-text-primary leading-relaxed">
                <p class="font-semibold text-primary">Perlindungan Privasi Data Warga</p>
                <p class="mt-0.5 text-text-secondary">Nomor Kartu Keluarga hanya digunakan untuk pencarian data identitas keluarga secara aman. Sistem tidak menyimpan No. KK plaintext dalam riwayat transaksi publik.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('keuangan.iuran.store') }}" class="space-y-6" id="form-iuran">
            @csrf

            {{-- 1. No. KK --}}
            <div>
                <label for="no_kk" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Nomor Kartu Keluarga (16 Digit) <span class="text-danger">*</span>
                </label>
                <input type="text" id="no_kk" name="no_kk" value="{{ old('no_kk') }}" maxlength="16" required
                    placeholder="Contoh: 3216010101230012"
                    class="w-full px-3.5 py-2.5 bg-surface border @error('no_kk') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono" />
                @error('no_kk')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-text-secondary">Warga harus sudah terdaftar pada data kependudukan RW 047.</p>
            </div>

            {{-- 2. Jenis Iuran --}}
            <div>
                <label for="iuran_type_id" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Jenis Iuran <span class="text-danger">*</span>
                </label>
                <select id="iuran_type_id" name="iuran_type_id" required
                    class="w-full px-3.5 py-2.5 bg-surface border @error('iuran_type_id') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    onchange="updateDefaultAmount(this)">
                    <option value="">Pilih Jenis Iuran</option>
                    @foreach($iuranTypes as $type)
                        <option value="{{ $type->id }}" data-amount="{{ $type->default_amount }}" {{ (string) old('iuran_type_id') === (string) $type->id ? 'selected' : '' }}>
                            {{ $type->name }} ({{ $type->code }}) — Standar: Rp {{ number_format((float) $type->default_amount, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                @error('iuran_type_id')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- 3. Nominal & Tanggal Bayar --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nominal" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Nominal Pembayaran (Rp) <span class="text-danger">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-semibold text-text-secondary pointer-events-none">Rp</span>
                        <input type="number" step="1000" min="1" id="nominal" name="nominal" value="{{ old('nominal') }}" required
                            placeholder="50000"
                            class="w-full pl-10 pr-3.5 py-2.5 bg-surface border @error('nominal') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono" />
                    </div>
                    @error('nominal')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_pembayaran" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Tanggal Penerimaan
                    </label>
                    <input type="date" id="tanggal_pembayaran" name="tanggal_pembayaran" value="{{ old('tanggal_pembayaran', date('Y-m-d')) }}"
                        class="w-full px-3.5 py-2.5 bg-surface border @error('tanggal_pembayaran') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    @error('tanggal_pembayaran')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 4. Periode Iuran (Bulan & Tahun) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="periode_bulan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Periode Bulan <span class="text-danger">*</span>
                    </label>
                    <select id="periode_bulan" name="periode_bulan" required
                        class="w-full px-3.5 py-2.5 bg-surface border @error('periode_bulan') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach([1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'] as $m => $nama)
                            <option value="{{ $m }}" {{ (int) old('periode_bulan', date('n')) === $m ? 'selected' : '' }}>
                                {{ $nama }} (Bulan {{ sprintf('%02d', $m) }})
                            </option>
                        @endforeach
                    </select>
                    @error('periode_bulan')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="periode_tahun" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                        Periode Tahun <span class="text-danger">*</span>
                    </label>
                    <select id="periode_tahun" name="periode_tahun" required
                        class="w-full px-3.5 py-2.5 bg-surface border @error('periode_tahun') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach(range(date('Y') - 1, date('Y') + 2) as $yr)
                            <option value="{{ $yr }}" {{ (int) old('periode_tahun', date('Y')) === $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                    @error('periode_tahun')
                        <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 5. Catatan / Bukti --}}
            <div>
                <label for="payment_proof_path" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Keterangan / Referensi Bukti (Opsional)
                </label>
                <input type="text" id="payment_proof_path" name="payment_proof_path" value="{{ old('payment_proof_path') }}"
                    placeholder="Contoh: No. Kuitansi / Bukti Transfer Mandiri"
                    class="w-full px-3.5 py-2.5 bg-surface border @error('payment_proof_path') border-danger ring-1 ring-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                @error('payment_proof_path')
                    <p class="mt-1.5 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="pt-4 border-t border-border flex items-center justify-end gap-3">
                <a href="{{ route('keuangan.iuran.index') }}"
                    class="px-5 py-2.5 text-sm font-medium text-text-secondary hover:text-text-primary bg-background border border-border rounded-sm hover:bg-surface transition-colors min-h-touch flex items-center">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-sm shadow-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Simpan Pencatatan Iuran
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function updateDefaultAmount(select) {
        const option = select.options[select.selectedIndex];
        const amount = option.getAttribute('data-amount');
        const nominalInput = document.getElementById('nominal');
        if (amount && (!nominalInput.value || nominalInput.value === '0')) {
            nominalInput.value = parseFloat(amount).toFixed(0);
        }
    }
</script>
@endpush
@endsection
