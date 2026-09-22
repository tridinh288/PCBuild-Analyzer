<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\FitsWithin;
use App\Domain\Configuration\BuildConfiguration;

final class GpuCaseClearanceRule extends Rule
{
    public function key(): string
    {
        return 'gpu_case_clearance';
    }

    public function title(): string
    {
        return 'Chiều dài card đồ họa';
    }

    public function involves(): array
    {
        return ['gpu', 'case'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $length = $config->gpu()->lengthMm();
        $limit = $config->pcCase()->maxGpuLengthMm();
        $details = ['gpu_length_mm' => $length, 'case_max_gpu_length_mm' => $limit];

        if ((new FitsWithin($limit))->isSatisfiedBy($length)) {
            return $this->compatible("Card đồ họa dài {$length}mm, vừa vỏ case (tối đa {$limit}mm).", $details);
        }

        return $this->incompatible("Card đồ họa dài {$length}mm, vượt giới hạn {$limit}mm của vỏ case.", $details);
    }
}
