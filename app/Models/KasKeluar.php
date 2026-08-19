<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusKasKeluar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Eloquent untuk tabel transaksi `kas_keluars`.
 *
 * Menyimpan data transaksi pengeluaran kas operasional RW.
 * Menegakkan dual-control (pencatat: Bendahara RW, penyetuju: Ketua RW).
 *
 * @see DATABASE_SCHEMA.md §3.11
 * @see API_SPECIFICATION.md §3.6.5-§3.6.7
 */
class KasKeluar extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'kas_keluars';

    protected $attributes = [
        'status' => 'PENDING',
    ];

    protected $fillable = [
        'kategori',
        'keterangan',
        'nominal',
        'tanggal_pengeluaran',
        'bukti_path',
        'recorded_by_user_id',
        'status',
        'approved_by_user_id',
        'approved_at',
        'rejection_notes',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal_pengeluaran' => 'date',
        'approved_at' => 'datetime',
        'status' => StatusKasKeluar::class,
    ];

    /**
     * Relasi ke User pencatat pengeluaran (Bendahara RW).
     *
     * @return BelongsTo<User, KasKeluar>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Relasi ke User penyetuju pengeluaran (Ketua RW).
     *
     * @return BelongsTo<User, KasKeluar>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Apakah status transaksi ini sudah final.
     */
    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
