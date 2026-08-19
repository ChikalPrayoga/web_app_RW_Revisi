<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Requests;

use App\Enums\StatusPengajuanSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validasi query parameter untuk daftar pengajuan surat (pengurus).
 *
 * Digunakan untuk endpoint `GET /surat/pengajuan`.
 * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
 *
 * @see API_SPECIFICATION.md §3.4.3
 */
class ListPengajuanSuratRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization via Policy ditangani di Controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_status' => ['nullable', new Enum(StatusPengajuanSurat::class)],
            'jenis_surat' => ['nullable', 'string'],
            'rt_code' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
