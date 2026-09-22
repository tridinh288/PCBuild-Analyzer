<?php

namespace App\Domain\Hardware\Components;

final class Motherboard extends HardwareComponent
{
    public static function category(): string
    {
        return 'motherboard';
    }

    public function socket(): string
    {
        return $this->string('socket');
    }

    public function ramType(): string
    {
        return $this->string('ram_type');
    }

    public function ramSlots(): int
    {
        return $this->int('ram_slots');
    }

    public function maxRamGb(): int
    {
        return $this->int('max_ram_gb');
    }

    public function formFactor(): string
    {
        return $this->string('form_factor');
    }

    public function m2Slots(): int
    {
        return $this->int('m2_slots');
    }

    public function sataPorts(): int
    {
        return $this->int('sata_ports');
    }
}
