<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk daftar pengajuan surat (pengurus).
 *
 * Tidak mengekspos NIK, no_kk, atau PII lain dari warga pemohon.
 * Hanya menyertakan nama pemohon dan rt_code untuk keperluan review.
 *
 * @see API_SPECIFICATION.md §3.4.3
 */
class PengajuanSuratResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->pengajuan_id,
            'tracking_code' => $this->tracking_code,
            'jenis_surat' => $this->jenis_surat?->value,
            'keperluan' => $this->keperluan,
            'current_status' => $this->current_status?->value,
            'nomor_surat' => $this->nomor_surat,
            'catatan_penolakan' => $this->catatan_penolakan,
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->toDateTimeString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateTimeString(),
            'pemohon' => $this->whenLoaded('warga', function () {
                return [
                    'nama_lengkap' => $this->warga->nama_lengkap,
                    'rt_code' => optional($this->warga->kartuKeluarga)->rt_code,
                ];
            }),
        ];
    }
}
