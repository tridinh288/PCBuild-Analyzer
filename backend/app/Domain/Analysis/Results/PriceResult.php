<?php

namespace App\Domain\Analysis\Results;

final readonly class PriceResult
{
    /**
     * @param  list<array{category: string, amount: int, percent: float}>  $byCategory
     */
    public function __construct(
        public int $total,
        public array $byCategory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['total' => $this->total, 'by_category' => $this->byCategory];
    }
}
