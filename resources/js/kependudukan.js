/**
 * SIM Layanan Warga RW 047 — Kependudukan Client-side UI Enhancements
 *
 * Catatan Arsitektur:
 * File ini HANYA menangani progressive enhancement dan client-side UX (onBlur validation,
 * loading indicator, character counter). Tidak melakukan fetch() ke API bisnis.
 */

document.addEventListener('DOMContentLoaded', () => {
    init16DigitInputs();
    initFormSubmitLoading();
});

/**
 * Validasi interaktif onBlur untuk input 16 digit (NIK / No. KK)
 */
function init16DigitInputs() {
    const inputs = document.querySelectorAll('input#nik, input#no_kk');

    inputs.forEach((input) => {
        // Filter input hanya angka
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Validasi visual saat kehilangan fokus (onBlur)
        input.addEventListener('blur', function () {
            const val = this.value.trim();
            const fieldName = this.id === 'nik' ? 'NIK' : 'Nomor KK';

            let errorEl = this.parentElement.parentElement.querySelector('.js-field-error');
            if (!errorEl) {
                errorEl = document.createElement('p');
                errorEl.className = 'js-field-error mt-1 text-xs text-danger font-medium';
                this.parentElement.parentElement.appendChild(errorEl);
            }

            if (val.length > 0 && val.length !== 16) {
                this.classList.add('border-danger');
                this.classList.remove('border-border', 'border-success');
                errorEl.textContent = `${fieldName} harus tepat 16 digit angka (saat ini ${val.length} digit).`;
                errorEl.classList.remove('hidden');
            } else if (val.length === 16) {
                this.classList.remove('border-danger');
                this.classList.add('border-border');
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            } else {
                this.classList.remove('border-danger');
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        });
    });
}

/**
 * Mencegah submit ganda dengan mengubah tombol ke loading state
 */
function initFormSubmitLoading() {
    const forms = document.querySelectorAll('#warga-form, #warga-edit-form, #kk-form, #verify-form');

    forms.forEach((form) => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memproses...</span>
                `;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            }
        });
    });
}
