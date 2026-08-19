<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Security\DataEncryptionService;
use App\Support\Security\PiiMaskingHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warga extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wargas';

    protected $fillable = [
        'kartu_keluarga_id',
        'nik',
        'nik_hash',
        'no_kk',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'pekerjaan',
        'nomor_hp',
        'status_hubungan_keluarga',
        'status_sosio_ekonomi',
        'status_warga',
        'verification_status',
        'verified_by_user_id',
        'verification_notes',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date:Y-m-d',
    ];

    /**
     * Mutator & Accessor untuk nik (Encrypted PII Payload & Deterministic Hash).
     */
    protected function nik(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => [
                'nik' => DataEncryptionService::encrypt($value),
                'nik_hash' => DataEncryptionService::deterministicHash($value),
            ]
        );
    }

    /**
     * Mutator & Accessor untuk no_kk terenkripsi di Warga.
     */
    protected function noKk(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => DataEncryptionService::encrypt($value),
        );
    }

    /**
     * Mutator & Accessor untuk tempat_lahir (Encrypted PII Payload).
     */
    protected function tempatLahir(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => DataEncryptionService::encrypt($value),
        );
    }

    /**
     * Mutator & Accessor untuk nomor_hp (Encrypted PII Payload).
     */
    protected function nomorHp(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => DataEncryptionService::decrypt($value),
            set: fn (?string $value) => DataEncryptionService::encrypt($value),
        );
    }

    /**
     * Nilai NIK yang sudah disamarkan (Masked PII).
     */
    public function getNikMaskedAttribute(): ?string
    {
        return PiiMaskingHelper::maskNik($this->nik);
    }

    /**
     * Nilai No KK yang sudah disamarkan (Masked PII).
     */
    public function getNoKkMaskedAttribute(): ?string
    {
        return PiiMaskingHelper::maskNoKk($this->no_kk);
    }

    /**
     * Relasi ke entitas induk Kartu Keluarga.
     */
    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_id');
    }

    /**
     * Relasi ke Sekretaris RW yang memverifikasi data.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Relasi ke riwayat pengajuan surat warga ini.
     *
     * Satu warga dapat mengajukan banyak surat sepanjang waktu (DATABASE_SCHEMA.md §4.2c).
     */
    public function pengajuanSurats(): HasMany
    {
        return $this->hasMany(PengajuanSurat::class, 'warga_id');
    }

    /**
     * Relasi ke riwayat laporan/aspirasi yang disampaikan warga ini.
     *
     * Satu warga dapat menyampaikan banyak laporan sepanjang waktu (DATABASE_SCHEMA.md §3.8).
     */
    public function laporanAspirasis(): HasMany
    {
        return $this->hasMany(LaporanAspirasi::class, 'warga_id');
    }
}
