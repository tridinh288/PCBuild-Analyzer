<?php

namespace App\Domain\Compatibility\Specifications;

/**
 * The value is one of the allowed codes (sockets, form factors). Works because related
 * categories share enum codes (D-005).
 */
final class ValueInSet extends Specification
{
    /**
     * @param  list<string>  $allowed
     */
    public function __construct(private readonly array $allowed) {}

    public function isSatisfiedBy(mixed $value): bool
    {
        return in_array($value, $this->allowed, true);
    }
}
