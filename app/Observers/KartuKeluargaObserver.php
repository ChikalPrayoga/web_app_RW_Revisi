<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\KartuKeluarga;
use App\Support\Audit\AuditService;

class KartuKeluargaObserver
{
    /**
     * Handle the KartuKeluarga "created" event.
     */
    public function created(KartuKeluarga $kk): void
    {
        AuditService::log(
            module: 'Kependudukan',
            action: 'CREATE_KARTU_KELUARGA',
            entityType: 'kartu_keluargas',
            entityId: (string) $kk->id,
            newValues: [
                'id' => $kk->id,
                'no_kk_masked' => $kk->no_kk_masked,
                'rt_code' => $kk->rt_code,
                'status_kepemilikan_rumah' => $kk->status_kepemilikan_rumah,
            ]
        );
    }
}
