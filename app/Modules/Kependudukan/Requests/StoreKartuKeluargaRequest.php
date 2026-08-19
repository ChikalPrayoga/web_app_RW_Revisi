<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class StoreKartuKeluargaRequest extends FormRequest
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
            'no_kk' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'rt_code' => ['required', 'string', 'max:20'],
            'alamat_lengkap' => ['required', 'string', 'max:500'],
            'blok' => ['nullable', 'string', 'max:20'],
            'nomor_rumah' => ['nullable', 'string', 'max:20'],
            'status_kepemilikan_rumah' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'no_kk.required' => 'Nomor Kartu Keluarga wajib diisi',
            'no_kk.size' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka',
            'no_kk.regex' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka',
            'rt_code.required' => 'Kode RT wajib diisi',
            'alamat_lengkap.required' => 'Alamat lengkap wajib diisi',
            'status_kepemilikan_rumah.required' => 'Status kepemilikan rumah wajib diisi',
        ];
    }
}
