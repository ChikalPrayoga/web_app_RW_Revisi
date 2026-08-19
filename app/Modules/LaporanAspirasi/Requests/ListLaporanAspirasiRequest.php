<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Requests;

use App\Enums\StatusLaporan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLaporanAspirasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_status' => ['nullable', Rule::enum(StatusLaporan::class)],
            'sort_by' => ['nullable', Rule::in(['submitted_at'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
