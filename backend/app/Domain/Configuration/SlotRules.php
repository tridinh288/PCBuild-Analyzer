<?php

namespace App\Domain\Configuration;

use InvalidArgumentException;

/**
 * Builder slot rules from config/hardware.php `slots`: which slots exist (in display order),
 * which are required, and how many items each accepts.
 *
 * `required` is true or a named condition; conditions are code, not config, because they
 * describe hardware logic (a CPU without integrated graphics needs a GPU).
 */
final class SlotRules
{
    /**
     * @param  array<string, array{required: bool|string, multiple: false|string, max_quantity?: int}>  $slots
     */
    public function __construct(private readonly array $slots) {}

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return array_keys($this->slots);
    }

    public function has(string $category): bool
    {
        return isset($this->slots[$category]);
    }

    /**
     * Several different products in one slot (storage).
     */
    public function acceptsSeveralProducts(string $category): bool
    {
        return $this->slot($category)['multiple'] === 'items';
    }

    /**
     * More than one unit allowed (RAM kits, identical drives).
     */
    public function acceptsQuantity(string $category): bool
    {
        return $this->slot($category)['multiple'] !== false;
    }

    public function maxQuantity(string $category): int
    {
        return $this->slot($category)['max_quantity'] ?? 1;
    }

    /**
     * Required slots that are still empty, in slot order.
     *
     * @return list<string>
     */
    public function missing(BuildConfiguration $config): array
    {
        return array_values(array_filter(
            $this->categories(),
            fn (string $category) => ! $config->has($category) && $this->isRequired($category, $config),
        ));
    }

    public function isRequired(string $category, BuildConfiguration $config): bool
    {
        $required = $this->slot($category)['required'];

        if (is_bool($required)) {
            return $required;
        }

        // Conditional slots depend on the CPU; until a CPU is chosen they are not reported as missing.
        $cpu = $config->cpu();

        return match ($required) {
            'unless_cpu_has_integrated_graphics' => $cpu !== null && ! $cpu->hasIntegratedGraphics(),
            'unless_cpu_includes_cooler' => $cpu !== null && ! $cpu->includesCooler(),
            default => throw new InvalidArgumentException("Unknown slot condition [{$required}]."),
        };
    }

    /**
     * @return array{required: bool|string, multiple: false|string, max_quantity?: int}
     */
    private function slot(string $category): array
    {
        return $this->slots[$category]
            ?? throw new InvalidArgumentException("Unknown builder slot [{$category}].");
    }
}
