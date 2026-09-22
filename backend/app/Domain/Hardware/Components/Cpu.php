<?php

namespace App\Domain\Hardware\Components;

final class Cpu extends HardwareComponent
{
    public static function category(): string
    {
        return 'cpu';
    }

    public function socket(): string
    {
        return $this->string('socket');
    }

    public function cores(): int
    {
        return $this->int('cores');
    }

    public function threads(): int
    {
        return $this->int('threads');
    }

    public function tdp(): int
    {
        return $this->int('tdp');
    }

    public function hasIntegratedGraphics(): bool
    {
        return $this->bool('has_integrated_graphics');
    }

    public function includesCooler(): bool
    {
        return $this->bool('includes_cooler');
    }

    /**
     * Project-defined 1–100 value entered by the admin; not benchmark data (D-015).
     */
    public function performanceTier(): int
    {
        return $this->int('performance_tier');
    }
}
