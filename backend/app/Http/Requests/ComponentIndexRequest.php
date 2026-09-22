<?php

namespace App\Http\Requests;

use App\Services\CatalogFilterService;
use App\Support\Hardware\SpecSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GET /api/components — common filters plus the category's spec filters from config.
 */
class ComponentIndexRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $schema = $this->container->make(SpecSchema::class);
        $category = $this->query('category');

        $rules = [
            'category' => ['required', 'string', Rule::in($schema->categories())],
            ...self::commonFilterRules(),
            'sort' => ['nullable', Rule::in(array_keys(CatalogFilterService::SORTS))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];

        if (is_string($category) && $schema->hasCategory($category)) {
            $rules += $schema->filterRulesFor($category);
        }

        return $rules;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function commonFilterRules(string $prefix = ''): array
    {
        return [
            $prefix.'search' => ['nullable', 'string', 'max:100'],
            $prefix.'brand' => ['nullable', 'string', 'max:60'],
            $prefix.'price_min' => ['nullable', 'integer', 'min:0'],
            $prefix.'price_max' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Validated filters without paging/sorting keys.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->validated())->except(['sort', 'page', 'per_page'])->all();
    }
}
