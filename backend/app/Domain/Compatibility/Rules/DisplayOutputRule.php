<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Contracts\PresenceRule;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;

/**
 * A CPU without integrated graphics needs a graphics card, or the PC has no display output.
 * Reports a missing part, so it is a PresenceRule (D-035).
 */
final class DisplayOutputRule extends Rule implements PresenceRule
{
    public function key(): string
    {
        return 'display_output';
    }

    public function title(): string
    {
        return 'Xuất hình';
    }

    public function involves(): array
    {
        return ['cpu', 'gpu'];
    }

    public function appliesTo(BuildConfiguration $config): bool
    {
        return $config->has('cpu');
    }

    protected function blames(): array
    {
        return ['gpu'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $hasIgpu = $config->cpu()->hasIntegratedGraphics();
        $details = ['cpu_has_integrated_graphics' => $hasIgpu, 'has_gpu' => $config->has('gpu')];

        if ($config->has('gpu')) {
            return $this->compatible('Xuất hình qua card đồ họa rời.', $details);
        }

        if ($hasIgpu) {
            return $this->compatible('Xuất hình qua đồ họa tích hợp của CPU.', $details);
        }

        return $this->incompatible('CPU không có đồ họa tích hợp, cần chọn card đồ họa rời để có tín hiệu hình ảnh.', $details);
    }
}
