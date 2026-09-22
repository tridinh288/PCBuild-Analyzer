<?php

namespace App\Domain\Hardware\Components;

final class Psu extends HardwareComponent
{
    public static function category(): string
    {
        return 'psu';
    }

    public function wattage(): int
    {
        return $this->int('wattage');
    }

    public function formFactor(): string
    {
        return $this->string('form_factor');
    }

    public function efficiencyRating(): string
    {
        return $this->string('efficiency_rating');
    }
}
