<?php

namespace App\Domain\Configuration;

use App\Domain\Hardware\Components\Cooler;
use App\Domain\Hardware\Components\Cpu;
use App\Domain\Hardware\Components\Gpu;
use App\Domain\Hardware\Components\HardwareComponent;
use App\Domain\Hardware\Components\Motherboard;
use App\Domain\Hardware\Components\PcCase;
use App\Domain\Hardware\Components\Psu;
use App\Domain\Hardware\Components\Ram;
use InvalidArgumentException;

/**
 * Immutable value object: the components of one PC build, from a template or a custom
 * selection (D-011). Rules and analyzers only ever see this, never the Eloquent Build.
 *
 * with() / without() return a new instance, so evaluating a candidate part can never
 * change the configuration it was evaluated against.
 */
final class BuildConfiguration
{
    /**
     * @param  array<string, list<ConfigurationItem>>  $items  Keyed by category.
     */
    private function __construct(
        private readonly SlotRules $slots,
        private readonly array $items,
    ) {}

    public static function empty(SlotRules $slots): self
    {
        return new self($slots, []);
    }

    /**
     * Single slots are replaced. Slots that accept several products (storage) get the
     * component added; adding a product that is already there increases its quantity.
     */
    public function with(HardwareComponent $component, int $quantity = 1): self
    {
        $category = $component::category();

        if (! $this->slots->has($category)) {
            throw new InvalidArgumentException("Unknown builder slot [{$category}].");
        }

        if ($quantity < 1 || ($quantity > 1 && ! $this->slots->acceptsQuantity($category))) {
            throw new InvalidArgumentException("Invalid quantity {$quantity} for slot [{$category}].");
        }

        if (! $this->slots->acceptsSeveralProducts($category)) {
            return $this->withItems($category, [new ConfigurationItem($component, $quantity)]);
        }

        $items = $this->items($category);

        foreach ($items as $index => $item) {
            if ($item->component->id() === $component->id()) {
                $items[$index] = new ConfigurationItem($component, $item->quantity + $quantity);

                return $this->withItems($category, $items);
            }
        }

        return $this->withItems($category, [...$items, new ConfigurationItem($component, $quantity)]);
    }

    /**
     * Removes the whole slot, or only one product from a multi-product slot.
     */
    public function without(string $category, ?int $productId = null): self
    {
        $items = $productId === null ? [] : array_values(array_filter(
            $this->items($category),
            fn (ConfigurationItem $item) => $item->component->id() !== $productId,
        ));

        return $this->withItems($category, $items);
    }

    public function has(string $category): bool
    {
        return $this->items($category) !== [];
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * The (first) component in a slot.
     */
    public function get(string $category): ?HardwareComponent
    {
        return ($this->items[$category][0] ?? null)?->component;
    }

    /**
     * @return list<ConfigurationItem>
     */
    public function items(string $category): array
    {
        return $this->items[$category] ?? [];
    }

    /**
     * All items in slot order.
     *
     * @return list<ConfigurationItem>
     */
    public function allItems(): array
    {
        return array_merge(...array_map(fn (string $category) => $this->items($category), $this->slots->categories()));
    }

    /**
     * Total units in a slot (RAM kits, storage drives).
     */
    public function quantityOf(string $category): int
    {
        return array_sum(array_map(fn (ConfigurationItem $item) => $item->quantity, $this->items($category)));
    }

    /**
     * @return list<string>
     */
    public function missingRequiredSlots(): array
    {
        return $this->slots->missing($this);
    }

    public function slotRules(): SlotRules
    {
        return $this->slots;
    }

    public function cpu(): ?Cpu
    {
        return $this->typed('cpu', Cpu::class);
    }

    public function motherboard(): ?Motherboard
    {
        return $this->typed('motherboard', Motherboard::class);
    }

    public function ram(): ?Ram
    {
        return $this->typed('ram', Ram::class);
    }

    public function gpu(): ?Gpu
    {
        return $this->typed('gpu', Gpu::class);
    }

    public function psu(): ?Psu
    {
        return $this->typed('psu', Psu::class);
    }

    public function pcCase(): ?PcCase
    {
        return $this->typed('case', PcCase::class);
    }

    public function cooler(): ?Cooler
    {
        return $this->typed('cooler', Cooler::class);
    }

    /**
     * @param  list<ConfigurationItem>  $items
     */
    private function withItems(string $category, array $items): self
    {
        $all = $this->items;

        if ($items === []) {
            unset($all[$category]);
        } else {
            $all[$category] = $items;
        }

        return new self($this->slots, $all);
    }

    /**
     * @template T of HardwareComponent
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    private function typed(string $category, string $class): ?HardwareComponent
    {
        $component = $this->get($category);

        return $component instanceof $class ? $component : null;
    }
}
