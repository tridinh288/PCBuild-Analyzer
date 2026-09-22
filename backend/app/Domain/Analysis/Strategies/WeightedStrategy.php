<?php

namespace App\Domain\Analysis\Strategies;

use App\Domain\Analysis\Contracts\AnalysisStrategy;
use App\Domain\Analysis\Results\PerformanceResult;
use App\Domain\Analysis\SubScoreCalculator;
use App\Domain\Configuration\BuildConfiguration;

/**
 * Weighted sum of the sub-scores, then a profile-specific adjustment (D-031).
 * Subclasses provide profile() and, when relevant, adjustments().
 */
abstract class WeightedStrategy implements AnalysisStrategy
{
    /**
     * @param  array<string, float>  $weights  cpu, gpu, ram, storage — sum to 1
     * @param  array<string, int>  $adjustment  Profile thresholds and penalty from config
     */
    public function __construct(
        protected readonly SubScoreCalculator $subScores,
        protected readonly array $weights,
        protected readonly array $adjustment = [],
    ) {}

    final public function score(BuildConfiguration $config): PerformanceResult
    {
        $subScores = $this->subScores->all($config);
        $adjustments = $this->adjustments($config, $subScores);

        $penalty = array_sum(array_column($adjustments, 'penalty'));
        $score = max(0, min(100, $this->weightedScore($subScores) - $penalty));

        return new PerformanceResult($this->profile(), $score, $subScores, $this->weights, $adjustments);
    }

    /**
     * Penalties specific to this profile. None by default.
     *
     * @param  array{cpu: ?int, gpu: ?int, ram: ?int, storage: ?int}  $subScores
     * @return list<array{reason: string, penalty: int}>
     */
    protected function adjustments(BuildConfiguration $config, array $subScores): array
    {
        return [];
    }

    /**
     * Shared by the profiles that need a minimum amount of RAM.
     *
     * @return list<array{reason: string, penalty: int}>
     */
    protected function minimumRamAdjustment(BuildConfiguration $config, string $purpose): array
    {
        $ram = $config->ram();
        $minimum = $this->adjustment['min_ram_gb'];

        if ($ram === null || $ram->totalCapacityGb($config->quantityOf('ram')) >= $minimum) {
            return [];
        }

        return [[
            'reason' => "Dưới {$minimum}GB RAM, chưa thoải mái cho nhu cầu {$purpose}.",
            'penalty' => $this->adjustment['penalty'],
        ]];
    }

    /**
     * Integer arithmetic (weights as percentages) so x.5 always rounds up, without float drift.
     * Missing parts count as 0.
     *
     * @param  array{cpu: ?int, gpu: ?int, ram: ?int, storage: ?int}  $subScores
     */
    private function weightedScore(array $subScores): int
    {
        $total = 0;

        foreach ($this->weights as $part => $weight) {
            $total += (int) round($weight * 100) * ($subScores[$part] ?? 0);
        }

        return intdiv($total + 50, 100);
    }
}
