<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationDecision: string
{
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
