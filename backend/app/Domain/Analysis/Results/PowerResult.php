<?php

namespace App\Domain\Analysis\Results;

/**
 * Estimated power draw. A rule-based ESTIMATE, not a measurement (D-015).
 */
final readonly class PowerResult
{
    /**
     * @param  array<string, int>  $breakdown  Watts per source, e.g. ['cpu' => 65, 'fans' => 15].
     */
    public function __construct(
        public int $estimatedWatts,
        public int $recommendedPsuWatts,
        public ?int $selectedPsuWatts,
        public array $breakdown,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estimated_watts' => $this->estimatedWatts,
            'recommended_psu_watts' => $this->recommendedPsuWatts,
            'selected_psu_watts' => $this->selectedPsuWatts,
            'breakdown' => $this->breakdown,
        ];
    }
}
