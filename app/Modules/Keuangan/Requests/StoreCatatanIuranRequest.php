<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCatatanIuranRequest extends FormRequest
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
            'no_kk' => ['required', 'string', 'digits:16'],
            'iuran_type_id' => ['required', 'integer', 'exists:iuran_types,id'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'tanggal_pembayaran' => ['nullable', 'date'],
            'payment_proof_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_kk.required' => 'Nomor Kartu Keluarga wajib diisi.',
            'no_kk.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit.',
            'iuran_type_id.required' => 'Jenis iuran wajib dipilih.',
            'iuran_type_id.exists' => 'Jenis iuran tidak ditemukan.',
            'nominal.required' => 'Nominal iuran wajib diisi.',
            'nominal.gt' => 'Nominal iuran harus lebih besar dari 0.',
            'periode_bulan.required' => 'Periode bulan wajib diisi.',
            'periode_bulan.between' => 'Periode bulan harus antara 1 sampai 12.',
            'periode_tahun.required' => 'Periode tahun wajib diisi.',
            'tanggal_pembayaran.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
