<?php

namespace App\Domain\Analysis\Results;

/**
 * Side-by-side facts for 2–3 configurations and what changed between neighbours.
 * Deliberately has no "winner" (D-017).
 */
final readonly class ComparisonResult
{
    /**
     * @param  list<array<string, mixed>>  $configurations  Label and key metrics per configuration
     * @param  list<array{category: string, values: list<list<string>>}>  $slots  Component names per slot, per configuration
     * @param  list<array{from: int, to: int, changes: list<array<string, mixed>>}>  $differences
     */
    public function __construct(
        public array $configurations,
        public array $slots,
        public array $differences,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'configurations' => $this->configurations,
            'slots' => $this->slots,
            'differences' => $this->differences,
        ];
    }
}
