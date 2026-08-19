<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WargaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'nik_hash' => $this->nik_hash,
            'nik_masked' => $this->nik_masked,
            'nama_lengkap' => $this->nama_lengkap,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d') ?? $this->tanggal_lahir,
            'pekerjaan' => $this->pekerjaan,
            'status_hubungan_keluarga' => $this->status_hubungan_keluarga,
            'status_warga' => $this->status_warga,
            'verification_status' => $this->verification_status,
            'no_kk_masked' => $this->no_kk_masked,
            'rt_code' => $this->kartuKeluarga?->rt_code,
        ];
    }
}
