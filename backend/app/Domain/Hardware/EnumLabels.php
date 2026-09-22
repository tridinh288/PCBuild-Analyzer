<?php

namespace App\Domain\Hardware;

/**
 * Display labels for spec codes ('matx' => 'Micro-ATX'), used in rule messages.
 * Built from config/hardware.php `enums` by the service provider (D-030).
 */
final class EnumLabels
{
    /**
     * @param  array<string, array<string, string>>  $enums
     */
    public function __construct(private readonly array $enums) {}

    public function label(string $enum, string $code): string
    {
        return $this->enums[$enum][$code] ?? $code;
    }

    /**
     * @param  list<string>  $codes
     */
    public function list(string $enum, array $codes): string
    {
        return implode(', ', array_map(fn (string $code) => $this->label($enum, $code), $codes));
    }
}
