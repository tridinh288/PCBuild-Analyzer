<?php

namespace App\Domain\Hardware\Components;

/**
 * A selected or candidate product as the analysis engine sees it: identity, price,
 * and typed access to its specs. No Eloquent (D-030); built by HardwareFactory.
 */
abstract class HardwareComponent
{
    /**
     * @param  array<string, mixed>  $specs  Raw specs, already validated against config/hardware.php.
     */
    final public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly int $price,
        private readonly array $specs,
        private readonly string $slug = '',
        private readonly string $brand = '',
    ) {}

    /**
     * Category slug, matching config/hardware.php.
     */
    abstract public static function category(): string;

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function brand(): string
    {
        return $this->brand;
    }

    /**
     * Unit price in VND.
     */
    public function price(): int
    {
        return $this->price;
    }

    /**
     * @return array<string, mixed>
     */
    public function specs(): array
    {
        return $this->specs;
    }

    protected function int(string $key): int
    {
        return (int) $this->specs[$key];
    }

    protected function nullableInt(string $key): ?int
    {
        return isset($this->specs[$key]) ? (int) $this->specs[$key] : null;
    }

    protected function string(string $key): string
    {
        return (string) $this->specs[$key];
    }

    protected function bool(string $key): bool
    {
        return (bool) $this->specs[$key];
    }

    /**
     * @return list<string>
     */
    protected function list(string $key): array
    {
        return array_values($this->specs[$key] ?? []);
    }
}
