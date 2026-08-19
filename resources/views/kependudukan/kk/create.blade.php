@extends('layouts.dashboard')

@section('title', 'Tambah Kartu Keluarga')
@section('breadcrumb', 'Tambah KK')

@section('content')
@php
    $user = Auth::user();
    $isKetuaRt = $user?->hasRole(\App\Enums\RoleName::KETUA_RT->value);
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header Back & Title -->
    <div class="flex items-center gap-3">
        <a href="{{ route('kependudukan.kk.index') }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-surface border border-border rounded-sm transition-colors min-h-touch min-w-touch flex items-center justify-center" aria-label="Kembali ke Daftar KK">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Pendaftaran Kartu Keluarga Baru
            </h1>
            <p class="text-sm text-text-secondary mt-0.5">
                Mendaftarkan unit keluarga baru ke dalam basis data RW 047
            </p>
        </div>
    </div>

    <!-- General Error Banner -->
    @if ($errors->any())
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <h2 class="text-sm font-bold">Terdapat kesalahan pada formulir pendaftaran KK:</h2>
            <ul class="mt-1 text-xs list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Form Card -->
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('kependudukan.kk.store') }}" id="kk-form" class="space-y-6" novalidate>
            @csrf

            <!-- No. KK -->
            <div>
                <label for="no_kk" class="block text-sm font-medium text-text-primary">
                    Nomor Kartu Keluarga (No. KK) <span class="text-danger font-bold">*</span>
                </label>
                <div class="mt-1.5 relative">
                    <input type="text" id="no_kk" name="no_kk" value="{{ old('no_kk') }}" maxlength="16" required
                        placeholder="16 digit Nomor Kartu Keluarga"
                        class="w-full pl-3.5 pr-10 py-2.5 bg-surface border @error('no_kk') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary font-mono placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-text-secondary" title="Data dienkripsi secara aman">
                        <svg class="w-4 h-4 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-1 text-xs text-text-secondary">Wajib 16 digit angka sesuai dokumen resmi KK</p>
                @error('no_kk')
                    <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Wilayah RT & Status Kepemilikan Rumah -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- RT Code -->
                <div>
                    <label for="rt_code" class="block text-sm font-medium text-text-primary">
                        Wilayah Rukun Tetangga (RT) <span class="text-danger font-bold">*</span>
                    </label>
                    @if($isKetuaRt)
                        <input type="hidden" name="rt_code" value="{{ $user->rt_code }}">
                        <input type="text" disabled value="RT {{ $user->rt_code }} (Wilayah Anda)"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-background border border-border rounded-sm text-sm text-text-primary font-semibold">
                    @else
                        <select id="rt_code" name="rt_code" required
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('rt_code') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Wilayah RT</option>
                            @foreach(['001', '002', '003', '004', '005', '006', '007', '008'] as $rt)
                                <option value="{{ $rt }}" {{ old('rt_code') === $rt ? 'selected' : '' }}>RT {{ $rt }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('rt_code')
                        <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Kepemilikan Rumah -->
                <div>
                    <label for="status_kepemilikan_rumah" class="block text-sm font-medium text-text-primary">
                        Status Kepemilikan Tempat Tinggal <span class="text-danger font-bold">*</span>
                    </label>
                    <select id="status_kepemilikan_rumah" name="status_kepemilikan_rumah" required
                        class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('status_kepemilikan_rumah') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Pilih Status Kepemilikan</option>
                        <option value="MILIK SENDIRI" {{ old('status_kepemilikan_rumah') === 'MILIK SENDIRI' ? 'selected' : '' }}>Milik Sendiri</option>
                        <option value="SEWA / KONTRAK" {{ old('status_kepemilikan_rumah') === 'SEWA / KONTRAK' ? 'selected' : '' }}>Sewa / Kontrak</option>
                        <option value="RUMAH DINAS" {{ old('status_kepemilikan_rumah') === 'RUMAH DINAS' ? 'selected' : '' }}>Rumah Dinas</option>
                        <option value="MENUMPANG" {{ old('status_kepemilikan_rumah') === 'MENUMPANG' ? 'selected' : '' }}>Menumpang</option>
                    </select>
                    @error('status_kepemilikan_rumah')
                        <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Blok & Nomor Rumah -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="blok" class="block text-sm font-medium text-text-primary">
                        Blok (Opsional)
                    </label>
                    <input type="text" id="blok" name="blok" value="{{ old('blok') }}" placeholder="Contoh: A, B1"
                        class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="nomor_rumah" class="block text-sm font-medium text-text-primary">
                        Nomor Rumah (Opsional)
                    </label>
                    <input type="text" id="nomor_rumah" name="nomor_rumah" value="{{ old('nomor_rumah') }}" placeholder="Contoh: 12, 12A"
                        class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <!-- Alamat Lengkap -->
            <div>
                <label for="alamat_lengkap" class="block text-sm font-medium text-text-primary">
                    Alamat Lengkap Domisili <span class="text-danger font-bold">*</span>
                </label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" rows="3" required maxlength="500"
                    placeholder="Alamat domisili lengkap di lingkungan RW 047..."
                    class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('alamat_lengkap') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('alamat_lengkap') }}</textarea>
                @error('alamat_lengkap')
                    <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit & Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
                <a href="{{ route('kependudukan.kk.index') }}" class="px-5 py-2.5 bg-surface hover:bg-background text-text-secondary border border-border rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Daftarkan Kartu Keluarga</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
