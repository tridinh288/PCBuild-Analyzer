<?php

namespace App\Domain\Analysis\Contracts;

use App\Domain\Analysis\Results\PerformanceResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;

/**
 * Strategy Pattern: one way of scoring a configuration per purpose (spec section 16).
 * The user can switch profile in the UI to see the same build scored differently (D-016).
 */
interface AnalysisStrategy
{
    public function profile(): BuildPurpose;

    public function score(BuildConfiguration $config): PerformanceResult;
}
