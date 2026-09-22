<?php

namespace App\Enums;

enum CompatibilityStatus: string
{
    case Compatible = 'compatible';
    case Warning = 'warning';
    case Incompatible = 'incompatible';
    case Skipped = 'skipped';

    /**
     * Higher is worse; used to find the overall status of a report.
     */
    public function severity(): int
    {
        return match ($this) {
            self::Skipped => 0,
            self::Compatible => 1,
            self::Warning => 2,
            self::Incompatible => 3,
        };
    }

    public function isProblem(): bool
    {
        return $this === self::Warning || $this === self::Incompatible;
    }
}
