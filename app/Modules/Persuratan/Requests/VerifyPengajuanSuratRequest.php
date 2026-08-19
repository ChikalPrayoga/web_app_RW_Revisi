<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Requests;

use App\Enums\ReviewAction;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validasi aksi verifikasi/review pengajuan surat oleh pengurus RT/RW.
 *
 * Digunakan untuk endpoint `POST /surat/pengajuan/{id}/verify`.
 * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW (sesuai API_SPECIFICATION.md §3.4.4).
 *
 * @see API_SPECIFICATION.md §3.4.4
 */
class VerifyPengajuanSuratRequest extends FormRequest
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
            RoleName::KETUA_RW->value,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', new Enum(ReviewAction::class)],
            'catatan' => [
                'nullable',
                'string',
                'max:500',
                // Catatan wajib ada jika action REJECT
                'required_if:action,REJECT',
            ],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi verifikasi wajib dipilih (APPROVE atau REJECT)',
            'action.*' => 'Aksi verifikasi tidak valid',
            'catatan.required_if' => 'Catatan penolakan wajib diisi saat menolak pengajuan',
            'catatan.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
