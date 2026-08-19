@extends('layouts.public')

@section('public-content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
            Ajukan Surat Keterangan
        </h1>
        <p class="mt-1 text-sm text-text-secondary">
            Isi formulir di bawah untuk mengajukan surat dari RW 047. Proses verifikasi memerlukan persetujuan RT dan RW.
        </p>
    </div>

    {{-- Form Errors --}}
    @if($errors->any())
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <p class="text-sm font-semibold mb-1">Terdapat kesalahan pada formulir:</p>
            <ul class="text-sm list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Form --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('persuratan.public.store') }}" id="form-pengajuan-surat" class="space-y-6">
            @csrf

            {{-- NIK --}}
            <div>
                <label for="nik" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Nomor Induk Kependudukan (NIK) <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="nik"
                    name="nik"
                    value="{{ old('nik') }}"
                    maxlength="16"
                    inputmode="numeric"
                    pattern="[0-9]{16}"
                    placeholder="Masukkan 16 digit NIK"
                    autocomplete="off"
                    class="w-full px-3.5 py-2.5 bg-surface border {{ $errors->has('nik') ? 'border-danger ring-1 ring-danger' : 'border-border' }} rounded-sm text-sm font-mono text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                >
                @error('nik')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-text-secondary">NIK digunakan untuk verifikasi identitas dan tidak disimpan dalam sistem pengajuan.</p>
            </div>

            {{-- Jenis Surat --}}
            <div>
                <label for="jenis_surat" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Jenis Surat <span class="text-danger">*</span>
                </label>
                <select
                    id="jenis_surat"
                    name="jenis_surat"
                    class="w-full px-3.5 py-2.5 bg-surface border {{ $errors->has('jenis_surat') ? 'border-danger ring-1 ring-danger' : 'border-border' }} rounded-sm text-sm text-text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors appearance-none"
                    style="background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1rem; padding-right: 2.5rem;"
                >
                    <option value="">— Pilih jenis surat —</option>
                    <option value="SURAT_PENGANTAR" {{ old('jenis_surat') === 'SURAT_PENGANTAR' ? 'selected' : '' }}>
                        Surat Pengantar
                    </option>
                    <option value="SURAT_KETERANGAN_DOMISILI" {{ old('jenis_surat') === 'SURAT_KETERANGAN_DOMISILI' ? 'selected' : '' }}>
                        Surat Keterangan Domisili
                    </option>
                </select>
                @error('jenis_surat')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            {{-- Keperluan --}}
            <div>
                <label for="keperluan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Keperluan / Tujuan Pengajuan <span class="text-danger">*</span>
                </label>
                <textarea
                    id="keperluan"
                    name="keperluan"
                    rows="4"
                    maxlength="1000"
                    placeholder="Contoh: Pengurusan administrasi perpanjangan KTP di Dinas Kependudukan..."
                    class="w-full px-3.5 py-2.5 bg-surface border {{ $errors->has('keperluan') ? 'border-danger ring-1 ring-danger' : 'border-border' }} rounded-sm text-sm text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                >{{ old('keperluan') }}</textarea>
                @error('keperluan')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-text-secondary">Minimal 10 karakter, maksimal 1000 karakter.</p>
            </div>

            {{-- Info Box --}}
            <div class="p-4 bg-primary-light/40 border border-primary/20 rounded-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-text-secondary">
                    <p class="font-semibold text-text-primary mb-0.5">Alur Verifikasi</p>
                    <p>Pengajuan Anda akan melalui: <strong>Ketua RT</strong> → <strong>Sekretaris RW</strong> → <strong>Ketua RW</strong>. Simpan kode pelacakan yang diberikan setelah pengajuan berhasil.</p>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                    type="submit"
                    id="btn-submit-surat"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Kirim Pengajuan
                </button>
                <a href="{{ route('persuratan.public.track') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
                    Lacak Surat
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
