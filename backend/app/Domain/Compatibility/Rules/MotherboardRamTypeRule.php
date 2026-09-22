<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\ValueInSet;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Hardware\EnumLabels;

final class MotherboardRamTypeRule extends Rule
{
    public function __construct(private readonly EnumLabels $labels) {}

    public function key(): string
    {
        return 'motherboard_ram_type';
    }

    public function title(): string
    {
        return 'Loại RAM';
    }

    public function involves(): array
    {
        return ['motherboard', 'ram'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $boardType = $config->motherboard()->ramType();
        $ramType = $config->ram()->ramType();
        $details = ['motherboard_ram_type' => $boardType, 'ram_type' => $ramType];

        if ((new ValueInSet([$boardType]))->isSatisfiedBy($ramType)) {
            return $this->compatible("RAM {$this->labels->label('ram_type', $ramType)} khớp với bo mạch chủ.", $details);
        }

        return $this->incompatible(sprintf(
            'Bo mạch chủ hỗ trợ %s, RAM này là %s.',
            $this->labels->label('ram_type', $boardType),
            $this->labels->label('ram_type', $ramType),
        ), $details);
    }
}
