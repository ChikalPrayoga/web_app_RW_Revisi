@extends('layouts.dashboard')

@section('title', 'Verifikasi Data Warga — ' . $warga->nama_lengkap)
@section('breadcrumb', 'Verifikasi Warga')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header Back & Title -->
    <div class="flex items-center gap-3">
        <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="p-2 text-text-secondary hover:text-text-primary hover:bg-surface border border-border rounded-sm transition-colors min-h-touch min-w-touch flex items-center justify-center" aria-label="Kembali ke Detail">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">
                Verifikasi Data Kependudukan
            </h1>
            <p class="text-sm text-text-secondary mt-0.5">
                Kewenangan Verifikasi: <span class="font-semibold text-primary">Sekretaris RW 047</span>
            </p>
        </div>
    </div>

    <!-- Ringkasan Data Warga Card -->
    <div class="bg-surface rounded-md border border-border shadow-sm p-6">
        <h2 class="text-sm font-semibold text-text-secondary uppercase tracking-wider border-b border-border pb-2 mb-4 font-display">
            Ringkasan Data Warga yang Diverifikasi
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-xs text-text-secondary block">Nama Lengkap</span>
                <span class="font-semibold text-text-primary">{{ $warga->nama_lengkap }}</span>
            </div>
            <div>
                <span class="text-xs text-text-secondary block">NIK (Masked)</span>
                <span class="font-mono text-text-primary">{{ $warga->nik_masked }}</span>
            </div>
            <div>
                <span class="text-xs text-text-secondary block">Nomor KK (Masked)</span>
                <span class="font-mono text-text-primary">{{ $warga->no_kk_masked }}</span>
            </div>
            <div>
                <span class="text-xs text-text-secondary block">Wilayah RT</span>
                <span class="font-medium text-primary">RT {{ $warga->kartuKeluarga?->rt_code ?? '-' }}</span>
            </div>
            <div>
                <span class="text-xs text-text-secondary block">Tempat, Tanggal Lahir</span>
                <span class="text-text-primary">{{ $warga->tempat_lahir }}, {{ $warga->tanggal_lahir?->format('d/m/Y') }}</span>
            </div>
            <div>
                <span class="text-xs text-text-secondary block">Hubungan Keluarga</span>
                <span class="text-text-primary">{{ $warga->status_hubungan_keluarga }}</span>
            </div>
        </div>
    </div>

    <!-- General Error Banner -->
    @if ($errors->any())
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            <h2 class="text-sm font-bold">Terdapat kesalahan pada formulir keputusan:</h2>
            <ul class="mt-1 text-xs list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Form Verifikasi -->
    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('kependudukan.warga.verify', ['nik_hash' => $warga->nik_hash]) }}" id="verify-form" class="space-y-6" novalidate>
            @csrf

            <!-- Keputusan Radio -->
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-3">
                    Pilih Keputusan Verifikasi <span class="text-danger font-bold">*</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Option APPROVED -->
                    <label class="relative flex items-center p-4 bg-surface border-2 rounded-md cursor-pointer hover:border-success/50 transition-colors border-border has-[:checked]:border-success has-[:checked]:bg-success-light/30">
                        <input type="radio" name="decision" value="APPROVED" {{ old('decision') === 'APPROVED' ? 'checked' : '' }}
                            class="w-4 h-4 text-success focus:ring-success" onchange="toggleRejectionNotes(false)">
                        <div class="ml-3">
                            <span class="block text-sm font-semibold text-text-primary">Setujui (APPROVED)</span>
                            <span class="block text-xs text-text-secondary mt-0.5">Data warga valid dan disahkan ke dalam basis data RW 047</span>
                        </div>
                    </label>

                    <!-- Option REJECTED -->
                    <label class="relative flex items-center p-4 bg-surface border-2 rounded-md cursor-pointer hover:border-danger/50 transition-colors border-border has-[:checked]:border-danger has-[:checked]:bg-danger-light/30">
                        <input type="radio" name="decision" value="REJECTED" {{ old('decision') === 'REJECTED' ? 'checked' : '' }}
                            class="w-4 h-4 text-danger focus:ring-danger" onchange="toggleRejectionNotes(true)">
                        <div class="ml-3">
                            <span class="block text-sm font-semibold text-text-primary">Tolak (REJECTED)</span>
                            <span class="block text-xs text-text-secondary mt-0.5">Data warga ditolak karena tidak sesuai atau tidak lengkap</span>
                        </div>
                    </label>
                </div>
                @error('decision')
                    <p class="mt-2 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Catatan Penolakan (Dynamic Display) -->
            <div id="rejection-notes-container" class="{{ old('decision') === 'REJECTED' ? '' : 'hidden' }} space-y-2">
                <label for="rejection_notes" class="block text-sm font-medium text-text-primary">
                    Catatan Penolakan Verifikasi <span class="text-danger font-bold">*</span>
                </label>
                <textarea id="rejection_notes" name="rejection_notes" rows="4" maxlength="1000"
                    placeholder="Jelaskan alasan penolakan data ini agar Ketua RT dapat memperbaiki dan mengajukan ulang..."
                    class="w-full px-3.5 py-2.5 bg-surface border @error('rejection_notes') border-danger @else border-border @enderror rounded-sm text-sm text-text-primary placeholder-text-secondary/40 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('rejection_notes') }}</textarea>
                <div class="flex items-center justify-between text-xs text-text-secondary">
                    <span>Wajib diisi jika permohonan ditolak</span>
                    <span id="rejection-char-counter">0/1000</span>
                </div>
                @error('rejection_notes')
                    <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-border">
                <a href="{{ route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash]) }}" class="px-5 py-2.5 bg-surface hover:bg-background text-text-secondary border border-border rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors">
                    Batal
                </a>
                <button type="button" onclick="openConfirmationModal()" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-sm text-sm font-medium min-h-touch flex items-center justify-center transition-colors shadow-sm gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Kirim Keputusan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div id="confirmation-modal" class="fixed inset-0 z-50 bg-text-primary/50 hidden flex items-center justify-center p-4">
    <div class="bg-surface rounded-md border border-border shadow-lg max-w-md w-full p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-warning-light text-warning flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-text-primary font-display">Konfirmasi Keputusan Verifikasi</h3>
                <p class="text-xs text-text-secondary mt-1 leading-relaxed">
                    Keputusan verifikasi data kependudukan ini bersifat resmi. Pastikan Anda telah memeriksa kesesuaian data warga dengan cermat.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
            <button type="button" onclick="closeConfirmationModal()" class="px-4 py-2 bg-surface hover:bg-background text-text-secondary border border-border rounded-sm text-sm font-medium min-h-touch">
                Batal
            </button>
            <button type="button" onclick="document.getElementById('verify-form').submit()" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-sm text-sm font-medium min-h-touch shadow-sm">
                Ya, Simpan Keputusan
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleRejectionNotes(show) {
        const container = document.getElementById('rejection-notes-container');
        const textarea = document.getElementById('rejection_notes');
        if (show) {
            container.classList.remove('hidden');
            textarea.setAttribute('required', 'required');
        } else {
            container.classList.add('hidden');
            textarea.removeAttribute('required');
        }
    }

    function openConfirmationModal() {
        const decisionSelected = document.querySelector('input[name="decision"]:checked');
        if (!decisionSelected) {
            alert('Pilih salah satu keputusan (Setujui atau Tolak) terlebih dahulu.');
            return;
        }

        if (decisionSelected.value === 'REJECTED') {
            const notes = document.getElementById('rejection_notes').value.trim();
            if (!notes) {
                alert('Catatan penolakan wajib diisi jika keputusan adalah Tolak.');
                document.getElementById('rejection_notes').focus();
                return;
            }
        }

        document.getElementById('confirmation-modal').classList.remove('hidden');
    }

    function closeConfirmationModal() {
        document.getElementById('confirmation-modal').classList.add('hidden');
    }

    const rejectionTextarea = document.getElementById('rejection_notes');
    const charCounter = document.getElementById('rejection-char-counter');
    if (rejectionTextarea && charCounter) {
        rejectionTextarea.addEventListener('input', function() {
            charCounter.textContent = this.value.length + '/1000';
        });
    }
</script>
@endpush
@endsection
