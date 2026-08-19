<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Resources;

use App\Enums\StatusKasKeluar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KasKeluarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof StatusKasKeluar ? $this->status->value : (string) $this->status;

        return [
            'id' => $this->id,
            'kategori' => $this->kategori,
            'keterangan' => $this->keterangan,
            'nominal' => (float) $this->nominal,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran?->format('Y-m-d'),
            'bukti_path' => $this->bukti_path,
            'status' => $statusValue,
            'rejection_notes' => $this->rejection_notes,
            'recorded_by' => $this->recordedBy?->full_name,
            'approved_by' => $this->approvedBy?->full_name,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
