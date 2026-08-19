@extends('layouts.dashboard')

@section('title', 'Verifikasi Pengajuan')
@section('breadcrumb', 'Verifikasi Pengajuan')

@section('content')
@php
    $user = Auth::user();
    $roleName = $user?->role?->name ?? '';
    $currentStatus = $pengajuan->current_status?->value ?? 'SUBMITTED';

    $statusConfig = [
        'SUBMITTED' => ['label' => 'Menunggu Review RT', 'class' => 'bg-warning-light text-warning border-warning/20'],
        'RT_REVIEW' => ['label' => 'Disetujui RT — Menunggu RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
        'RW_REVIEW' => ['label' => 'Review Ketua RW', 'class' => 'bg-primary-light text-primary border-primary/20'],
    ];
    $sc = $statusConfig[$currentStatus] ?? ['label' => $currentStatus, 'class' => 'bg-background text-text-secondary border-border'];
@endphp

<div class="max-w-2xl mx-auto space-y-6">
    {{-- Back --}}
    <div>
        <a href="{{ route('persuratan.show', $pengajuan->pengajuan_id) }}" class="inline-flex items-center gap-1.5 text-sm text-text-secondary hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Detail
        </a>
    </div>

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-display font-semibold text-text-primary">Verifikasi Pengajuan</h1>
        <p class="mt-1 text-sm text-text-secondary">Pilih keputusan untuk pengajuan surat ini. Penolakan memerlukan catatan alasan.</p>
    </div>

    {{-- Summary Card --}}
    <div class="bg-surface rounded-md border border-border shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Kode Pelacakan</p>
                <span class="font-mono text-base font-semibold text-primary tracking-widest">{{ $pengajuan->tracking_code }}</span>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-sm text-sm font-semibold border {{ $sc['class'] }}">{{ $sc['label'] }}</span>
        </div>
        <div class="mt-4 pt-4 border-t border-border grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Pemohon</p>
                <p class="font-medium text-text-primary">{{ optional($pengajuan->warga)->nama_lengkap ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary mb-0.5">Jenis Surat</p>
                <p class="font-medium text-text-primary">
                    {{ $pengajuan->jenis_surat?->value === 'SURAT_PENGANTAR' ? 'Surat Pengantar' : 'Surat Keterangan Domisili' }}
                </p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-text-secondary mb-0.5">Keperluan</p>
                <p class="text-text-primary leading-relaxed">{{ $pengajuan->keperluan }}</p>
            </div>
        </div>
    </div>

    {{-- Context Info by Role --}}
    @if($roleName === 'KETUA_RT')
    <div class="p-4 bg-primary-light/40 border border-primary/20 rounded-sm flex items-start gap-3">
        <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-sm">
            <p class="font-semibold text-text-primary">Review Ketua RT</p>
            <p class="text-text-secondary mt-0.5">Jika disetujui, pengajuan akan diteruskan ke Sekretaris/Ketua RW untuk verifikasi final.</p>
        </div>
    </div>
    @elseif(in_array($roleName, ['SEKRETARIS_RW', 'KETUA_RW']))
    <div class="p-4 bg-primary-light/40 border border-primary/20 rounded-sm flex items-start gap-3">
        <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-sm">
            <p class="font-semibold text-text-primary">Verifikasi
                @if($roleName === 'SEKRETARIS_RW') Sekretaris RW @else Ketua RW @endif
            </p>
            <p class="text-text-secondary mt-0.5">
                @if($roleName === 'SEKRETARIS_RW' && $currentStatus === 'RT_REVIEW')
                    Jika disetujui, pengajuan akan diteruskan ke Ketua RW untuk persetujuan final.
                @else
                    Jika disetujui, nomor surat resmi akan diterbitkan otomatis.
                @endif
            </p>
        </div>
    </div>
    @endif

    {{-- Verify Form --}}
    @if($errors->any())
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <ul class="text-sm list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('persuratan.verify', $pengajuan->pengajuan_id) }}" id="form-verifikasi">
            @csrf

            {{-- Action Selection --}}
            <div class="mb-6">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Keputusan</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer" id="label-approve">
                        <input type="radio" name="action" value="APPROVE" id="action-approve"
                            {{ old('action') !== 'REJECT' ? 'checked' : '' }}
                            class="peer sr-only"
                            onchange="toggleCatatan(this)">
                        <div class="flex items-center gap-3 p-4 rounded-sm border-2 border-border peer-checked:border-success peer-checked:bg-success-light transition-all">
                            <div class="w-10 h-10 rounded-full bg-success-light peer-checked:bg-success flex items-center justify-center flex-shrink-0 transition-colors">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-text-primary">Setujui</p>
                                <p class="text-xs text-text-secondary">Teruskan ke tahap berikutnya</p>
                            </div>
                        </div>
                    </label>

                    <label class="relative cursor-pointer" id="label-reject">
                        <input type="radio" name="action" value="REJECT" id="action-reject"
                            {{ old('action') === 'REJECT' ? 'checked' : '' }}
                            class="peer sr-only"
                            onchange="toggleCatatan(this)">
                        <div class="flex items-center gap-3 p-4 rounded-sm border-2 border-border peer-checked:border-danger peer-checked:bg-danger-light transition-all">
                            <div class="w-10 h-10 rounded-full bg-danger-light flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-text-primary">Tolak</p>
                                <p class="text-xs text-text-secondary">Hentikan proses pengajuan</p>
                            </div>
                        </div>
                    </label>
                </div>
                @error('action')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            {{-- Catatan (required jika REJECT) --}}
            <div id="field-catatan" class="{{ old('action') === 'REJECT' ? '' : 'hidden' }} mb-6">
                <label for="catatan" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Catatan Penolakan <span class="text-danger">*</span>
                </label>
                <textarea
                    id="catatan"
                    name="catatan"
                    rows="3"
                    maxlength="500"
                    placeholder="Jelaskan alasan penolakan pengajuan ini..."
                    class="w-full px-3.5 py-2.5 bg-surface border {{ $errors->has('catatan') ? 'border-danger ring-1 ring-danger' : 'border-border' }} rounded-sm text-sm text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                >{{ old('catatan') }}</textarea>
                @error('catatan')
                    <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            {{-- Optional Catatan for APPROVE --}}
            <div id="field-catatan-approve" class="{{ old('action') === 'REJECT' ? 'hidden' : '' }} mb-6">
                <label for="catatan-approve-input" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Catatan Tambahan <span class="text-text-secondary font-normal">(opsional)</span>
                </label>
                <textarea
                    id="catatan-approve-input"
                    name="catatan"
                    rows="2"
                    maxlength="500"
                    placeholder="Catatan opsional untuk pengurus berikutnya..."
                    class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                ></textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" id="btn-submit-verify"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm shadow-sm transition-colors min-h-touch">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Konfirmasi Keputusan
                </button>
                <a href="{{ route('persuratan.show', $pengajuan->pengajuan_id) }}"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-surface hover:bg-background border border-border text-text-secondary hover:text-text-primary text-sm font-medium rounded-sm transition-colors min-h-touch">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleCatatan(radio) {
    const fieldReject = document.getElementById('field-catatan');
    const fieldApprove = document.getElementById('field-catatan-approve');
    const catatanReject = document.getElementById('catatan');
    const catatanApproveInput = document.getElementById('catatan-approve-input');

    if (radio.value === 'REJECT') {
        fieldReject.classList.remove('hidden');
        fieldApprove.classList.add('hidden');
        catatanApproveInput.name = '';
        catatanReject.name = 'catatan';
    } else {
        fieldReject.classList.add('hidden');
        fieldApprove.classList.remove('hidden');
        catatanReject.name = '';
        catatanApproveInput.name = 'catatan';
    }
}
</script>
@endpush
