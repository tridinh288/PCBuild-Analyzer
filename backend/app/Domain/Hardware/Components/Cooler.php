<?php

namespace App\Domain\Hardware\Components;

final class Cooler extends HardwareComponent
{
    public static function category(): string
    {
        return 'cooler';
    }

    public function type(): string
    {
        return $this->string('type');
    }

    public function isAir(): bool
    {
        return $this->type() === 'air';
    }

    /**
     * @return list<string>
     */
    public function supportedSockets(): array
    {
        return $this->list('supported_sockets');
    }

    public function supportsSocket(string $socket): bool
    {
        return in_array($socket, $this->supportedSockets(), true);
    }

    public function maxTdp(): int
    {
        return $this->int('max_tdp');
    }

    /**
     * Height of an air cooler; null for AIO coolers (the pump block height does not matter here).
     */
    public function heightMm(): ?int
    {
        return $this->nullableInt('height_mm');
    }

    public function radiatorMm(): ?int
    {
        return $this->nullableInt('radiator_mm');
    }
}
