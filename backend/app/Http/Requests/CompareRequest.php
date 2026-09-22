<?php

namespace App\Http\Requests;

use App\Domain\Configuration\SlotRules;
use App\Enums\BuildPurpose;
use App\Rules\ValidSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/compare — 2 or 3 configurations, templates and/or custom selections.
 */
class CompareRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'configurations' => ['required', 'array', 'min:2', 'max:3'],
            'configurations.*.type' => ['required', Rule::in(['template', 'custom'])],
            'configurations.*.slug' => ['required_if:configurations.*.type,template', 'string', 'max:180'],
            'configurations.*.selected' => ['required_if:configurations.*.type,custom', 'array',
                new ValidSelection($this->container->make(SlotRules::class))],
            'configurations.*.label' => ['nullable', 'string', 'max:60'],
            'profile' => ['nullable', Rule::enum(BuildPurpose::class)],
        ];
    }

    public function profile(): ?BuildPurpose
    {
        return BuildPurpose::tryFrom((string) $this->validated('profile'));
    }
}
