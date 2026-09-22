<?php

namespace App\Support\Hardware;

use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Read access to config/hardware.php (the single source of truth for specs).
 *
 * Receives the config array in the constructor so it can be built in tests
 * without the framework; the container binds it with config('hardware').
 */
class SpecSchema
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @return list<string> Category slugs in configuration order.
     */
    public function categories(): array
    {
        return array_keys($this->config['categories']);
    }

    public function hasCategory(string $category): bool
    {
        return isset($this->config['categories'][$category]);
    }

    /**
     * Default name and sort order used to seed the categories table.
     *
     * @return array{name: string, sort_order: int}
     */
    public function categoryDefaults(string $category): array
    {
        $definition = $this->category($category);

        return ['name' => $definition['name'], 'sort_order' => $definition['sort_order']];
    }

    /**
     * @return array<string, array<string, mixed>> Spec key => definition, in display order.
     */
    public function specs(string $category): array
    {
        return $this->category($category)['specs'];
    }

    /**
     * @return array<string, array<string, string>> Enum name => (code => label).
     */
    public function enums(): array
    {
        return $this->config['enums'];
    }

    /**
     * @return array<string, string> Code => label.
     */
    public function enum(string $name): array
    {
        return $this->config['enums'][$name]
            ?? throw new InvalidArgumentException("Unknown hardware enum [{$name}].");
    }

    /**
     * Laravel validation rules for a category's specs, nested under $prefix.
     * Unknown keys are rejected; numbers and booleans must be real JSON types (D-005).
     *
     * @return array<string, list<mixed>>
     */
    public function rulesFor(string $category, string $prefix = 'specs'): array
    {
        $specs = $this->specs($category);
        $rules = [$prefix => ['required', 'array:'.implode(',', array_keys($specs))]];

        foreach ($specs as $key => $definition) {
            $field = "{$prefix}.{$key}";
            $rules[$field] = [...$this->presenceRules($definition, $prefix), ...$this->typeRules($definition)];

            if ($definition['type'] === 'enum_list') {
                $rules["{$field}.*"] = ['string', 'distinct', Rule::in(array_keys($this->enum($definition['enum'])))];
            }
        }

        return $rules;
    }

    /**
     * Filterable specs of a category, keyed by query parameter name.
     * min / max filters use `<param>_min` / `<param>_max`.
     *
     * @return array<string, array{key: string, filter: string, type: string, enum: ?string}>
     */
    public function filters(string $category): array
    {
        $filters = [];

        foreach ($this->specs($category) as $key => $definition) {
            if (! isset($definition['filter'])) {
                continue;
            }

            $param = $definition['filter_param'] ?? $key;
            $param = match ($definition['filter']) {
                'min' => "{$param}_min",
                'max' => "{$param}_max",
                default => $param,
            };

            $filters[$param] = [
                'key' => $key,
                'filter' => $definition['filter'],
                'type' => $definition['type'],
                'enum' => $definition['enum'] ?? null,
            ];
        }

        return $filters;
    }

    /**
     * Validation rules for a category's spec filters (query string or request body),
     * nested under $prefix (e.g. 'filters.').
     *
     * @return array<string, list<mixed>>
     */
    public function filterRulesFor(string $category, string $prefix = ''): array
    {
        $rules = [];

        foreach ($this->filters($category) as $param => $filter) {
            $rules[$prefix.$param] = match (true) {
                $filter['filter'] === 'boolean' => ['nullable', 'boolean'],
                $filter['enum'] !== null => ['nullable', 'string', Rule::in(array_keys($this->enum($filter['enum'])))],
                default => ['nullable', 'integer', 'min:0'],
            };
        }

        return $rules;
    }

    /**
     * Field definitions for the admin product form (enum → select, enum_list → checkboxes,
     * integer → number with unit, boolean → switch). Same source as validation.
     *
     * @return list<array<string, mixed>>
     */
    public function formSchema(string $category): array
    {
        $fields = [];

        foreach ($this->specs($category) as $key => $definition) {
            $field = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'unit' => $definition['unit'] ?? null,
                'required' => $definition['required'] ?? false, // true, or ['when' => [otherKey => value]]
                'min' => $definition['min'] ?? null,
                'max' => $definition['max'] ?? null,
            ];

            if (isset($definition['enum'])) {
                $enum = $this->enum($definition['enum']);
                $field['options'] = array_map(fn (string $code, string $label) => ['value' => $code, 'label' => $label],
                    array_keys($enum), $enum);
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Label and unit of a spec, for filter definitions.
     *
     * @return array<string, mixed>
     */
    public function spec(string $category, string $key): array
    {
        return $this->specs($category)[$key]
            ?? throw new InvalidArgumentException("Unknown spec [{$category}.{$key}].");
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function slots(): array
    {
        return $this->config['slots'];
    }

    /**
     * @return array<string, mixed>
     */
    public function power(): array
    {
        return $this->config['power'];
    }

    /**
     * @return array<string, mixed>
     */
    public function scoring(): array
    {
        return $this->config['scoring'];
    }

    /**
     * @return array<string, mixed>
     */
    private function category(string $category): array
    {
        return $this->config['categories'][$category]
            ?? throw new InvalidArgumentException("Unknown hardware category [{$category}].");
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private function presenceRules(array $definition, string $prefix): array
    {
        $required = $definition['required'] ?? false;

        if ($required === true) {
            return ['required'];
        }

        if (is_array($required)) {
            $other = array_key_first($required['when']);

            return ["required_if:{$prefix}.{$other},{$required['when'][$other]}", 'nullable'];
        }

        return ['nullable'];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<mixed>
     */
    private function typeRules(array $definition): array
    {
        return match ($definition['type']) {
            'integer' => ['integer:strict', "min:{$definition['min']}", "max:{$definition['max']}"],
            'boolean' => ['boolean:strict'],
            'enum' => ['string', Rule::in(array_keys($this->enum($definition['enum'])))],
            'enum_list' => ['array', 'min:1'],
            default => throw new InvalidArgumentException("Unknown spec type [{$definition['type']}]."),
        };
    }
}
