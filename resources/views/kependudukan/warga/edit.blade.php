@extends('layouts.dashboard')

@section('title', 'Edit Data Warga — ' . $warga->nama_lengkap)
@section('breadcrumb', 'Edit Warga')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Back & Title -->
    <div class="flex items-center gap-3">
        <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-surface border border-border rounded-sm transition-colors min-h-touch min-w-touch flex items-center justify-center" aria-label="Kembali ke Detail">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Perbarui Data Warga
            </h1>
            <p class="text-sm text-text-secondary mt-0.5">
                Mengubah informasi untuk warga: <span class="font-semibold text-text-primary">{{ $warga->nama_lengkap }}</span>
            </p>
        </div>
    </div>

    <!-- Notice re-verification -->
    <div class="p-4 rounded-sm bg-warning-light border border-warning/30 text-warning flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div class="text-xs sm:text-sm text-text-primary">
            <span class="font-semibold text-warning">Perhatian:</span> Setiap perubahan pada data warga akan mengubah status verifikasi kembali menjadi <strong class="text-warning font-semibold">Menunggu Verifikasi</strong> oleh Sekretaris RW.
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
        <form method="POST" action="{{ route('kependudukan.warga.update', ['nik_hash' => $warga->nik_hash]) }}" id="warga-edit-form" class="space-y-8" novalidate>
            @csrf
            @method('PATCH')

            <!-- Section 1: Identitas Tetap (Read-Only) -->
            <div class="space-y-5">
                <div class="border-b border-border/80 pb-2">
                    <h2 class="text-base font-semibold text-text-primary font-display">
                        1. Identitas Kependudukan Terdaftar
                    </h2>
                    <p class="text-xs text-text-secondary mt-0.5">
                        Identitas utama tidak dapat diubah setelah terdaftar. Hubungi Administrator jika ada kesalahan data pokok.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 bg-background p-4 rounded-sm border border-border">
                    <div>
                        <span class="block text-xs font-semibold text-text-secondary uppercase tracking-wider">NIK (Masked)</span>
                        <span class="font-mono text-sm font-medium text-text-primary mt-1 block">{{ $warga->nik_masked }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-text-secondary uppercase tracking-wider">Nomor KK (Masked)</span>
                        <span class="font-mono text-sm font-medium text-text-primary mt-1 block">{{ $warga->no_kk_masked }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-text-secondary uppercase tracking-wider">Nama Lengkap</span>
                        <span class="text-sm font-medium text-text-primary mt-1 block">{{ $warga->nama_lengkap }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-text-secondary uppercase tracking-wider">Jenis Kelamin</span>
                        <span class="text-sm font-medium text-text-primary mt-1 block">{{ $warga->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                    </div>
                </div>
            </div>

            <!-- Section 2: Data yang Dapat Diperbarui -->
            <div class="space-y-5">
                <div class="border-b border-border/80 pb-2">
                    <h2 class="text-base font-semibold text-text-primary font-display">
                        2. Informasi yang Dapat Diperbarui
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
                            <option value="KEPALA KELUARGA" {{ old('status_hubungan_keluarga', $warga->status_hubungan_keluarga) === 'KEPALA KELUARGA' ? 'selected' : '' }}>Kepala Keluarga</option>
                            <option value="ISTRI" {{ old('status_hubungan_keluarga', $warga->status_hubungan_keluarga) === 'ISTRI' ? 'selected' : '' }}>Istri</option>
                            <option value="ANAK" {{ old('status_hubungan_keluarga', $warga->status_hubungan_keluarga) === 'ANAK' ? 'selected' : '' }}>Anak</option>
                            <option value="FAMILI LAIN" {{ old('status_hubungan_keluarga', $warga->status_hubungan_keluarga) === 'FAMILI LAIN' ? 'selected' : '' }}>Famili Lain</option>
                        </select>
                        @error('status_hubungan_keluarga')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Warga -->
                    <div>
                        <label for="status_warga" class="block text-sm font-medium text-text-primary">
                            Status Kependudukan <span class="text-danger font-bold">*</span>
                        </label>
                        <select id="status_warga" name="status_warga" required
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('status_warga') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="TETAP" {{ old('status_warga', $warga->status_warga) === 'TETAP' ? 'selected' : '' }}>Warga Tetap</option>
                            <option value="KONTRAK" {{ old('status_warga', $warga->status_warga) === 'KONTRAK' ? 'selected' : '' }}>Warga Kontrak</option>
                            <option value="PINDAH" {{ old('status_warga', $warga->status_warga) === 'PINDAH' ? 'selected' : '' }}>Pindah</option>
                            <option value="MENINGGAL" {{ old('status_warga', $warga->status_warga) === 'MENINGGAL' ? 'selected' : '' }}>Meninggal</option>
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
                        <input type="text" id="pekerjaan" name="pekerjaan" value="{{ old('pekerjaan', $warga->pekerjaan) }}"
                            placeholder="Contoh: Karyawan Swasta"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Nomor HP -->
                    <div>
                        <label for="nomor_hp" class="block text-sm font-medium text-text-primary">
                            Nomor Telepon / WhatsApp
                        </label>
                        <input type="tel" id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp', $warga->nomor_hp) }}"
                            placeholder="Contoh: 08123456789"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border @error('nomor_hp') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('nomor_hp')
                            <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Sosio Ekonomi -->
                    <div>
                        <label for="status_sosio_ekonomi" class="block text-sm font-medium text-text-primary">
                            Status Sosio Ekonomi
                        </label>
                        <select id="status_sosio_ekonomi" name="status_sosio_ekonomi"
                            class="mt-1.5 w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Pilih Kategori</option>
                            <option value="MAMPU" {{ old('status_sosio_ekonomi', $warga->status_sosio_ekonomi) === 'MAMPU' ? 'selected' : '' }}>Mampu</option>
                            <option value="MENENGAH" {{ old('status_sosio_ekonomi', $warga->status_sosio_ekonomi) === 'MENENGAH' ? 'selected' : '' }}>Menengah</option>
                            <option value="PRA_SEJAHTERA" {{ old('status_sosio_ekonomi', $warga->status_sosio_ekonomi) === 'PRA_SEJAHTERA' ? 'selected' : '' }}>Pra Sejahtera</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit & Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
                <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="px-5 py-2.5 bg-surface hover:bg-background text-text-secondary border border-border rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
