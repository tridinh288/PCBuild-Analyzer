<?php

namespace App\Domain\Hardware;

use App\Domain\Hardware\Components\Cooler;
use App\Domain\Hardware\Components\Cpu;
use App\Domain\Hardware\Components\Gpu;
use App\Domain\Hardware\Components\HardwareComponent;
use App\Domain\Hardware\Components\Motherboard;
use App\Domain\Hardware\Components\PcCase;
use App\Domain\Hardware\Components\Psu;
use App\Domain\Hardware\Components\Ram;
use App\Domain\Hardware\Components\Storage;
use InvalidArgumentException;

/**
 * Factory Pattern: the only place that maps a category slug to a component class.
 * Takes plain attributes, so the Domain stays free of Eloquent (D-030).
 */
class HardwareFactory
{
    /** @var array<string, class-string<HardwareComponent>> */
    private const CLASSES = [
        'cpu' => Cpu::class,
        'motherboard' => Motherboard::class,
        'ram' => Ram::class,
        'gpu' => Gpu::class,
        'storage' => Storage::class,
        'psu' => Psu::class,
        'case' => PcCase::class,
        'cooler' => Cooler::class,
    ];

    /**
     * @param  array{id: int, name: string, price: int, specs: array<string, mixed>, slug?: string, brand?: string}  $attributes
     */
    public function make(string $category, array $attributes): HardwareComponent
    {
        $class = self::CLASSES[$category]
            ?? throw new InvalidArgumentException("Unknown hardware category [{$category}].");

        return new $class(
            id: $attributes['id'],
            name: $attributes['name'],
            price: $attributes['price'],
            specs: $attributes['specs'],
            slug: $attributes['slug'] ?? '',
            brand: $attributes['brand'] ?? '',
        );
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return array_keys(self::CLASSES);
    }
}
