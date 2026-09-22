<?php

namespace App\Services;

use App\Domain\Configuration\BuildConfiguration;

/**
 * A custom selection turned into a configuration, plus the product IDs that could not
 * be used (unknown, inactive, or in the wrong category). The API reports them as
 * `missing` instead of failing (D-019).
 */
final readonly class ResolvedSelection
{
    /**
     * @param  list<array{category: string, id: int}>  $missing
     */
    public function __construct(
        public BuildConfiguration $configuration,
        public array $missing = [],
    ) {}
}
