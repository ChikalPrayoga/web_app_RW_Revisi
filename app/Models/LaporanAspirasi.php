<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusLaporan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanAspirasi extends Model
{
    use SoftDeletes;

    protected $table = 'laporan_aspirasis';

    protected $primaryKey = 'aspirasi_id';

    protected $fillable = [
        'ticket_number',
        'warga_id',
        'judul_laporan',
        'teks_keluhan',
        'lokasi_kejadian',
        'current_status',
        'catatan_tindak_lanjut',
        'submitted_at',
        'resolved_at',
    ];

    protected $casts = [
        'current_status' => StatusLaporan::class,
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function warga(): BelongsTo
    {
        return $this->belongsTo(Warga::class, 'warga_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Filter by status.
     */
    public function scopeStatus(Builder $query, StatusLaporan|string $status): Builder
    {
        $value = $status instanceof StatusLaporan ? $status->value : $status;

        return $query->where('current_status', $value);
    }

    /**
     * Scope laporan yang belum ditutup (untuk pengurus active list).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('current_status', '!=', StatusLaporan::CLOSED->value);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Generate unique ticket number in format LPR-YYYYMMDD-XXXXX.
     */
    public static function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = str_pad((string) (static::whereDate('submitted_at', today())->count() + 1), 5, '0', STR_PAD_LEFT);

        return "LPR-{$date}-{$sequence}";
    }

    public function isClosed(): bool
    {
        return $this->current_status === StatusLaporan::CLOSED;
    }

    public function canTransitionTo(StatusLaporan $next): bool
    {
        return $this->current_status->canTransitionTo($next);
    }
}
