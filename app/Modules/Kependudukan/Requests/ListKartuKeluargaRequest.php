<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class ListKartuKeluargaRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
