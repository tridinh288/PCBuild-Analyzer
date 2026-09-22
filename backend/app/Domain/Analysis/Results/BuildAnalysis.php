<?php

namespace App\Domain\Analysis\Results;

use App\Domain\Compatibility\Results\CompatibilityReport;

final readonly class BuildAnalysis
{
    /**
     * @param  list<string>  $missingSlots
     */
    public function __construct(
        public CompatibilityReport $compatibility,
        public PowerResult $power,
        public PriceResult $price,
        public PerformanceResult $performance,
        public array $missingSlots,
    ) {}

    public function isComplete(): bool
    {
        return $this->missingSlots === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'compatibility' => $this->compatibility->toArray(),
            'power' => $this->power->toArray(),
            'price' => $this->price->toArray(),
            'performance' => $this->performance->toArray(),
            'missing_slots' => $this->missingSlots,
            'is_complete' => $this->isComplete(),
        ];
    }
}
