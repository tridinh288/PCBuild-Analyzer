<?php

namespace App\Domain\Hardware\Components;

final class Gpu extends HardwareComponent
{
    public static function category(): string
    {
        return 'gpu';
    }

    public function vramGb(): int
    {
        return $this->int('vram_gb');
    }

    public function lengthMm(): int
    {
        return $this->int('length_mm');
    }

    public function tdp(): int
    {
        return $this->int('tdp');
    }

    /**
     * Project-defined 1–100 value entered by the admin; not benchmark data (D-015).
     */
    public function performanceTier(): int
    {
        return $this->int('performance_tier');
    }
}
