<?php

namespace App\Domain\Hardware\Components;

/**
 * A RAM kit. Capacity and module count are per kit; a configuration may hold several kits.
 */
final class Ram extends HardwareComponent
{
    public static function category(): string
    {
        return 'ram';
    }

    public function ramType(): string
    {
        return $this->string('ram_type');
    }

    public function capacityGb(): int
    {
        return $this->int('capacity_gb');
    }

    public function modules(): int
    {
        return $this->int('modules');
    }

    public function speedMhz(): int
    {
        return $this->int('speed_mhz');
    }

    public function totalModules(int $kits): int
    {
        return $this->modules() * $kits;
    }

    public function totalCapacityGb(int $kits): int
    {
        return $this->capacityGb() * $kits;
    }
}
