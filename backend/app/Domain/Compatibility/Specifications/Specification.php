<?php

namespace App\Domain\Compatibility\Specifications;

/**
 * Specification Pattern: a small, reusable boolean condition over a plain value (D-014).
 * It knows nothing about BuildConfiguration; rules pick the values and use specifications.
 */
abstract class Specification
{
    abstract public function isSatisfiedBy(mixed $value): bool;

    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }

    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }

    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}
