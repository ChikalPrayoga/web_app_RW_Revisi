<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Warga;
use App\Support\Audit\AuditService;

class WargaObserver
{
    /**
     * Handle the Warga "created" event.
     */
    public function created(Warga $warga): void
    {
        AuditService::log(
            module: 'Kependudukan',
            action: 'CREATE_WARGA',
            entityType: 'wargas',
            entityId: (string) $warga->id,
            newValues: [
                'id' => $warga->id,
                'kartu_keluarga_id' => $warga->kartu_keluarga_id,
                'nik_masked' => $warga->nik_masked,
                'nama_lengkap' => $warga->nama_lengkap,
                'verification_status' => $warga->verification_status,
                'status_warga' => $warga->status_warga,
            ]
        );
    }

    /**
     * Handle the Warga "updated" event.
     */
    public function updated(Warga $warga): void
    {
        // Jika perubahan terjadi karena aksi verifikasi oleh Sekretaris RW
        if ($warga->wasChanged('verification_status') && in_array($warga->verification_status, ['TERVERIFIKASI', 'DITOLAK'], true)) {
            AuditService::log(
                module: 'Kependudukan',
                action: 'VERIFY_WARGA',
                entityType: 'wargas',
                entityId: (string) $warga->id,
                newValues: [
                    'verification_status' => $warga->verification_status,
                    'verified_by_user_id' => $warga->verified_by_user_id,
                    'verification_notes' => $warga->verification_notes,
                ]
            );

            return;
        }

        // Untuk update data biasa
        $changes = $warga->getChanges();
        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $warga->getOriginal($key);
        }

        AuditService::log(
            module: 'Kependudukan',
            action: 'UPDATE_WARGA',
            entityType: 'wargas',
            entityId: (string) $warga->id,
            oldValues: $original,
            newValues: $changes
        );
    }
}
