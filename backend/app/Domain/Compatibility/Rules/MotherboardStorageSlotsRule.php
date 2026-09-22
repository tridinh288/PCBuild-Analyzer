<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\FitsWithin;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\ConfigurationItem;
use Closure;

/**
 * M.2 drives must fit the M.2 slots and SATA drives the SATA ports.
 */
final class MotherboardStorageSlotsRule extends Rule
{
    public function key(): string
    {
        return 'motherboard_storage_slots';
    }

    public function title(): string
    {
        return 'Khe cắm ổ cứng';
    }

    public function involves(): array
    {
        return ['motherboard', 'storage'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $board = $config->motherboard();
        $items = $config->items('storage');

        $m2Drives = $this->count($items, fn ($drive) => $drive->isM2());
        $sataDrives = $this->count($items, fn ($drive) => $drive->isSata());
        $details = [
            'm2_drives' => $m2Drives, 'm2_slots' => $board->m2Slots(),
            'sata_drives' => $sataDrives, 'sata_ports' => $board->sataPorts(),
        ];

        $problems = [];

        if (! (new FitsWithin($board->m2Slots()))->isSatisfiedBy($m2Drives)) {
            $problems[] = "Cần {$m2Drives} khe M.2 nhưng bo mạch chủ chỉ có {$board->m2Slots()} khe.";
        }

        if (! (new FitsWithin($board->sataPorts()))->isSatisfiedBy($sataDrives)) {
            $problems[] = "Cần {$sataDrives} cổng SATA nhưng bo mạch chủ chỉ có {$board->sataPorts()} cổng.";
        }

        if ($problems !== []) {
            return $this->incompatible(implode(' ', $problems), $details);
        }

        return $this->compatible('Bo mạch chủ đủ khe cắm cho các ổ cứng đã chọn.', $details);
    }

    /**
     * @param  list<ConfigurationItem>  $items
     */
    private function count(array $items, Closure $matches): int
    {
        return array_sum(array_map(fn (ConfigurationItem $item) => $matches($item->component) ? $item->quantity : 0, $items));
    }
}
