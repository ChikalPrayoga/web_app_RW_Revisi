<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use App\Enums\VerificationDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class VerifyWargaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole(RoleName::SEKRETARIS_RW->value);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', new Enum(VerificationDecision::class)],
            'rejection_notes' => ['required_if:decision,'.VerificationDecision::REJECTED->value, 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan verifikasi wajib diisi',
            'decision.in' => 'Keputusan verifikasi harus APPROVED atau REJECTED',
            'rejection_notes.required_if' => 'Catatan penolakan wajib diisi jika permohonan ditolak',
        ];
    }
}
