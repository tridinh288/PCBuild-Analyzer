<?php

namespace App\Domain\Analysis\Strategies;

use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;

/**
 * GPU > CPU > RAM > storage, and a penalty when CPU and GPU tiers are far apart
 * (one would hold the other back in games).
 */
final class GamingStrategy extends WeightedStrategy
{
    public function profile(): BuildPurpose
    {
        return BuildPurpose::Gaming;
    }

    protected function adjustments(BuildConfiguration $config, array $subScores): array
    {
        $cpu = $config->cpu();
        $gpu = $config->gpu();

        if ($cpu === null || $gpu === null) {
            return [];
        }

        $gap = abs($cpu->performanceTier() - $gpu->performanceTier());

        if ($gap <= $this->adjustment['max_cpu_gpu_tier_gap']) {
            return [];
        }

        $weaker = $cpu->performanceTier() < $gpu->performanceTier() ? 'CPU' : 'GPU';

        return [[
            'reason' => "CPU và GPU chênh lệch {$gap} bậc hiệu năng; {$weaker} có thể gây nghẽn cổ chai khi chơi game.",
            'penalty' => $this->adjustment['penalty'],
        ]];
    }
}
