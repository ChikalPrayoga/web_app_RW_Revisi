<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Security\DataEncryptionService;
use App\Support\Security\PiiMaskingHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KartuKeluarga extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kartu_keluargas';

    protected $fillable = [
        'no_kk',
        'no_kk_hash',
        'rt_code',
        'alamat_lengkap',
        'blok',
        'nomor_rumah',
        'status_kepemilikan_rumah',
    ];

    /**
     * Mutator & Accessor untuk no_kk (Encrypted PII Payload & Deterministic Hash).
     */
    protected function noKk(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => [
                'no_kk' => DataEncryptionService::encrypt($value),
                'no_kk_hash' => DataEncryptionService::deterministicHash($value),
            ]
        );
    }

    /**
     * Mutator & Accessor untuk alamat_lengkap (Encrypted PII Payload).
     */
    protected function alamatLengkap(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => DataEncryptionService::encrypt($value),
        );
    }

    /**
     * Nilai No KK yang sudah disamarkan (Masked PII).
     */
    public function getNoKkMaskedAttribute(): ?string
    {
        return PiiMaskingHelper::maskNoKk($this->no_kk);
    }

    /**
     * Relasi ke seluruh anggota warga dalam Kartu Keluarga ini.
     */
    public function wargas(): HasMany
    {
        return $this->hasMany(Warga::class, 'kartu_keluarga_id');
    }

    /**
     * Relasi ke seluruh catatan pembayaran iuran dari Kartu Keluarga ini.
     *
     * @return HasMany<CatatanIuran>
     */
    public function catatanIurans(): HasMany
    {
        return $this->hasMany(CatatanIuran::class, 'kartu_keluarga_id');
    }
}
