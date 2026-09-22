<?php

namespace App\Domain\Analysis\Strategies;

use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;

/**
 * CPU > RAM > storage > GPU (compiling, containers, several IDEs), penalty below a RAM threshold.
 */
final class ProgrammingStrategy extends WeightedStrategy
{
    public function profile(): BuildPurpose
    {
        return BuildPurpose::Programming;
    }

    protected function adjustments(BuildConfiguration $config, array $subScores): array
    {
        return $this->minimumRamAdjustment($config, 'lập trình');
    }
}
