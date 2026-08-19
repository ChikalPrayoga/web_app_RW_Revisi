@extends('layouts.public')

@section('public-content')
<div class="max-w-xl mx-auto px-4 sm:px-6 space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-display font-semibold text-text-primary">Lacak Surat</h1>
        <p class="mt-1 text-sm text-text-secondary">Masukkan kode pelacakan yang Anda terima saat mengajukan surat.</p>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-sm bg-danger-light border border-danger/30 text-danger flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div>
            @foreach($errors->all() as $error)
                <p class="text-sm">{{ $error }}</p>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-surface rounded-md border border-border shadow-sm p-6 sm:p-8">
        <form method="GET" action="" id="form-lacak-surat" class="space-y-5">
            <div>
                <label for="tracking_code_input" class="block text-xs font-semibold text-text-secondary uppercase tracking-wider mb-1.5">
                    Kode Pelacakan <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    id="tracking_code_input"
                    name="tracking_code_input"
                    placeholder="Contoh: SRT-20260817-A8F3K2"
                    autocomplete="off"
                    class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-sm font-mono text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors uppercase"
                >
                <p class="mt-1.5 text-xs text-text-secondary">Format: SRT-YYYYMMDD-XXXXXX (contoh: SRT-20260817-A8F3K2)</p>
            </div>

            <button
                type="button"
                id="btn-lacak"
                onclick="lacakSurat()"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Cek Status Surat
            </button>
        </form>
    </div>

    <div class="text-center">
        <p class="text-sm text-text-secondary">Belum punya surat? <a href="{{ route('persuratan.public.create') }}" class="text-primary font-medium hover:underline">Ajukan sekarang</a></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function lacakSurat() {
    const code = document.getElementById('tracking_code_input').value.trim().toUpperCase();
    if (!code) {
        alert('Masukkan kode pelacakan terlebih dahulu');
        return;
    }
    window.location.href = '/surat/lacak/' + encodeURIComponent(code);
}

document.getElementById('form-lacak-surat').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lacakSurat(); }
});
</script>
@endpush
