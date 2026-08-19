<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Requests;

use App\Enums\KategoriInformasi;
use App\Enums\StatusInformasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembaruan informasi publik.
 */
class UpdateInformasiPublikRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:150'],
            'konten' => ['required', 'string'],
            'kategori' => ['required', 'string', Rule::enum(KategoriInformasi::class)],
            'tanggal_publikasi' => ['nullable', 'date'],
            'tanggal_agenda' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->input('kategori') === KategoriInformasi::AGENDA->value),
            ],
            'status' => ['required', 'string', Rule::enum(StatusInformasi::class)],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul informasi wajib diisi',
            'judul.max' => 'Judul informasi maksimal 150 karakter',
            'konten.required' => 'Konten/isi informasi wajib diisi',
            'kategori.required' => 'Kategori informasi wajib dipilih',
            'kategori.Illuminate\Validation\Rules\Enum' => 'Kategori harus salah satu dari: PENGUMUMAN, BERITA, AGENDA',
            'kategori.enum' => 'Kategori harus salah satu dari: PENGUMUMAN, BERITA, AGENDA',
            'tanggal_agenda.required' => 'Tanggal agenda wajib diisi untuk kategori Agenda Kegiatan',
            'tanggal_agenda.required_if' => 'Tanggal agenda wajib diisi untuk kategori Agenda Kegiatan',
            'status.required' => 'Status publikasi wajib dipilih',
            'status.Illuminate\Validation\Rules\Enum' => 'Status harus salah satu dari: DRAFT, PUBLISHED, ARCHIVED',
            'status.enum' => 'Status harus salah satu dari: DRAFT, PUBLISHED, ARCHIVED',
        ];
    }
}
