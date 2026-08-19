<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk tracking publik pengajuan surat.
 *
 * Hanya mengekspos data non-sensitif yang diperlukan pemohon:
 * tracking_code, jenis_surat, current_status, nomor_surat, dan riwayat_status.
 * NIK, no_kk, alamat, dan PII lain TIDAK diekspos.
 *
 * riwayat_status dibangun dari audit_logs (bukan dari current_status saja),
 * sehingga history perubahan dapat ditelusuri secara akurat.
 *
 * @see API_SPECIFICATION.md §3.4.2
 * @see AGENTS.md §3.2 — dilarang ekspos PII dalam response publik
 */
class PengajuanSuratTrackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $riwayat = AuditLog::where('entity_type', 'pengajuan_surats')
            ->where('entity_id', (string) $this->pengajuan_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($log) => [
                'action' => $log->action,
                'new_status' => data_get($log->new_values, 'current_status'),
                'catatan' => data_get($log->new_values, 'catatan_penolakan'),
                'waktu' => $log->created_at?->toDateTimeString(),
            ])
            ->values();

        return [
            'tracking_code' => $this->tracking_code,
            'jenis_surat' => $this->jenis_surat?->value,
            'current_status' => $this->current_status?->value,
            'nomor_surat' => $this->nomor_surat,
            'catatan_penolakan' => $this->when(
                $this->current_status?->value === 'REJECTED',
                $this->catatan_penolakan
            ),
            'tanggal_pengajuan' => $this->tanggal_pengajuan?->toDateTimeString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateTimeString(),
            'riwayat_status' => $riwayat,
        ];
    }
}
