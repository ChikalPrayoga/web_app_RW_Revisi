<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Masuk — SIM Layanan Warga RW 047</title>

    <!-- Google Fonts: Fraunces & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-text-primary antialiased bg-background flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none sm:px-0 sm:w-80"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo & Identitas RW -->
        <div class="flex justify-center">
            <div class="w-14 h-14 rounded-md bg-primary flex items-center justify-center text-white shadow-md">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </div>
        <h1 class="mt-4 text-center text-2xl sm:text-3xl font-display font-semibold text-primary">
            SIM Layanan Warga
        </h1>
        <p class="mt-1 text-center text-sm text-text-secondary">
            Rukun Warga 047 — Akses Masuk Pengurus & Warga
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <!-- Card Login -->
        <div class="bg-surface py-8 px-6 sm:px-10 rounded-md border border-border shadow-sm">
            <form id="login-form" method="POST" action="{{ route('login.post') }}" class="space-y-6" novalidate>
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-text-primary">
                        Alamat Email <span class="text-danger font-bold" aria-hidden="true">*</span>
                    </label>
                    <div class="mt-1.5 relative">
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="block w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-text-primary placeholder-text-secondary/50 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-shadow"
                            placeholder="nama@rw047.id"
                            value="{{ old('email') }}">
                    </div>
                    <p id="email-error" class="mt-1.5 text-xs text-danger font-medium hidden"></p>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-text-primary">
                            Kata Sandi <span class="text-danger font-bold" aria-hidden="true">*</span>
                        </label>
                    </div>
                    <div class="mt-1.5 relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full px-3.5 py-2.5 bg-surface border border-border rounded-sm text-text-primary placeholder-text-secondary/50 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-shadow"
                            placeholder="Minimal 8 karakter">
                    </div>
                    <p id="password-error" class="mt-1.5 text-xs text-danger font-medium hidden"></p>
                </div>

                <!-- Submit Button with Loading State -->
                <div>
                    <button id="submit-btn" type="submit"
                        class="w-full min-h-touch flex justify-center items-center py-2.5 px-4 rounded-sm shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btn-text">Masuk ke Sistem</span>
                        <span id="btn-spinner" class="hidden items-center gap-2">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer / Bantuan -->
        <p class="mt-6 text-center text-xs text-text-secondary">
            Mengalami kendala saat masuk? Hubungi pengurus RT/RW setempat.
        </p>
    </div>

    <!-- Login Form Handler Script (Session-based, zero token in localStorage) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('login-form');
            const submitBtn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailError = document.getElementById('email-error');
            const passwordError = document.getElementById('password-error');

            function setLoading(isLoading) {
                if (isLoading) {
                    submitBtn.disabled = true;
                    btnText.classList.add('hidden');
                    btnSpinner.classList.remove('hidden');
                    btnSpinner.classList.add('flex');
                } else {
                    submitBtn.disabled = false;
                    btnText.classList.remove('hidden');
                    btnSpinner.classList.add('hidden');
                    btnSpinner.classList.remove('flex');
                }
            }

            function clearErrors() {
                emailError.classList.add('hidden');
                emailError.textContent = '';
                emailInput.classList.remove('border-danger', 'focus:ring-danger');

                passwordError.classList.add('hidden');
                passwordError.textContent = '';
                passwordInput.classList.remove('border-danger', 'focus:ring-danger');
            }

            function showFieldError(field, message) {
                if (field === 'email') {
                    emailError.textContent = message;
                    emailError.classList.remove('hidden');
                    emailInput.classList.add('border-danger', 'focus:ring-danger');
                } else if (field === 'password') {
                    passwordError.textContent = message;
                    passwordError.classList.remove('hidden');
                    passwordInput.classList.add('border-danger', 'focus:ring-danger');
                }
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearErrors();

                const email = emailInput.value.trim();
                const password = passwordInput.value;

                // Client-side quick validation
                let hasClientError = false;
                if (!email) {
                    showFieldError('email', 'Kolom email wajib diisi.');
                    hasClientError = true;
                }
                if (!password) {
                    showFieldError('password', 'Kolom kata sandi wajib diisi.');
                    hasClientError = true;
                }

                if (hasClientError) return;

                setLoading(true);

                try {
                    const formData = new FormData(form);

                    // Mengirim ke endpoint autentikasi web session (cookie httpOnly)
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        if (window.toast) {
                            window.toast.success('Login berhasil. Mengalihkan ke dashboard...');
                        }

                        // Mengalihkan ke dashboard (autentikasi tersimpan aman di httpOnly session cookie)
                        setTimeout(() => {
                            window.location.href = data.redirect || '{{ route("dashboard") }}';
                        }, 500);

                    } else if (response.status === 429) {
                        // Rate limit exceeded (NFR-01)
                        if (window.toast) {
                            window.toast.warning(data.message || 'Terlalu banyak percobaan login. Silakan coba lagi dalam beberapa saat.');
                        }
                    } else if (response.status === 422) {
                        // Validation error or invalid credentials
                        if (data.errors) {
                            for (const [key, msgs] of Object.entries(data.errors)) {
                                showFieldError(key, msgs[0]);
                            }
                        }
                        if (window.toast) {
                            window.toast.danger(data.message || 'Email atau kata sandi yang Anda masukkan salah.');
                        }
                    } else {
                        if (window.toast) {
                            window.toast.danger(data.message || 'Terjadi kesalahan pada sistem. Silakan coba lagi.');
                        }
                    }
                } catch (error) {
                    console.error('Login Error:', error);
                    if (window.toast) {
                        window.toast.danger('Gagal terhubung ke server. Periksa koneksi internet Anda dan coba lagi.');
                    }
                } finally {
                    setLoading(false);
                }
            });
        });
    </script>
</body>
</html>
