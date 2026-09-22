<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\ValueInSet;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Hardware\EnumLabels;

final class CpuMotherboardSocketRule extends Rule
{
    public function __construct(private readonly EnumLabels $labels) {}

    public function key(): string
    {
        return 'cpu_motherboard_socket';
    }

    public function title(): string
    {
        return 'Socket CPU và bo mạch chủ';
    }

    public function involves(): array
    {
        return ['cpu', 'motherboard'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $cpuSocket = $config->cpu()->socket();
        $boardSocket = $config->motherboard()->socket();
        $details = ['cpu_socket' => $cpuSocket, 'motherboard_socket' => $boardSocket];

        if ((new ValueInSet([$boardSocket]))->isSatisfiedBy($cpuSocket)) {
            return $this->compatible("CPU và bo mạch chủ cùng socket {$this->labels->label('socket', $cpuSocket)}.", $details);
        }

        return $this->incompatible(sprintf(
            'CPU dùng socket %s nhưng bo mạch chủ dùng socket %s.',
            $this->labels->label('socket', $cpuSocket),
            $this->labels->label('socket', $boardSocket),
        ), $details);
    }
}
