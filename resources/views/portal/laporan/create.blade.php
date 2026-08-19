@extends('layouts.public')

@section('title', 'Sampaikan Laporan & Aspirasi — Portal Warga RW 047')

@section('public-content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
            Sampaikan Laporan & Aspirasi
        </h1>
        <p class="mt-1 text-sm text-text-secondary">
            Sampaikan keluhan fasilitas, keamanan lingkungan, atau aspirasi warga RW 047 secara resmi dan transparan.
        </p>
    </div>

    {{-- Form Errors --}}
    @if($errors->any())
    <div class="p-4 rounded-sm bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <p class="text-sm font-semibold mb-1">Terdapat kesalahan pada formulir:</p>
            <ul class="text-xs list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Form Container --}}
    <div class="bg-surface rounded-md border border-border shadow-xs p-6 sm:p-8">
        <form method="POST" action="{{ route('portal.laporan.store') }}" id="form-laporan-warga" class="space-y-6">
            @csrf

            {{-- Judul Laporan --}}
            <div>
                <label for="judul_laporan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Judul Laporan / Keluhan <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="judul_laporan"
                    name="judul_laporan"
                    value="{{ old('judul_laporan') }}"
                    maxlength="150"
                    placeholder="Contoh: Lampu jalan mati di Blok C"
                    required
                    class="w-full px-3.5 py-2.5 rounded-sm border border-border bg-background text-text-primary text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors @error('judul_laporan') border-rose-500 @enderror"
                >
                @error('judul_laporan')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Lokasi Kejadian --}}
            <div>
                <label for="lokasi_kejadian" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Lokasi / Alamat Kejadian <span class="text-text-muted font-normal lowercase">(opsional)</span>
                </label>
                <input
                    type="text"
                    id="lokasi_kejadian"
                    name="lokasi_kejadian"
                    value="{{ old('lokasi_kejadian') }}"
                    maxlength="500"
                    placeholder="Contoh: Depan Pos Kamling RT 03 / Jl. Mawar No. 12"
                    class="w-full px-3.5 py-2.5 rounded-sm border border-border bg-background text-text-primary text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors @error('lokasi_kejadian') border-rose-500 @enderror"
                >
                @error('lokasi_kejadian')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Teks Keluhan --}}
            <div x-data="{ count: {{ strlen(old('teks_keluhan', '')) }} }">
                <div class="flex items-center justify-between mb-1.5">
                    <label for="teks_keluhan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Rincian Laporan / Keluhan <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-text-muted">Min. 20 karakter (<span x-text="count">0</span> karakter)</span>
                </div>
                <textarea
                    id="teks_keluhan"
                    name="teks_keluhan"
                    rows="5"
                    x-on:input="count = $event.target.value.length"
                    placeholder="Jelaskan secara rinci permasalahan, waktu perkiraan terjadi, atau saran perbaikan yang Anda usulkan..."
                    required
                    class="w-full px-3.5 py-2.5 rounded-sm border border-border bg-background text-text-primary text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors @error('teks_keluhan') border-rose-500 @enderror"
                >{{ old('teks_keluhan') }}</textarea>
                @error('teks_keluhan')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- NIK (Optional) --}}
            <div>
                <label for="nik" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    NIK Pelapor <span class="text-text-muted font-normal lowercase">(opsional / untuk verifikasi warga)</span>
                </label>
                <input
                    type="text"
                    id="nik"
                    name="nik"
                    value="{{ old('nik') }}"
                    maxlength="16"
                    inputmode="numeric"
                    placeholder="Masukkan 16 digit NIK jika ingin terhubung dengan data kependudukan"
                    class="w-full px-3.5 py-2.5 rounded-sm border border-border bg-background text-text-primary text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors @error('nik') border-rose-500 @enderror"
                >
                <p class="mt-1 text-[11px] text-text-muted">
                    Laporan dapat dikirim secara anonim tanpa mengisi NIK. Jika diisi, data NIK Anda dilindungi enkripsi.
                </p>
                @error('nik')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-6 bg-primary hover:bg-primary-dark text-white font-semibold text-sm rounded-sm shadow-xs transition-colors flex items-center justify-center gap-2 min-h-touch"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <span>Kirim Laporan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
