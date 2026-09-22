<?php

namespace App\Domain\Compatibility\Contracts;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;

/**
 * One hardware constraint (D-012). Rules are registered in AnalysisServiceProvider, so a
 * new rule needs no change to the engine or any controller (Open/Closed Principle).
 */
interface CompatibilityRule
{
    /**
     * Stable key used by the API and the frontend, e.g. 'cpu_motherboard_socket'.
     */
    public function key(): string;

    /**
     * Short Vietnamese title for the UI.
     */
    public function title(): string;

    /**
     * Categories this rule looks at. A candidate only shows results of rules involving
     * its own category.
     *
     * @return list<string>
     */
    public function involves(): array;

    /**
     * False when a part the rule needs is not selected yet: the rule is then reported
     * as skipped, not as an error, so parts can be chosen in any order.
     */
    public function appliesTo(BuildConfiguration $config): bool;

    /**
     * Only called when appliesTo() is true.
     */
    public function check(BuildConfiguration $config): CompatibilityResult;
}
