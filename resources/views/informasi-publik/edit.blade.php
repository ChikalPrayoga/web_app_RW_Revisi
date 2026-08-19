@extends('layouts.dashboard')

@section('title', 'Edit Informasi Publik')

@section('content')
<div class="max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-border pb-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-text-primary">Edit Informasi</h1>
            <p class="text-xs text-text-secondary mt-1">Perbarui data konten pengumuman, berita, atau agenda</p>
        </div>
        <a href="{{ route('informasi-publik.index') }}" class="text-xs font-semibold text-text-secondary hover:text-primary transition-colors">
            &larr; Kembali ke Daftar
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-surface p-6 rounded-md border border-border shadow-xs">
        <form method="POST" action="{{ route('informasi-publik.update', $informasi->id) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Judul --}}
            <div>
                <label for="judul" class="block text-xs font-semibold text-text-primary mb-1">
                    Judul Informasi <span class="text-danger">*</span>
                </label>
                <input type="text" id="judul" name="judul" value="{{ old('judul', $informasi->judul) }}" required maxlength="150"
                    class="w-full text-xs bg-background border @error('judul') border-danger @else border-border @enderror rounded-sm px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary" />
                @error('judul')
                <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Grid Kategori & Status --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Kategori --}}
                <div>
                    <label for="kategori" class="block text-xs font-semibold text-text-primary mb-1">
                        Kategori Konten <span class="text-danger">*</span>
                    </label>
                    <select id="kategori" name="kategori" required
                        class="w-full text-xs bg-background border @error('kategori') border-danger @else border-border @enderror rounded-sm px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary">
                        @foreach($kategoris as $kat)
                        <option value="{{ $kat->value }}" {{ (old('kategori', $informasi->kategori->value) === $kat->value) ? 'selected' : '' }}>
                            {{ $kat->label() }}
                        </option>
                        @endforeach
                    </select>
                    @error('kategori')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-xs font-semibold text-text-primary mb-1">
                        Status Publikasi <span class="text-danger">*</span>
                    </label>
                    <select id="status" name="status" required
                        class="w-full text-xs bg-background border @error('status') border-danger @else border-border @enderror rounded-sm px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary">
                        @foreach($statuses as $st)
                        <option value="{{ $st->value }}" {{ (old('status', $informasi->status->value) === $st->value) ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                        @endforeach
                    </select>
                    @error('status')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Grid Tanggal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Tanggal Publikasi --}}
                <div>
                    <label for="tanggal_publikasi" class="block text-xs font-semibold text-text-primary mb-1">
                        Tanggal Publikasi
                    </label>
                    <input type="date" id="tanggal_publikasi" name="tanggal_publikasi"
                        value="{{ old('tanggal_publikasi', $informasi->tanggal_publikasi?->toDateString()) }}"
                        class="w-full text-xs bg-background border @error('tanggal_publikasi') border-danger @else border-border @enderror rounded-sm px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary" />
                    @error('tanggal_publikasi')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal Agenda --}}
                <div id="agenda-date-container">
                    <label for="tanggal_agenda" class="block text-xs font-semibold text-text-primary mb-1">
                        Tanggal Agenda Kegiatan <span id="agenda-required-mark" class="text-danger hidden">*</span>
                    </label>
                    <input type="date" id="tanggal_agenda" name="tanggal_agenda"
                        value="{{ old('tanggal_agenda', $informasi->tanggal_agenda?->toDateString()) }}"
                        class="w-full text-xs bg-background border @error('tanggal_agenda') border-danger @else border-border @enderror rounded-sm px-3 py-2 focus:ring-1 focus:ring-primary focus:border-primary" />
                    <span class="text-[10px] text-text-secondary">Wajib diisi jika memilih kategori Agenda Kegiatan</span>
                    @error('tanggal_agenda')
                    <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Konten / Isi --}}
            <div>
                <label for="konten" class="block text-xs font-semibold text-text-primary mb-1">
                    Isi / Konten Lengkap <span class="text-danger">*</span>
                </label>
                <textarea id="konten" name="konten" rows="8" required
                    class="w-full text-xs bg-background border @error('konten') border-danger @else border-border @enderror rounded-sm p-3 focus:ring-1 focus:ring-primary focus:border-primary leading-relaxed">{{ old('konten', $informasi->konten) }}</textarea>
                @error('konten')
                <p class="text-[11px] text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                <a href="{{ route('informasi-publik.index') }}"
                    class="px-4 py-2 text-xs font-semibold text-text-secondary hover:text-text-primary border border-border rounded-sm transition-colors">
                    Batal
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-xs font-semibold rounded-sm shadow-xs transition-colors min-h-touch">
                    Perbarui Informasi
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kategoriSelect = document.getElementById('kategori');
        const agendaMark = document.getElementById('agenda-required-mark');
        const tanggalAgendaInput = document.getElementById('tanggal_agenda');

        function toggleAgendaRequirement() {
            if (kategoriSelect.value === 'AGENDA') {
                agendaMark.classList.remove('hidden');
                tanggalAgendaInput.setAttribute('required', 'required');
            } else {
                agendaMark.classList.add('hidden');
                tanggalAgendaInput.removeAttribute('required');
            }
        }

        kategoriSelect.addEventListener('change', toggleAgendaRequirement);
        toggleAgendaRequirement();
    });
</script>
@endpush
@endsection
