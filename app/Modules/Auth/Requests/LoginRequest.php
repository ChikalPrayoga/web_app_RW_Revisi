<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi payload login.
 * Memastikan format email valid dan password tidak kosong
 * sebelum business logic dieksekusi di AuthService.
 *
 * @see API_SPECIFICATION.md §3.1.1
 */
class LoginRequest extends FormRequest
{
    /**
     * Seluruh endpoint login bersifat publik — tidak memerlukan otorisasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi input login.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia agar konsisten dengan respons API.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Kolom email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email tidak boleh lebih dari 100 karakter.',
            'password.required' => 'Kolom kata sandi wajib diisi.',
            'password.string' => 'Kata sandi harus berupa teks.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
