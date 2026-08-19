<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Requests;

use App\Enums\StatusLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization ditangani di Controller via Policy
    }

    public function rules(): array
    {
        return [
            'current_status' => [
                'required',
                Rule::enum(StatusLaporan::class),
            ],
            // catatan_tindak_lanjut wajib saat status RESOLVED
            'catatan_tindak_lanjut' => [
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_status.required' => 'Status laporan wajib diisi.',
            'current_status.enum' => 'Status laporan tidak valid.',
            'catatan_tindak_lanjut.min' => 'Catatan tindak lanjut minimal 5 karakter.',
        ];
    }
}
