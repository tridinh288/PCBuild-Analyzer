<?php

namespace App\Domain\Hardware\Components;

final class Storage extends HardwareComponent
{
    public static function category(): string
    {
        return 'storage';
    }

    public function interface(): string
    {
        return $this->string('interface');
    }

    public function form(): string
    {
        return $this->string('form');
    }

    public function capacityGb(): int
    {
        return $this->int('capacity_gb');
    }

    /**
     * Uses an M.2 slot on the motherboard.
     */
    public function isM2(): bool
    {
        return $this->form() === 'm2';
    }

    /**
     * Uses a SATA port on the motherboard.
     */
    public function isSata(): bool
    {
        return $this->interface() === 'sata';
    }
}
