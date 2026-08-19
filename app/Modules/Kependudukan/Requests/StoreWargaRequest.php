<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Requests;

use App\Enums\RoleName;
use App\Enums\StatusWarga;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWargaRequest extends FormRequest
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
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'no_kk' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'status_hubungan_keluarga' => ['required', 'string', 'max:50'],
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
            'nik.required' => 'NIK wajib diisi',
            'nik.size' => 'NIK harus terdiri dari 16 digit angka',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka',
            'no_kk.required' => 'Nomor KK wajib diisi',
            'no_kk.size' => 'Nomor KK harus terdiri dari 16 digit angka',
            'no_kk.regex' => 'Nomor KK harus terdiri dari 16 digit angka',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi',
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus YYYY-MM-DD',
            'status_hubungan_keluarga.required' => 'Status hubungan keluarga wajib diisi',
        ];
    }
}
