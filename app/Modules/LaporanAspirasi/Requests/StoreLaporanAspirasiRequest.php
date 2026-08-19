<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanAspirasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pengiriman laporan bersifat publik — tidak memerlukan autentikasi
        return true;
    }

    public function rules(): array
    {
        return [
            'judul_laporan' => ['required', 'string', 'max:150'],
            'teks_keluhan' => ['required', 'string', 'min:20'],
            'lokasi_kejadian' => ['nullable', 'string', 'max:500'],
            // NIK opsional — jika diisi digunakan untuk lookup warga_id
            'nik' => ['nullable', 'string', 'digits:16'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul_laporan.required' => 'Judul laporan wajib diisi.',
            'judul_laporan.max' => 'Judul laporan maksimal 150 karakter.',
            'teks_keluhan.required' => 'Deskripsi keluhan wajib diisi.',
            'teks_keluhan.min' => 'Deskripsi keluhan minimal 20 karakter.',
            'nik.digits' => 'NIK harus berupa 16 digit angka.',
        ];
    }
}
