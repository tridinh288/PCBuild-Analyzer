<?php

namespace App\Domain\Analysis\Strategies;

use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;

/**
 * CPU > RAM > GPU > storage (rendering, video editing), higher RAM threshold than programming.
 */
final class WorkstationStrategy extends WeightedStrategy
{
    public function profile(): BuildPurpose
    {
        return BuildPurpose::Workstation;
    }

    protected function adjustments(BuildConfiguration $config, array $subScores): array
    {
        return $this->minimumRamAdjustment($config, 'đồ họa / workstation');
    }
}
