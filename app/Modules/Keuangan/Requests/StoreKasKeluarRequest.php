<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKasKeluarRequest extends FormRequest
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
            'kategori' => ['required', 'string', 'min:3', 'max:100'],
            'keterangan' => ['required', 'string', 'min:10', 'max:1000'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'tanggal_pengeluaran' => ['required', 'date'],
            'bukti_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kategori.required' => 'Kategori pengeluaran wajib diisi.',
            'kategori.min' => 'Kategori pengeluaran minimal 3 karakter.',
            'keterangan.required' => 'Keterangan pengeluaran wajib diisi minimal 10 karakter',
            'keterangan.min' => 'Keterangan pengeluaran wajib diisi minimal 10 karakter',
            'nominal.required' => 'Nominal pengeluaran wajib diisi.',
            'nominal.gt' => 'Nominal pengeluaran harus lebih besar dari 0',
            'tanggal_pengeluaran.required' => 'Tanggal pengeluaran wajib diisi.',
            'tanggal_pengeluaran.date' => 'Format tanggal pengeluaran tidak valid.',
        ];
    }
}
