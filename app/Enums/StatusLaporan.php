<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusLaporan: string
{
    case SUBMITTED = 'SUBMITTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Disampaikan',
            self::IN_PROGRESS => 'Sedang Ditangani',
            self::RESOLVED => 'Selesai Ditangani',
            self::CLOSED => 'Ditutup',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SUBMITTED => 'bg-yellow-100 text-yellow-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::RESOLVED => 'bg-green-100 text-green-800',
            self::CLOSED => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Returns valid next statuses that can transition from current status.
     *
     * @return StatusLaporan[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SUBMITTED => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::RESOLVED],
            self::RESOLVED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
