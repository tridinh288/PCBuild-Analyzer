<?php

namespace App\Domain\Compatibility\Specifications;

final class AndSpecification extends Specification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $value): bool
    {
        return $this->left->isSatisfiedBy($value) && $this->right->isSatisfiedBy($value);
    }
}
