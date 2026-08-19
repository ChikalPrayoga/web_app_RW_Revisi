<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusCatatanIuran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Eloquent untuk tabel transaksi `catatan_iurans`.
 *
 * Menyimpan data transaksi pembayaran iuran warga berdasarkan Kartu Keluarga.
 * Menggunakan surrogate foreign key `kartu_keluarga_id` ke tabel `kartu_keluargas`.
 *
 * @see DATABASE_SCHEMA.md §3.10
 * @see API_SPECIFICATION.md §3.6.2-§3.6.4
 */
class CatatanIuran extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'catatan_iurans';

    protected $primaryKey = 'iuran_id';

    protected $attributes = [
        'status' => 'PENDING',
    ];

    protected $fillable = [
        'kartu_keluarga_id',
        'iuran_type_id',
        'nominal',
        'periode_bulan',
        'periode_tahun',
        'tanggal_pembayaran',
        'recorded_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'status',
        'payment_proof_path',
        'rejection_notes',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'periode_bulan' => 'integer',
        'periode_tahun' => 'integer',
        'tanggal_pembayaran' => 'date',
        'approved_at' => 'datetime',
        'status' => StatusCatatanIuran::class,
    ];

    /**
     * Relasi ke Kartu Keluarga pembayar iuran.
     *
     * @return BelongsTo<KartuKeluarga, CatatanIuran>
     */
    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_id');
    }

    /**
     * Relasi ke master Jenis Iuran.
     *
     * @return BelongsTo<IuranType, CatatanIuran>
     */
    public function iuranType(): BelongsTo
    {
        return $this->belongsTo(IuranType::class, 'iuran_type_id');
    }

    /**
     * Relasi ke User pencatat (Ketua RT).
     *
     * @return BelongsTo<User, CatatanIuran>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * Relasi ke User penyetuju (Bendahara RW).
     *
     * @return BelongsTo<User, CatatanIuran>
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
