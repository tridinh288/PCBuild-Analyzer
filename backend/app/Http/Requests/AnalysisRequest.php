<?php

namespace App\Http\Requests;

use App\Enums\BuildPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /api/builds/{slug}/analysis?profile=gaming
 */
class AnalysisRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'profile' => ['nullable', Rule::enum(BuildPurpose::class)],
        ];
    }

    public function profile(): ?BuildPurpose
    {
        return BuildPurpose::tryFrom((string) $this->validated('profile'));
    }
}
