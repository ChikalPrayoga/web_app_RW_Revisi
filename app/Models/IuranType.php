<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent untuk tabel master `iuran_types`.
 *
 * @see DATABASE_SCHEMA.md §3.9
 * @see API_SPECIFICATION.md §3.6.1
 */
class IuranType extends Model
{
    use HasFactory;

    protected $table = 'iuran_types';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'code',
        'default_amount',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke seluruh transaksi pencatatan iuran dari jenis ini.
     *
     * @return HasMany<CatatanIuran>
     */
    public function catatanIurans(): HasMany
    {
        return $this->hasMany(CatatanIuran::class, 'iuran_type_id');
    }
}
