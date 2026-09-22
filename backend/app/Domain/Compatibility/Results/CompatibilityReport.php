<?php

namespace App\Domain\Compatibility\Results;

use App\Enums\CompatibilityStatus;

/**
 * All rule results for one configuration, with the overall status and counts
 * used by the summary banner and the red/yellow Builder slots.
 */
final readonly class CompatibilityReport
{
    /**
     * @param  list<CompatibilityResult>  $results
     */
    public function __construct(public array $results) {}

    /**
     * The worst status among the rules that applied; compatible when nothing failed.
     */
    public function status(): CompatibilityStatus
    {
        $worst = CompatibilityStatus::Compatible;

        foreach ($this->results as $result) {
            if ($result->status->severity() > $worst->severity()) {
                $worst = $result->status;
            }
        }

        return $worst;
    }

    public function errors(): int
    {
        return $this->count(CompatibilityStatus::Incompatible);
    }

    public function warnings(): int
    {
        return $this->count(CompatibilityStatus::Warning);
    }

    public function hasErrors(): bool
    {
        return $this->errors() > 0;
    }

    /**
     * @return list<CompatibilityResult>
     */
    public function problems(): array
    {
        return array_values(array_filter($this->results, fn (CompatibilityResult $r) => $r->isProblem()));
    }

    /**
     * Rule keys with a problem, per Builder slot.
     *
     * @return array<string, list<string>>
     */
    public function problemsByCategory(): array
    {
        $byCategory = [];

        foreach ($this->problems() as $result) {
            foreach ($result->categories as $category) {
                $byCategory[$category][] = $result->rule;
            }
        }

        return $byCategory;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status()->value,
            'errors' => $this->errors(),
            'warnings' => $this->warnings(),
            'results' => array_map(fn (CompatibilityResult $r) => $r->toArray(), $this->results),
            'by_category' => $this->problemsByCategory(),
        ];
    }

    private function count(CompatibilityStatus $status): int
    {
        return count(array_filter($this->results, fn (CompatibilityResult $r) => $r->status === $status));
    }
}
