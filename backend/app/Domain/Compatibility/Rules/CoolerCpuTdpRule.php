<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\AtLeast;
use App\Domain\Configuration\BuildConfiguration;

/**
 * A cooler rated below the CPU TDP still mounts and runs, so this is a warning, not an error.
 */
final class CoolerCpuTdpRule extends Rule
{
    public function key(): string
    {
        return 'cooler_cpu_tdp';
    }

    public function title(): string
    {
        return 'Khả năng tản nhiệt';
    }

    public function involves(): array
    {
        return ['cooler', 'cpu'];
    }

    protected function blames(): array
    {
        return ['cooler'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $cpuTdp = $config->cpu()->tdp();
        $coolerTdp = $config->cooler()->maxTdp();
        $details = ['cpu_tdp' => $cpuTdp, 'cooler_max_tdp' => $coolerTdp];

        if ((new AtLeast($cpuTdp))->isSatisfiedBy($coolerTdp)) {
            return $this->compatible("Tản nhiệt chịu được {$coolerTdp}W, đủ cho CPU {$cpuTdp}W.", $details);
        }

        return $this->warning("Tản nhiệt chỉ chịu được {$coolerTdp}W, thấp hơn TDP {$cpuTdp}W của CPU; CPU có thể bị giảm xung khi tải nặng.", $details);
    }
}
