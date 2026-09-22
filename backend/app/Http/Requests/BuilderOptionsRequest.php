<?php

namespace App\Http\Requests;

use App\Domain\Configuration\SlotRules;
use App\Rules\ValidSelection;
use App\Services\CatalogFilterService;
use App\Support\Hardware\SpecSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/builder/options
 */
class BuilderOptionsRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $schema = $this->container->make(SpecSchema::class);
        $category = $this->input('category');

        $rules = [
            'category' => ['required', 'string', Rule::in($schema->categories())],
            'selected' => ['present', 'array', new ValidSelection($this->container->make(SlotRules::class))],
            'filters' => ['nullable', 'array'],
            ...ComponentIndexRequest::commonFilterRules('filters.'),
            'compatible_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(CatalogFilterService::SORTS))],
        ];

        if (is_string($category) && $schema->hasCategory($category)) {
            $rules += $schema->filterRulesFor($category, 'filters.');
        }

        return $rules;
    }
}
