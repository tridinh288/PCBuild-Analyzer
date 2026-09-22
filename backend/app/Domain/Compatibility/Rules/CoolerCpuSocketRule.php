<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\ValueInSet;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Hardware\EnumLabels;

final class CoolerCpuSocketRule extends Rule
{
    public function __construct(private readonly EnumLabels $labels) {}

    public function key(): string
    {
        return 'cooler_cpu_socket';
    }

    public function title(): string
    {
        return 'Socket tản nhiệt';
    }

    public function involves(): array
    {
        return ['cooler', 'cpu'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $socket = $config->cpu()->socket();
        $supported = $config->cooler()->supportedSockets();
        $details = ['cpu_socket' => $socket, 'cooler_supported_sockets' => $supported];
        $label = $this->labels->label('socket', $socket);

        if ((new ValueInSet($supported))->isSatisfiedBy($socket)) {
            return $this->compatible("Tản nhiệt hỗ trợ socket {$label}.", $details);
        }

        return $this->incompatible(sprintf(
            'Tản nhiệt không hỗ trợ socket %s (chỉ hỗ trợ: %s).',
            $label,
            $this->labels->list('socket', $supported),
        ), $details);
    }
}
