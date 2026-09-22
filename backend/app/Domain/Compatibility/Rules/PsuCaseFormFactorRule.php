<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\ValueInSet;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Hardware\EnumLabels;

final class PsuCaseFormFactorRule extends Rule
{
    public function __construct(private readonly EnumLabels $labels) {}

    public function key(): string
    {
        return 'psu_case_form_factor';
    }

    public function title(): string
    {
        return 'Kích thước nguồn và vỏ case';
    }

    public function involves(): array
    {
        return ['psu', 'case'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $formFactor = $config->psu()->formFactor();
        $supported = $config->pcCase()->supportedPsuFormFactors();
        $details = ['psu_form_factor' => $formFactor, 'case_supported_psu_form_factors' => $supported];
        $label = $this->labels->label('psu_form_factor', $formFactor);

        if ((new ValueInSet($supported))->isSatisfiedBy($formFactor)) {
            return $this->compatible("Vỏ case hỗ trợ nguồn {$label}.", $details);
        }

        return $this->incompatible(sprintf(
            'Vỏ case không hỗ trợ nguồn %s (chỉ hỗ trợ: %s).',
            $label,
            $this->labels->list('psu_form_factor', $supported),
        ), $details);
    }
}
