<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Services;

use App\Enums\StatusLaporan;
use App\Models\LaporanAspirasi;
use App\Models\User;
use App\Models\Warga;
use App\Support\Security\DataEncryptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LaporanAspirasiService
{
    /**
     * Menyimpan laporan/aspirasi baru dari warga.
     * NIK bersifat opsional — jika diisi, digunakan untuk lookup warga_id.
     *
     * @see US-LAP-01, FR-04
     *
     * @param  array<string, mixed>  $data
     */
    public function submitLaporan(array $data): LaporanAspirasi
    {
        // Resolve warga_id dari NIK jika NIK disertakan
        $wargaId = null;
        if (! empty($data['nik'])) {
            $nikHash = DataEncryptionService::deterministicHash($data['nik']);
            $warga = Warga::where('nik_hash', $nikHash)->first();
            if ($warga) {
                $wargaId = $warga->id;
            }
        }

        return DB::transaction(function () use ($data, $wargaId): LaporanAspirasi {
            return LaporanAspirasi::create([
                'ticket_number' => LaporanAspirasi::generateTicketNumber(),
                'warga_id' => $wargaId,
                'judul_laporan' => strip_tags($data['judul_laporan']),
                'teks_keluhan' => strip_tags($data['teks_keluhan']),
                'lokasi_kejadian' => isset($data['lokasi_kejadian']) ? strip_tags($data['lokasi_kejadian']) : null,
                'current_status' => StatusLaporan::SUBMITTED,
                'submitted_at' => now(),
            ]);
        });
    }

    /**
     * Melacak status laporan berdasarkan nomor tiket (akses publik).
     *
     * @see US-LAP-01 (tracking)
     */
    public function trackByTicket(string $ticketNumber): LaporanAspirasi
    {
        $laporan = LaporanAspirasi::where('ticket_number', $ticketNumber)->first();

        if (! $laporan) {
            throw new NotFoundHttpException('Nomor tiket tidak ditemukan.');
        }

        return $laporan;
    }

    /**
     * Mengambil daftar laporan untuk pengurus dengan filter dan paginasi.
     *
     * @see US-LAP-03, US-LAP-04
     *
     * @param  array<string, mixed>  $filters
     */
    public function listLaporan(array $filters = []): LengthAwarePaginator
    {
        $query = LaporanAspirasi::query();

        if (! empty($filters['current_status'])) {
            $query->status($filters['current_status']);
        }

        $sortBy = $filters['sort_by'] ?? 'submitted_at';
        $query->orderBy($sortBy, 'desc');

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query->paginate($perPage);
    }

    /**
     * Memperbarui status laporan oleh pengurus RW.
     * Menegakkan state machine dan validasi catatan saat RESOLVED.
     *
     * @see US-LAP-04
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(LaporanAspirasi $laporan, User $user, array $data): LaporanAspirasi
    {
        $nextStatus = StatusLaporan::from($data['current_status']);

        // Validasi transisi state machine
        if (! $laporan->canTransitionTo($nextStatus)) {
            throw new UnprocessableEntityHttpException(
                "Transisi status dari {$laporan->current_status->value} ke {$nextStatus->value} tidak valid."
            );
        }

        // catatan_tindak_lanjut wajib saat transisi ke RESOLVED
        if ($nextStatus === StatusLaporan::RESOLVED && empty($data['catatan_tindak_lanjut'])) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid',
                    'errors' => ['catatan_tindak_lanjut' => ['Catatan tindak lanjut wajib diisi saat status RESOLVED.']],
                ], 422)
            );
        }

        return DB::transaction(function () use ($laporan, $nextStatus, $data): LaporanAspirasi {
            $updatePayload = [
                'current_status' => $nextStatus,
            ];

            if (! empty($data['catatan_tindak_lanjut'])) {
                $updatePayload['catatan_tindak_lanjut'] = strip_tags($data['catatan_tindak_lanjut']);
            }

            if ($nextStatus === StatusLaporan::RESOLVED) {
                $updatePayload['resolved_at'] = now();
            }

            $laporan->update($updatePayload);

            return $laporan->fresh();
        });
    }

    /**
     * Soft delete laporan (hanya Sekretaris RW, Ketua RW, Super Admin).
     */
    public function deleteLaporan(LaporanAspirasi $laporan, User $user): void
    {
        DB::transaction(function () use ($laporan): void {
            $laporan->delete();
        });
    }
}
