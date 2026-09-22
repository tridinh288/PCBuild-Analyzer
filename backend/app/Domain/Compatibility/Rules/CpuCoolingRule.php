<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Contracts\PresenceRule;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;

/**
 * A CPU sold without a cooler needs one. Warning, because the parts are not in conflict;
 * the build is just not ready to run. PresenceRule (D-035).
 */
final class CpuCoolingRule extends Rule implements PresenceRule
{
    public function key(): string
    {
        return 'cpu_cooling';
    }

    public function title(): string
    {
        return 'Tản nhiệt CPU';
    }

    public function involves(): array
    {
        return ['cpu', 'cooler'];
    }

    public function appliesTo(BuildConfiguration $config): bool
    {
        return $config->has('cpu');
    }

    protected function blames(): array
    {
        return ['cooler'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $includesCooler = $config->cpu()->includesCooler();
        $details = ['cpu_includes_cooler' => $includesCooler, 'has_cooler' => $config->has('cooler')];

        if ($config->has('cooler')) {
            return $this->compatible('Đã chọn tản nhiệt cho CPU.', $details);
        }

        if ($includesCooler) {
            return $this->compatible('Dùng tản nhiệt đi kèm CPU.', $details);
        }

        return $this->warning('CPU không kèm tản nhiệt, cần chọn thêm tản nhiệt CPU.', $details);
    }
}
