<?php

namespace App\Support\Hardware;

/**
 * Turns raw spec values into display rows for API Resources (D-005):
 *   { key, label, value, display, highlight }
 * Order and labels come from config/hardware.php through SpecSchema.
 */
class SpecFormatter
{
    public function __construct(private readonly SpecSchema $schema) {}

    /**
     * @param  array<string, mixed>  $specs
     * @return list<array{key: string, label: string, value: mixed, display: string, highlight: bool}>
     */
    public function format(string $category, array $specs): array
    {
        $rows = [];

        foreach ($this->schema->specs($category) as $key => $definition) {
            if (! isset($specs[$key])) {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'label' => $definition['label'],
                'value' => $specs[$key],
                'display' => $this->display($definition, $specs[$key]),
                'highlight' => $definition['highlight'] ?? false,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function display(array $definition, mixed $value): string
    {
        return match ($definition['type']) {
            'integer' => number_format((int) $value, 0, ',', '.').(isset($definition['unit']) ? " {$definition['unit']}" : ''),
            'boolean' => $value ? 'Có' : 'Không',
            'enum' => $this->schema->enum($definition['enum'])[$value] ?? (string) $value,
            'enum_list' => implode(', ', array_map(
                fn (string $code) => $this->schema->enum($definition['enum'])[$code] ?? $code,
                (array) $value,
            )),
            default => (string) $value,
        };
    }
}
