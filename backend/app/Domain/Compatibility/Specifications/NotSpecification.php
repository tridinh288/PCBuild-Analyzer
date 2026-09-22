<?php

namespace App\Domain\Compatibility\Specifications;

final class NotSpecification extends Specification
{
    public function __construct(private readonly Specification $inner) {}

    public function isSatisfiedBy(mixed $value): bool
    {
        return ! $this->inner->isSatisfiedBy($value);
    }
}
