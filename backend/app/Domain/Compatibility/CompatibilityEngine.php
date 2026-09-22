<?php

namespace App\Domain\Compatibility;

use App\Domain\Compatibility\Contracts\CompatibilityRule;
use App\Domain\Compatibility\Contracts\PresenceRule;
use App\Domain\Compatibility\Results\CompatibilityReport;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\CompatibilityStatus;

/**
 * Runs the registered rules. It knows no rule by name: rules are injected (Open/Closed).
 * Compatibility logic exists only here, in PHP, never in SQL (D-013).
 */
final class CompatibilityEngine
{
    /** @var list<CompatibilityRule> */
    private readonly array $rules;

    /**
     * @param  iterable<CompatibilityRule>  $rules
     */
    public function __construct(iterable $rules)
    {
        $this->rules = [...$rules];
    }

    public function check(BuildConfiguration $config): CompatibilityReport
    {
        return $this->run($this->rules, $config);
    }

    /**
     * Evaluates a Builder candidate: $config already contains the candidate. Only rules
     * involving the candidate's category count, so an existing CPU/motherboard mismatch is
     * not blamed on a RAM candidate. Presence rules are left to the full check (D-035).
     */
    public function checkCandidate(BuildConfiguration $config, string $category): CompatibilityReport
    {
        $rules = array_filter($this->rules, fn (CompatibilityRule $rule) => ! $rule instanceof PresenceRule
            && in_array($category, $rule->involves(), true));

        return $this->run($rules, $config);
    }

    /**
     * @return list<CompatibilityRule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @param  array<CompatibilityRule>  $rules
     */
    private function run(array $rules, BuildConfiguration $config): CompatibilityReport
    {
        $results = [];

        foreach ($rules as $rule) {
            $results[] = $rule->appliesTo($config)
                ? $rule->check($config)
                : new CompatibilityResult(CompatibilityStatus::Skipped, $rule->key(), $rule->title(),
                    'Chưa đủ linh kiện để kiểm tra.', [], $rule->involves());
        }

        return new CompatibilityReport($results);
    }
}
