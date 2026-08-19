<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Resources;

use App\Models\InformasiPublik;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource untuk transformasi JSON respon Informasi Publik.
 *
 * @mixin InformasiPublik
 *
 * @see docs/API_SPECIFICATION.md §3.7.1
 */
class InformasiPublikResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'konten' => $this->konten,
            'kategori' => $this->kategori->value,
            'kategori_label' => $this->kategori->label(),
            'tanggal_publikasi' => $this->tanggal_publikasi?->toDateString(),
            'tanggal_agenda' => $this->tanggal_agenda?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'published_by' => $this->publishedBy?->full_name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
