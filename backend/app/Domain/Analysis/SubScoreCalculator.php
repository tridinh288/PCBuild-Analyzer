<?php

namespace App\Domain\Analysis;

use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\ConfigurationItem;

/**
 * Component sub-scores (0–100) shared by every strategy. Project-defined rules, not benchmarks
 * (D-015); all tables come from config/hardware.php `scoring`.
 *
 * null means the part is missing (or, for the GPU, the build has no display output at all).
 */
final class SubScoreCalculator
{
    /**
     * @param  array{integrated_graphics_score: int, ram_capacity_scores: array<int, int>,
     *     ram_type_bonus: array<string, int>, storage_interface_scores: array<string, int>,
     *     storage_capacity_bonus: array<int, int>}  $scoring
     */
    public function __construct(private readonly array $scoring) {}

    /**
     * @return array{cpu: ?int, gpu: ?int, ram: ?int, storage: ?int}
     */
    public function all(BuildConfiguration $config): array
    {
        return [
            'cpu' => $this->cpu($config),
            'gpu' => $this->gpu($config),
            'ram' => $this->ram($config),
            'storage' => $this->storage($config),
        ];
    }

    public function cpu(BuildConfiguration $config): ?int
    {
        return $config->cpu()?->performanceTier();
    }

    /**
     * Discrete GPU tier, or a low fixed score when only integrated graphics is used.
     */
    public function gpu(BuildConfiguration $config): ?int
    {
        if ($config->gpu() !== null) {
            return $config->gpu()->performanceTier();
        }

        return $config->cpu()?->hasIntegratedGraphics() ? $this->scoring['integrated_graphics_score'] : null;
    }

    public function ram(BuildConfiguration $config): ?int
    {
        $ram = $config->ram();

        if ($ram === null) {
            return null;
        }

        $totalGb = $ram->totalCapacityGb($config->quantityOf('ram'));
        $score = $this->fromThresholds($this->scoring['ram_capacity_scores'], $totalGb, scaleBelowMinimum: true);

        return min(100, $score + ($this->scoring['ram_type_bonus'][$ram->ramType()] ?? 0));
    }

    /**
     * Fastest drive interface plus a bonus for total capacity.
     */
    public function storage(BuildConfiguration $config): ?int
    {
        $items = $config->items('storage');

        if ($items === []) {
            return null;
        }

        $best = max(array_map(
            fn (ConfigurationItem $item) => $this->scoring['storage_interface_scores'][$item->component->interface()] ?? 0,
            $items,
        ));
        $totalGb = array_sum(array_map(fn (ConfigurationItem $item) => $item->component->capacityGb() * $item->quantity, $items));

        return min(100, $best + $this->fromThresholds($this->scoring['storage_capacity_bonus'], $totalGb));
    }

    /**
     * Value of the highest threshold reached. Below the lowest threshold: 0, or a proportional
     * share of the lowest value when $scaleBelowMinimum (e.g. 4 GB RAM gets half of the 8 GB score).
     *
     * @param  array<int, int>  $thresholds  threshold => value, ascending
     */
    private function fromThresholds(array $thresholds, int $amount, bool $scaleBelowMinimum = false): int
    {
        $value = null;

        foreach ($thresholds as $threshold => $thresholdValue) {
            if ($amount >= $threshold) {
                $value = $thresholdValue;
            }
        }

        if ($value !== null) {
            return $value;
        }

        if (! $scaleBelowMinimum) {
            return 0;
        }

        $lowest = array_key_first($thresholds);

        return (int) round($thresholds[$lowest] * $amount / $lowest);
    }
}
