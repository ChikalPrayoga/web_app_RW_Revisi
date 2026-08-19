<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Requests;

use App\Enums\JenisSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validasi pengajuan surat baru melalui Portal Warga (Public Self-Service).
 *
 * Akses: Publik (tanpa autentikasi).
 * Pemohon mengisi NIK (16 digit), jenis surat, dan keperluan.
 * Backend memvalidasi dan melakukan lookup ke data kependudukan via HMAC-SHA256 hash.
 *
 * @see API_SPECIFICATION.md §3.4.1
 * @see USER_STORIES.md US-SRT-01
 */
class StorePengajuanSuratRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Public self-service submission — tidak memerlukan login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'jenis_surat' => ['required', new Enum(JenisSurat::class)],
            'keperluan' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus terdiri dari 16 digit angka',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka',
            'jenis_surat.required' => 'Jenis surat wajib dipilih',
            'jenis_surat.*' => 'Jenis surat yang dipilih tidak valid',
            'keperluan.required' => 'Kolom keperluan wajib diisi',
            'keperluan.min' => 'Keperluan minimal 10 karakter',
            'keperluan.max' => 'Keperluan maksimal 1000 karakter',
        ];
    }
}
