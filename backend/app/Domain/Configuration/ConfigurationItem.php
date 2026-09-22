<?php

namespace App\Domain\Configuration;

use App\Domain\Hardware\Components\HardwareComponent;

/**
 * One selected component and how many units of it (RAM: kits, storage: identical drives).
 */
final readonly class ConfigurationItem
{
    public function __construct(
        public HardwareComponent $component,
        public int $quantity = 1,
    ) {}

    public function category(): string
    {
        return $this->component::category();
    }

    public function totalPrice(): int
    {
        return $this->component->price() * $this->quantity;
    }
}
