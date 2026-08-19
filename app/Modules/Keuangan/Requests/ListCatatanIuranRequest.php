<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCatatanIuranRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(['PENDING', 'APPROVED', 'REJECTED'])],
            'iuran_type_id' => ['nullable', 'integer', 'exists:iuran_types,id'],
            'periode_bulan' => ['nullable', 'integer', 'between:1,12'],
            'periode_tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'rt_code' => ['nullable', 'string', 'max:10'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
