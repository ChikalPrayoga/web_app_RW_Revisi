<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KategoriInformasi;
use App\Enums\StatusInformasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model untuk entitas Informasi Publik (Pengumuman, Berita, Agenda RW 047).
 *
 * @property int $id
 * @property string $judul
 * @property string $konten
 * @property KategoriInformasi $kategori
 * @property \Illuminate\Support\Carbon $tanggal_publikasi
 * @property \Illuminate\Support\Carbon|null $tanggal_agenda
 * @property int $published_by_user_id
 * @property StatusInformasi $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $publishedBy
 *
 * @see docs/DATABASE_SCHEMA.md §3.12
 */
class InformasiPublik extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'informasi_publiks';

    protected $fillable = [
        'judul',
        'konten',
        'kategori',
        'tanggal_publikasi',
        'tanggal_agenda',
        'published_by_user_id',
        'status',
    ];

    protected $casts = [
        'kategori' => KategoriInformasi::class,
        'status' => StatusInformasi::class,
        'tanggal_publikasi' => 'date',
        'tanggal_agenda' => 'date',
    ];

    /**
     * Relasi ke pengurus yang mempublikasikan konten.
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    /**
     * Scope query untuk konten yang berstatus PUBLISHED.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', StatusInformasi::PUBLISHED->value);
    }

    /**
     * Scope query berdasarkan kategori tertentu.
     */
    public function scopeKategori(Builder $query, KategoriInformasi|string $kategori): Builder
    {
        $val = $kategori instanceof KategoriInformasi ? $kategori->value : (string) $kategori;

        return $query->where('kategori', $val);
    }

    /**
     * Scope query untuk pencarian kata kunci pada judul atau konten.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('judul', 'like', "%{$term}%")
                ->orWhere('konten', 'like', "%{$term}%");
        });
    }

    /**
     * Apakah konten berstatus publikasi (PUBLISHED).
     */
    public function isPublished(): bool
    {
        return $this->status === StatusInformasi::PUBLISHED;
    }

    /**
     * Apakah konten berstatus DRAFT.
     */
    public function isDraft(): bool
    {
        return $this->status === StatusInformasi::DRAFT;
    }

    /**
     * Apakah konten berstatus ARCHIVED.
     */
    public function isArchived(): bool
    {
        return $this->status === StatusInformasi::ARCHIVED;
    }

    /**
     * Apakah konten berjenis AGENDA.
     */
    public function isAgenda(): bool
    {
        return $this->kategori === KategoriInformasi::AGENDA;
    }
}
