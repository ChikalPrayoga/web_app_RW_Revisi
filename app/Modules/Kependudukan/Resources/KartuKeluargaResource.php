<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Resources;

use App\Support\Security\PiiMaskingHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KartuKeluargaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'no_kk_masked' => $this->no_kk_masked,
            'rt_code' => $this->rt_code,
            'alamat_lengkap_masked' => PiiMaskingHelper::maskAddress($this->alamat_lengkap),
            'blok' => $this->blok,
            'nomor_rumah' => $this->nomor_rumah,
            'status_kepemilikan_rumah' => $this->status_kepemilikan_rumah,
            'jumlah_anggota' => $this->wargas_count ?? $this->wargas()->count(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
