<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LaporanAspirasi;
use App\Support\Audit\AuditService;

/**
 * Observer untuk mencatat audit trail setiap lifecycle LaporanAspirasi.
 *
 * Setiap pembuatan laporan, transisi status (SUBMITTED → IN_PROGRESS → RESOLVED → CLOSED),
 * dan penghapusan (soft delete) menghasilkan satu baris audit_logs.
 * Tidak menyimpan NIK plaintext atau data sensitif pelapor.
 *
 * @see AGENTS.md §4 — Audit Trail wajib via Observer/Event Listener
 * @see DATABASE_SCHEMA.md §3.8 — tabel audit_logs
 */
class LaporanAspirasiObserver
{
    /**
     * Handle the LaporanAspirasi "created" event.
     */
    public function created(LaporanAspirasi $laporan): void
    {
        AuditService::log(
            module: 'Laporan Aspirasi',
            action: 'CREATE_LAPORAN_ASPIRASI',
            entityType: 'laporan_aspirasis',
            entityId: (string) $laporan->aspirasi_id,
            newValues: [
                'ticket_number' => $laporan->ticket_number,
                'judul_laporan' => substr($laporan->judul_laporan, 0, 50),
                'current_status' => $laporan->current_status?->value ?? (string) $laporan->getRawOriginal('current_status'),
                'warga_id' => $laporan->warga_id,
                'submitted_at' => $laporan->submitted_at?->toDateTimeString(),
            ]
        );
    }

    /**
     * Handle the LaporanAspirasi "updated" event.
     */
    public function updated(LaporanAspirasi $laporan): void
    {
        if ($laporan->wasChanged('current_status')) {
            $oldStatusRaw = $laporan->getOriginal('current_status');
            $oldStatus = $oldStatusRaw instanceof \BackedEnum ? $oldStatusRaw->value : (string) $oldStatusRaw;

            $currentStatus = $laporan->current_status;
            $newStatus = $currentStatus instanceof \BackedEnum ? $currentStatus->value : (string) $currentStatus;

            AuditService::log(
                module: 'Laporan Aspirasi',
                action: 'STATUS_CHANGE_LAPORAN',
                entityType: 'laporan_aspirasis',
                entityId: (string) $laporan->aspirasi_id,
                oldValues: [
                    'current_status' => $oldStatus,
                ],
                newValues: [
                    'ticket_number' => $laporan->ticket_number,
                    'current_status' => $newStatus,
                    'catatan_tindak_lanjut_preview' => $laporan->catatan_tindak_lanjut
                        ? substr($laporan->catatan_tindak_lanjut, 0, 50)
                        : null,
                    'resolved_at' => $laporan->resolved_at?->toDateTimeString(),
                ]
            );
        }
    }

    /**
     * Handle the LaporanAspirasi "deleted" event.
     */
    public function deleted(LaporanAspirasi $laporan): void
    {
        AuditService::log(
            module: 'Laporan Aspirasi',
            action: 'DELETE_LAPORAN_ASPIRASI',
            entityType: 'laporan_aspirasis',
            entityId: (string) $laporan->aspirasi_id,
            newValues: [
                'ticket_number' => $laporan->ticket_number,
            ]
        );
    }
}
