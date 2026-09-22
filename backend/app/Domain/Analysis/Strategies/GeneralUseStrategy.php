<?php

namespace App\Domain\Analysis\Strategies;

use App\Enums\BuildPurpose;

/**
 * Balanced weights, no adjustment. Default profile for custom builds (D-016).
 */
final class GeneralUseStrategy extends WeightedStrategy
{
    public function profile(): BuildPurpose
    {
        return BuildPurpose::GeneralUse;
    }
}
