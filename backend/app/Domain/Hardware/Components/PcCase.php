<?php

namespace App\Domain\Hardware\Components;

/**
 * Named PcCase because `case` is a reserved word in PHP.
 */
final class PcCase extends HardwareComponent
{
    public static function category(): string
    {
        return 'case';
    }

    /**
     * @return list<string>
     */
    public function supportedFormFactors(): array
    {
        return $this->list('supported_form_factors');
    }

    public function supportsFormFactor(string $formFactor): bool
    {
        return in_array($formFactor, $this->supportedFormFactors(), true);
    }

    public function maxGpuLengthMm(): int
    {
        return $this->int('max_gpu_length_mm');
    }

    public function maxCoolerHeightMm(): int
    {
        return $this->int('max_cooler_height_mm');
    }

    /**
     * @return list<string>
     */
    public function supportedPsuFormFactors(): array
    {
        return $this->list('supported_psu_form_factors');
    }

    public function supportsPsuFormFactor(string $formFactor): bool
    {
        return in_array($formFactor, $this->supportedPsuFormFactors(), true);
    }
}
