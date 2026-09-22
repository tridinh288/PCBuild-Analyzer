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
