<?php

namespace App\Domain\Compatibility\Specifications;

/**
 * The value does not exceed a limit (GPU length, cooler height, RAM modules and capacity, drive slots).
 */
final class FitsWithin extends Specification
{
    public function __construct(private readonly int $limit) {}

    public function isSatisfiedBy(mixed $value): bool
    {
        return $value <= $this->limit;
    }
}
