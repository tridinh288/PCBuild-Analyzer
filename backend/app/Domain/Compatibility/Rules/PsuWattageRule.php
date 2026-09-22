<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Analysis\PowerCalculator;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\AtLeast;
use App\Domain\Configuration\BuildConfiguration;

/**
 * Below the estimated power → incompatible; enough, but below the recommended size → warning.
 * Uses PowerCalculator, so the estimate is computed in one place only.
 */
final class PsuWattageRule extends Rule
{
    public function __construct(private readonly PowerCalculator $power) {}

    public function key(): string
    {
        return 'psu_wattage';
    }

    public function title(): string
    {
        return 'Công suất nguồn';
    }

    public function involves(): array
    {
        return ['psu', 'cpu', 'gpu'];
    }

    /**
     * The GPU is optional: a build on integrated graphics is still checked.
     */
    public function appliesTo(BuildConfiguration $config): bool
    {
        return $config->has('psu') && $config->has('cpu');
    }

    protected function blames(): array
    {
        return ['psu'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $power = $this->power->calculate($config);
        $wattage = $config->psu()->wattage();
        $details = [
            'estimated_power' => $power->estimatedWatts,
            'recommended_psu' => $power->recommendedPsuWatts,
            'selected_psu' => $wattage,
        ];

        $sufficient = new AtLeast($power->estimatedWatts);
        $belowRecommendation = $sufficient->and((new AtLeast($power->recommendedPsuWatts))->not());

        if (! $sufficient->isSatisfiedBy($wattage)) {
            return $this->incompatible("Nguồn {$wattage}W thấp hơn công suất ước tính {$power->estimatedWatts}W của hệ thống.", $details);
        }

        if ($belowRecommendation->isSatisfiedBy($wattage)) {
            return $this->warning("Công suất PSU thấp hơn mức khuyến nghị {$power->recommendedPsuWatts}W (ước tính {$power->estimatedWatts}W).", $details);
        }

        return $this->compatible("Nguồn {$wattage}W đáp ứng mức khuyến nghị {$power->recommendedPsuWatts}W.", $details);
    }
}
