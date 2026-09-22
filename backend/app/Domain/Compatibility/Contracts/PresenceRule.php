<?php

namespace App\Domain\Compatibility\Contracts;

/**
 * Marker for rules that report a missing part (e.g. no GPU for a CPU without integrated
 * graphics) rather than a conflict between two selected parts.
 *
 * They run in the full configuration check but not when a Builder candidate is evaluated:
 * choosing a CPU first must not paint every "F" CPU red because no GPU is chosen yet (D-035).
 */
interface PresenceRule extends CompatibilityRule {}
