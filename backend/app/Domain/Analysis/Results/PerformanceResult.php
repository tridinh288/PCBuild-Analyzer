<?php

namespace App\Domain\Analysis\Results;

use App\Enums\BuildPurpose;

/**
 * Estimated configuration score for one profile ("Điểm cấu hình ước tính").
 * Project-defined scoring rules, not benchmark results (D-015).
 */
final readonly class PerformanceResult
{
    /**
     * @param  array{cpu: ?int, gpu: ?int, ram: ?int, storage: ?int}  $subScores  null = part missing
     * @param  array<string, float>  $weights
     * @param  list<array{reason: string, penalty: int}>  $adjustments  Profile-specific penalties (D-031)
     * @param  list<string>  $notes
     */
    public function __construct(
        public BuildPurpose $profile,
        public int $score,
        public array $subScores,
        public array $weights,
        public array $adjustments = [],
        public array $notes = [],
    ) {}

    public function withNote(string $note): self
    {
        return new self($this->profile, $this->score, $this->subScores, $this->weights,
            $this->adjustments, [...$this->notes, $note]);
    }

    /**
     * @return list<string>
     */
    public function missingParts(): array
    {
        return array_keys(array_filter($this->subScores, fn (?int $score) => $score === null));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile->value,
            'profile_label' => $this->profile->label(),
            'score' => $this->score,
            'sub_scores' => $this->subScores,
            'weights' => $this->weights,
            'adjustments' => $this->adjustments,
            'missing_parts' => $this->missingParts(),
            'notes' => $this->notes,
        ];
    }
}
