<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisSurat;
use App\Enums\StatusPengajuanSurat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Eloquent untuk tabel `pengajuan_surats`.
 *
 * Merepresentasikan permohonan surat warga beserta status verifikasi berjenjang (RT → RW).
 * Tidak menyimpan PII langsung — pemohon diidentifikasi via relasi ke `wargas` (FK warga_id).
 * `tracking_code` digunakan sebagai identifier publik untuk pelacakan tanpa exposing PII.
 *
 * @see DATABASE_SCHEMA.md §3.7
 * @see API_SPECIFICATION.md §3.4
 */
class PengajuanSurat extends Model
{
    use SoftDeletes;

    protected $table = 'pengajuan_surats';

    protected $primaryKey = 'pengajuan_id';

    protected $fillable = [
        'tracking_code',
        'warga_id',
        'nomor_surat',
        'jenis_surat',
        'keperluan',
        'current_status',
        'catatan_penolakan',
        'tanggal_pengajuan',
        'tanggal_selesai',
    ];

    protected $casts = [
        'jenis_surat' => JenisSurat::class,
        'current_status' => StatusPengajuanSurat::class,
        'tanggal_pengajuan' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    /**
     * Relasi ke warga pemohon.
     * Digunakan untuk mendapatkan nama pemohon dan rt_code untuk area scoping.
     */
    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }

    /**
     * Apakah status pengajuan saat ini sudah final (tidak dapat diubah lagi).
     * Sesuai AGENTS.md §3.1 — larangan mengubah status yang sudah final.
     */
    public function isFinal(): bool
    {
        return $this->current_status->isFinal();
    }
}
