<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use App\Enums\StatusWarga;
use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListWargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'rt_code' => ['nullable', 'string', 'max:20'],
            'no_kk_hash' => ['nullable', 'string', 'size:64'],
            'search' => ['nullable', 'string', 'max:100'],
            'verification_status' => ['nullable', new Enum(VerificationStatus::class)],
            'status_warga' => ['nullable', new Enum(StatusWarga::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
