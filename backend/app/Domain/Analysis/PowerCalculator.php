<?php

namespace App\Domain\Analysis;

use App\Domain\Analysis\Results\PowerResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\ConfigurationItem;
use App\Domain\Hardware\Components\Storage;

/**
 * Estimated system power and recommended PSU size (spec section 14).
 *
 *   CPU TDP + GPU TDP + RAM per module + storage per drive + motherboard + fans
 *   recommended PSU = estimate × safety factor, rounded up to the next common PSU size
 *
 * Every constant comes from config/hardware.php `power`, passed in by the provider.
 */
final class PowerCalculator
{
    /**
     * @param  array{ram_per_module: int, storage: array<string, int>, motherboard: int, fans: int,
     *     safety_factor: float, psu_steps: list<int>}  $constants
     */
    public function __construct(private readonly array $constants) {}

    public function calculate(BuildConfiguration $config): PowerResult
    {
        $breakdown = $this->breakdown($config);
        $estimated = array_sum($breakdown);

        return new PowerResult(
            estimatedWatts: $estimated,
            recommendedPsuWatts: $this->recommendedPsu($estimated),
            selectedPsuWatts: $config->psu()?->wattage(),
            breakdown: $breakdown,
        );
    }

    public function recommendedPsu(int $estimatedWatts): int
    {
        if ($estimatedWatts === 0) {
            return 0;
        }

        $needed = (int) ceil($estimatedWatts * $this->constants['safety_factor']);

        foreach ($this->constants['psu_steps'] as $step) {
            if ($step >= $needed) {
                return $step;
            }
        }

        // Beyond the largest common size: round up to the next 100 W.
        return (int) ceil($needed / 100) * 100;
    }

    /**
     * @return array<string, int>
     */
    private function breakdown(BuildConfiguration $config): array
    {
        if ($config->isEmpty()) {
            return [];
        }

        $ram = $config->ram();

        // Motherboard and fans are counted for any build: every PC has them.
        return array_filter([
            'cpu' => $config->cpu()?->tdp() ?? 0,
            'gpu' => $config->gpu()?->tdp() ?? 0,
            'ram' => $ram ? $ram->totalModules($config->quantityOf('ram')) * $this->constants['ram_per_module'] : 0,
            'storage' => array_sum(array_map(
                fn (ConfigurationItem $item) => $this->storageWatts($item->component) * $item->quantity,
                $config->items('storage'),
            )),
            'motherboard' => $this->constants['motherboard'],
            'fans' => $this->constants['fans'],
        ]);
    }

    private function storageWatts(Storage $drive): int
    {
        return $this->constants['storage'][$drive->interface()] ?? 0;
    }
}
