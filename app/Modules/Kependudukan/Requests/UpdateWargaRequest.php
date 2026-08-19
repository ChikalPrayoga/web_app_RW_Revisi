<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use App\Enums\StatusWarga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateWargaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasAnyRole([
            RoleName::KETUA_RT->value,
            RoleName::SEKRETARIS_RW->value,
            RoleName::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'nomor_hp' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'status_hubungan_keluarga' => ['nullable', 'string', 'max:50'],
            'status_sosio_ekonomi' => ['nullable', 'string', 'max:50'],
            'status_warga' => ['nullable', new Enum(StatusWarga::class)],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'nomor_hp.regex' => 'Format nomor telepon tidak valid',
            'status_warga.in' => 'Status warga tidak valid',
        ];
    }
}
