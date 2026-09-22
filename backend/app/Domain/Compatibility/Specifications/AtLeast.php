<?php

namespace App\Domain\Compatibility\Specifications;

/**
 * The value reaches a minimum (PSU wattage, cooler TDP rating).
 */
final class AtLeast extends Specification
{
    public function __construct(private readonly int $minimum) {}

    public function isSatisfiedBy(mixed $value): bool
    {
        return $value >= $this->minimum;
    }
}
