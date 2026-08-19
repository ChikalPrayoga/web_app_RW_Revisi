<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanAspirasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Cek apakah request berasal dari pengurus (authenticated dengan peran tertentu)
        $isPengurus = $request->user()?->hasAnyRole(['KETUA_RT', 'SEKRETARIS_RW', 'KETUA_RW', 'SUPER_ADMIN']);

        return [
            'aspirasi_id' => $this->aspirasi_id,
            'ticket_number' => $this->ticket_number,
            'judul_laporan' => $this->judul_laporan,
            // teks_keluhan penuh hanya untuk pengurus — publik hanya mendapat preview
            'teks_keluhan' => $isPengurus
                ? $this->teks_keluhan
                : (strlen($this->teks_keluhan) > 100 ? substr($this->teks_keluhan, 0, 100).'...' : $this->teks_keluhan),
            'lokasi_kejadian' => $this->lokasi_kejadian,
            'current_status' => $this->current_status->value,
            'current_status_label' => $this->current_status->label(),
            'catatan_tindak_lanjut' => $this->catatan_tindak_lanjut,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
