<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PengajuanSurat;
use App\Support\Audit\AuditService;

/**
 * Observer untuk mencatat audit trail setiap perubahan status PengajuanSurat.
 *
 * Setiap transisi status (SUBMITTED → RT_REVIEW → RW_REVIEW → COMPLETED/REJECTED)
 * menghasilkan satu baris audit_logs yang dapat ditelusuri.
 * Tidak menyimpan NIK atau PII — hanya data non-sensitif surat.
 *
 * @see AGENTS.md §4 — Audit Trail wajib via Observer/Event Listener
 * @see DATABASE_SCHEMA.md §3.8 — tabel audit_logs
 */
class PengajuanSuratObserver
{
    /**
     * Handle the PengajuanSurat "created" event.
     * Mencatat pengajuan baru masuk dengan status SUBMITTED.
     */
    public function created(PengajuanSurat $pengajuan): void
    {
        AuditService::log(
            module: 'Persuratan',
            action: 'SUBMIT_PENGAJUAN_SURAT',
            entityType: 'pengajuan_surats',
            entityId: (string) $pengajuan->pengajuan_id,
            newValues: [
                'tracking_code' => $pengajuan->tracking_code,
                'jenis_surat' => $pengajuan->jenis_surat?->value ?? $pengajuan->getRawOriginal('jenis_surat'),
                'current_status' => $pengajuan->current_status?->value ?? $pengajuan->getRawOriginal('current_status'),
                'warga_id' => $pengajuan->warga_id,
                'tanggal_pengajuan' => $pengajuan->tanggal_pengajuan?->toDateTimeString(),
            ]
        );
    }

    /**
     * Handle the PengajuanSurat "updated" event.
     * Mencatat setiap perubahan status pada alur verifikasi berjenjang.
     */
    public function updated(PengajuanSurat $pengajuan): void
    {
        if (! $pengajuan->wasChanged('current_status')) {
            return;
        }

        // getOriginal() mengembalikan nilai raw database (string) sebelum cast ke Enum
        $oldStatusRaw = $pengajuan->getOriginal('current_status');
        $oldStatus = $oldStatusRaw instanceof \BackedEnum ? $oldStatusRaw->value : (string) $oldStatusRaw;

        // current_status setelah save sudah menjadi Enum object
        $currentStatus = $pengajuan->current_status;
        $newStatus = $currentStatus instanceof \BackedEnum ? $currentStatus->value : (string) $currentStatus;

        AuditService::log(
            module: 'Persuratan',
            action: 'STATUS_CHANGE_'.$newStatus,
            entityType: 'pengajuan_surats',
            entityId: (string) $pengajuan->pengajuan_id,
            oldValues: [
                'current_status' => $oldStatus,
            ],
            newValues: [
                'tracking_code' => $pengajuan->tracking_code,
                'current_status' => $newStatus,
                'catatan_penolakan' => $pengajuan->catatan_penolakan,
                'nomor_surat' => $pengajuan->nomor_surat,
                'tanggal_selesai' => $pengajuan->tanggal_selesai?->toDateTimeString(),
            ]
        );
    }
}
