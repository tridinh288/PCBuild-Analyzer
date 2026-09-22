<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\FitsWithin;
use App\Domain\Configuration\BuildConfiguration;

/**
 * Air cooler height must fit the case. AIO radiator size vs case is a future rule.
 */
final class CoolerCaseHeightRule extends Rule
{
    public function key(): string
    {
        return 'cooler_case_height';
    }

    public function title(): string
    {
        return 'Chiều cao tản nhiệt';
    }

    public function involves(): array
    {
        return ['cooler', 'case'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $cooler = $config->cooler();

        if (! $cooler->isAir()) {
            return $this->compatible('Tản nước AIO: không áp dụng giới hạn chiều cao tản khí.');
        }

        $height = $cooler->heightMm();
        $limit = $config->pcCase()->maxCoolerHeightMm();
        $details = ['cooler_height_mm' => $height, 'case_max_cooler_height_mm' => $limit];

        if ((new FitsWithin($limit))->isSatisfiedBy($height)) {
            return $this->compatible("Tản nhiệt cao {$height}mm, vừa vỏ case (tối đa {$limit}mm).", $details);
        }

        return $this->incompatible("Tản nhiệt cao {$height}mm, vượt giới hạn {$limit}mm của vỏ case.", $details);
    }
}
