<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Resources;

use App\Enums\StatusCatatanIuran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatatanIuranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusValue = $this->status instanceof StatusCatatanIuran ? $this->status->value : (string) $this->status;

        return [
            'iuran_id' => $this->iuran_id,
            'no_kk_masked' => $this->kartuKeluarga?->no_kk_masked,
            'rt_code' => $this->kartuKeluarga?->rt_code,
            'iuran_type' => [
                'id' => $this->iuranType?->id,
                'name' => $this->iuranType?->name,
                'code' => $this->iuranType?->code,
            ],
            'nominal' => (float) $this->nominal,
            'periode_bulan' => (int) $this->periode_bulan,
            'periode_tahun' => (int) $this->periode_tahun,
            'tanggal_pembayaran' => $this->tanggal_pembayaran?->format('Y-m-d'),
            'status' => $statusValue,
            'payment_proof_path' => $this->payment_proof_path,
            'rejection_notes' => $this->rejection_notes,
            'recorded_by' => $this->recordedBy?->full_name,
            'approved_by' => $this->approvedBy?->full_name,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
