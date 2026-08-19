@extends('layouts.dashboard')

@section('title', 'Tambah Data Warga')
@section('breadcrumb', 'Tambah Warga')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Back & Title -->
    <div class="flex items-center gap-3">
        <a href="{{ route('kependudukan.warga.index') }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-surface border border-border rounded-sm transition-colors min-h-touch min-w-touch flex items-center justify-center" aria-label="Kembali ke Daftar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Pendaftaran Data Warga Baru
            </h1>
            <p class="text-sm text-text-secondary mt-0.5">
                Tambahkan data warga ke dalam basis data Kartu Keluarga RW 047
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
            <h2 class="text-sm font-bold">Terdapat kesalahan pada isian formulir:</h2>
            <ul class="mt-1 text-xs list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Form Input Card -->
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('kependudukan.warga.store') }}" id="warga-form" class="space-y-8" novalidate>
            @csrf

            <!-- Section 1: Data Identitas & Kartu Keluarga -->
            <div class="space-y-5">
                <div class="border-b border-border/80 pb-2">
                    <h2 class="text-base font-semibold text-text-primary font-display">
                        1. Data Identitas Kependudukan (PII Terenkripsi)
                    </h2>
                    <p class="text-xs text-text-secondary mt-0.5">
                        Data identitas ini dienkripsi pada tingkat aplikasi dengan algoritma AES-256 demi perlindungan privasi warga.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- NIK -->
                    <div>
                        <label for="nik" class="block text-sm font-medium text-text-primary">
                            Nomor Induk Kependudukan (NIK) <span class="text-danger font-bold">*</span>
                        </label>
                        <div class="mt-1.5 relative">
                            <input type="text" id="nik" name="nik" value="{{ old('nik') }}" maxlength="16" required
                                placeholder="16 digit NIK"
                                class="w-full pl-3.5 pr-10 py-2.5 bg-surface border @error('nik') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary font-mono placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-text-secondary" title="Data dienkripsi secara aman">
                                <svg class="w-4 h-4 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-text-secondary">Wajib 16 digit angka sesuai KTP-el</p>
                        @error('nik')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- No KK -->
                    <div>
                        <label for="no_kk" class="block text-sm font-medium text-text-primary">
                            Nomor Kartu Keluarga (No. KK) <span class="text-danger font-bold">*</span>
                        </label>
                        <div class="mt-1.5 relative">
                            <input type="text" id="no_kk" name="no_kk" value="{{ old('no_kk') }}" maxlength="16" required
                                placeholder="16 digit Nomor KK"
                                class="w-full pl-3.5 pr-10 py-2.5 bg-surface border @error('no_kk') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary font-mono placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-text-secondary" title="Data dienkripsi secara aman">
                                <svg class="w-4 h-4 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-text-secondary">Nomor KK harus sudah terdaftar pada sistem</p>
                        @error('no_kk')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Nama Lengkap & Jenis Kelamin -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label for="nama_lengkap" class="block text-sm font-medium text-text-primary">
                            Nama Lengkap <span class="text-danger font-bold">*</span>
                        </label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                            placeholder="Sesuai dokumen kependudukan"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('nama_lengkap') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('nama_lengkap')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="jenis_kelamin" class="block text-sm font-medium text-text-primary">
                            Jenis Kelamin <span class="text-danger font-bold">*</span>
                        </label>
                        <select id="jenis_kelamin" name="jenis_kelamin" required
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('jenis_kelamin') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                            <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                        </select>
                        @error('jenis_kelamin')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tempat Lahir & Tanggal Lahir -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="tempat_lahir" class="block text-sm font-medium text-text-primary">
                            Tempat Lahir <span class="text-danger font-bold">*</span>
                        </label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" required
                            placeholder="Kota / Kabupaten Kelahiran"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('tempat_lahir') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('tempat_lahir')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tanggal_lahir" class="block text-sm font-medium text-text-primary">
                            Tanggal Lahir <span class="text-danger font-bold">*</span>
                        </label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('tanggal_lahir') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('tanggal_lahir')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Data Hubungan & Tambahan -->
            <div class="space-y-5">
                <div class="border-b border-border/80 pb-2">
                    <h2 class="text-base font-semibold text-text-primary font-display">
                        2. Data Hubungan Keluarga & Status Warga
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Status Hubungan Keluarga -->
                    <div>
                        <label for="status_hubungan_keluarga" class="block text-sm font-medium text-text-primary">
                            Status Hubungan dalam Keluarga <span class="text-danger font-bold">*</span>
                        </label>
                        <select id="status_hubungan_keluarga" name="status_hubungan_keluarga" required
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('status_hubungan_keluarga') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Hubungan Keluarga</option>
                            <option value="KEPALA KELUARGA" {{ old('status_hubungan_keluarga') === 'KEPALA KELUARGA' ? 'selected' : '' }}>Kepala Keluarga</option>
                            <option value="ISTRI" {{ old('status_hubungan_keluarga') === 'ISTRI' ? 'selected' : '' }}>Istri</option>
                            <option value="ANAK" {{ old('status_hubungan_keluarga') === 'ANAK' ? 'selected' : '' }}>Anak</option>
                            <option value="FAMILI LAIN" {{ old('status_hubungan_keluarga') === 'FAMILI LAIN' ? 'selected' : '' }}>Famili Lain</option>
                        </select>
                        @error('status_hubungan_keluarga')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Warga -->
                    <div>
                        <label for="status_warga" class="block text-sm font-medium text-text-primary">
                            Status Kependudukan
                        </label>
                        <select id="status_warga" name="status_warga"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('status_warga') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="TETAP" {{ old('status_warga', 'TETAP') === 'TETAP' ? 'selected' : '' }}>Warga Tetap</option>
                            <option value="KONTRAK" {{ old('status_warga') === 'KONTRAK' ? 'selected' : '' }}>Warga Kontrak</option>
                            <option value="PINDAH" {{ old('status_warga') === 'PINDAH' ? 'selected' : '' }}>Pindah</option>
                            <option value="MENINGGAL" {{ old('status_warga') === 'MENINGGAL' ? 'selected' : '' }}>Meninggal</option>
                        </select>
                        @error('status_warga')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <!-- Pekerjaan -->
                    <div>
                        <label for="pekerjaan" class="block text-sm font-medium text-text-primary">
                            Pekerjaan
                        </label>
                        <input type="text" id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan') }}"
                            placeholder="Contoh: Karyawan Swasta, Wiraswasta"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Nomor HP -->
                    <div>
                        <label for="nomor_hp" class="block text-sm font-medium text-text-primary">
                            Nomor Telepon / WhatsApp
                        </label>
                        <input type="tel" id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp') }}"
                            placeholder="Contoh: 08123456789"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Status Sosio Ekonomi -->
                    <div>
                        <label for="status_sosio_ekonomi" class="block text-sm font-medium text-text-primary">
                            Status Sosio Ekonomi
                        </label>
                        <select id="status_sosio_ekonomi" name="status_sosio_ekonomi"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            <option value="MAMPU" {{ old('status_sosio_ekonomi') === 'MAMPU' ? 'selected' : '' }}>Mampu</option>
                            <option value="MENENGAH" {{ old('status_sosio_ekonomi') === 'MENENGAH' ? 'selected' : '' }}>Menengah</option>
                            <option value="PRA_SEJAHTERA" {{ old('status_sosio_ekonomi') === 'PRA_SEJAHTERA' ? 'selected' : '' }}>Pra Sejahtera</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit & Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
                <a href="{{ route('kependudukan.warga.index') }}" class="px-5 py-2.5 bg-surface hover:bg-background text-text-secondary border border-border rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors">
                    Batal
                </a>
                <button type="submit" id="btn-submit-warga" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Daftarkan Warga</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
