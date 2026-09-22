<?php

namespace App\Domain\Compatibility\Results;

use App\Enums\CompatibilityStatus;

/**
 * Outcome of one rule. Never a bare true/false (spec section 12).
 */
final readonly class CompatibilityResult
{
    /**
     * @param  array<string, mixed>  $details  The numbers or codes the rule compared.
     * @param  list<string>  $categories  Builder slots to highlight when this is a problem.
     */
    public function __construct(
        public CompatibilityStatus $status,
        public string $rule,
        public string $title,
        public string $message,
        public array $details = [],
        public array $categories = [],
    ) {}

    public function isProblem(): bool
    {
        return $this->status->isProblem();
    }

    /**
     * @return array{status: string, rule: string, title: string, message: string, details: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'rule' => $this->rule,
            'title' => $this->title,
            'message' => $this->message,
            'details' => (object) $this->details, // always a JSON object, even when empty
        ];
    }
}
