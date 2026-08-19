<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RekapIuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'rt_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode_bulan.required' => 'Periode bulan wajib diisi.',
            'periode_bulan.between' => 'Periode bulan harus antara 1 sampai 12.',
            'periode_tahun.required' => 'Periode tahun wajib diisi.',
        ];
    }
}
