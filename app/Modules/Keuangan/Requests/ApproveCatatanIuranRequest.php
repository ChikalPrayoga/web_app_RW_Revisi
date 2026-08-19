<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveCatatanIuranRequest extends FormRequest
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
            'action' => ['required', 'string', Rule::in(['APPROVE', 'REJECT'])],
            'rejection_notes' => ['required_if:action,REJECT', 'nullable', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi persetujuan/penolakan wajib dipilih.',
            'action.in' => 'Aksi harus berupa APPROVE atau REJECT.',
            'rejection_notes.required_if' => 'Alasan penolakan wajib diisi ketika aksi REJECT',
            'rejection_notes.min' => 'Alasan penolakan minimal 5 karakter.',
        ];
    }
}
