<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Contracts\CompatibilityRule;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\CompatibilityStatus;

/**
 * Shared helpers for rules: building results and the default appliesTo()
 * (every involved slot is filled).
 */
abstract class Rule implements CompatibilityRule
{
    public function appliesTo(BuildConfiguration $config): bool
    {
        foreach ($this->involves() as $category) {
            if (! $config->has($category)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Slots highlighted when this rule reports a problem. Defaults to every involved slot.
     *
     * @return list<string>
     */
    protected function blames(): array
    {
        return $this->involves();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function compatible(string $message, array $details = []): CompatibilityResult
    {
        return $this->result(CompatibilityStatus::Compatible, $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function warning(string $message, array $details = []): CompatibilityResult
    {
        return $this->result(CompatibilityStatus::Warning, $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function incompatible(string $message, array $details = []): CompatibilityResult
    {
        return $this->result(CompatibilityStatus::Incompatible, $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function result(CompatibilityStatus $status, string $message, array $details): CompatibilityResult
    {
        return new CompatibilityResult($status, $this->key(), $this->title(), $message, $details, $this->blames());
    }
}
