<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListKasKeluarRequest extends FormRequest
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
            'kategori' => ['nullable', 'string', 'max:100'],
            'periode_bulan' => ['nullable', 'integer', 'between:1,12'],
            'periode_tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
