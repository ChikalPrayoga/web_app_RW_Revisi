/**
 * SIM Layanan Warga RW 047 - Global Toast Notification Utility
 * Sesuai spesifikasi UI_UX_SPECIFICATION.md §3.3
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        if (typeof document === 'undefined') return;

        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none sm:px-0 sm:w-80';
            document.body.appendChild(container);
        }
        this.container = container;
    }

    /**
     * @param {string} message - Pesan notifikasi
     * @param {'success'|'warning'|'danger'|'info'} type - Tipe notifikasi
     * @param {number|null} duration - Durasi milidetik (null untuk manual close)
     */
    show(message, type = 'info', duration = null) {
        if (!this.container) this.init();

        const config = {
            success: {
                bg: 'bg-success-light',
                border: 'border-success',
                text: 'text-text-primary',
                iconColor: 'text-success',
                icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
                autoDismiss: 4000,
            },
            warning: {
                bg: 'bg-warning-light',
                border: 'border-warning',
                text: 'text-text-primary',
                iconColor: 'text-warning',
                icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`,
                autoDismiss: 6000,
            },
            danger: {
                bg: 'bg-danger-light',
                border: 'border-danger',
                text: 'text-text-primary',
                iconColor: 'text-danger',
                icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
                autoDismiss: null, // Manual close per §3.3
            },
            info: {
                bg: 'bg-info-light',
                border: 'border-info',
                text: 'text-text-primary',
                iconColor: 'text-info',
                icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
                autoDismiss: 5000,
            },
        };

        const currentConfig = config[type] || config.info;
        const autoDismissTime = duration !== null ? duration : currentConfig.autoDismiss;

        const toast = document.createElement('div');
        toast.className = `${currentConfig.bg} border-l-4 ${currentConfig.border} ${currentConfig.text} p-4 rounded-md shadow-md flex items-start gap-3 pointer-events-auto transition-all duration-300 transform translate-y-[-10px] opacity-0`;
        
        toast.innerHTML = `
            <div class="flex-shrink-0 ${currentConfig.iconColor} mt-0.5">${currentConfig.icon}</div>
            <div class="flex-1 text-sm font-medium leading-5">${message}</div>
            <button type="button" class="flex-shrink-0 text-text-secondary hover:text-text-primary focus:outline-none p-1 rounded-sm min-w-touch min-h-touch flex items-center justify-center -mr-2 -mt-2" aria-label="Tutup notifikasi">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;

        this.container.appendChild(toast);

        // Animation enter
        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-[-10px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        });

        const closeBtn = toast.querySelector('button');
        const dismiss = () => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-[-10px]', 'opacity-0');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        };

        closeBtn.addEventListener('click', dismiss);

        if (autoDismissTime) {
            setTimeout(dismiss, autoDismissTime);
        }
    }

    success(message, duration = null) {
        this.show(message, 'success', duration);
    }

    warning(message, duration = null) {
        this.show(message, 'warning', duration);
    }

    danger(message, duration = null) {
        this.show(message, 'danger', duration);
    }

    info(message, duration = null) {
        this.show(message, 'info', duration);
    }
}

export const toast = new ToastManager();
if (typeof window !== 'undefined') {
    window.toast = toast;
}
