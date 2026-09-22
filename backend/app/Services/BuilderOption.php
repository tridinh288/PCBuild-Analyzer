<?php

namespace App\Services;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Enums\CompatibilityStatus;
use App\Models\Product;

/**
 * One candidate product for a Builder slot, with its status against the current selection.
 */
final readonly class BuilderOption
{
    /**
     * @param  list<CompatibilityResult>  $issues  Warnings and errors from rules involving this slot
     */
    public function __construct(
        public Product $product,
        public CompatibilityStatus $status,
        public array $issues,
        public bool $selected,
    ) {}
}
