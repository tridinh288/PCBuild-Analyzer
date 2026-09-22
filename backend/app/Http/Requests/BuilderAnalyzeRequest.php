<?php

namespace App\Http\Requests;

use App\Domain\Configuration\SlotRules;
use App\Enums\BuildPurpose;
use App\Rules\ValidSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/builder/analyze
 */
class BuilderAnalyzeRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'selected' => ['present', 'array', new ValidSelection($this->container->make(SlotRules::class))],
            'profile' => ['nullable', Rule::enum(BuildPurpose::class)],
        ];
    }

    public function profile(): ?BuildPurpose
    {
        return BuildPurpose::tryFrom((string) $this->validated('profile'));
    }
}
