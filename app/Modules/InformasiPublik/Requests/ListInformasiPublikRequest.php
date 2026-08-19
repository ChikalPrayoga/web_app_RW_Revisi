<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Requests;

use App\Enums\KategoriInformasi;
use App\Enums\StatusInformasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk filter dan pencarian daftar informasi publik.
 *
 * @see docs/API_SPECIFICATION.md §3.7.1
 */
class ListInformasiPublikRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kategori' => ['nullable', 'string', Rule::enum(KategoriInformasi::class)],
            'status' => ['nullable', 'string', Rule::enum(StatusInformasi::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
