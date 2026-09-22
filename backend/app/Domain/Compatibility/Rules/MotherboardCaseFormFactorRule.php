<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\ValueInSet;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Hardware\EnumLabels;

final class MotherboardCaseFormFactorRule extends Rule
{
    public function __construct(private readonly EnumLabels $labels) {}

    public function key(): string
    {
        return 'motherboard_case_form_factor';
    }

    public function title(): string
    {
        return 'Kích thước bo mạch chủ và vỏ case';
    }

    public function involves(): array
    {
        return ['motherboard', 'case'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $formFactor = $config->motherboard()->formFactor();
        $supported = $config->pcCase()->supportedFormFactors();
        $details = ['motherboard_form_factor' => $formFactor, 'case_supported_form_factors' => $supported];
        $label = $this->labels->label('board_form_factor', $formFactor);

        if ((new ValueInSet($supported))->isSatisfiedBy($formFactor)) {
            return $this->compatible("Vỏ case hỗ trợ bo mạch chủ {$label}.", $details);
        }

        return $this->incompatible(sprintf(
            'Vỏ case không hỗ trợ bo mạch chủ %s (chỉ hỗ trợ: %s).',
            $label,
            $this->labels->list('board_form_factor', $supported),
        ), $details);
    }
}
