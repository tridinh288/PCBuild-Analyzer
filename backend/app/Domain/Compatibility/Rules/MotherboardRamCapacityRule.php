<?php

namespace App\Domain\Compatibility\Rules;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Specifications\FitsWithin;
use App\Domain\Configuration\BuildConfiguration;

/**
 * Total RAM modules must fit the slots and total capacity must not exceed the board maximum.
 * RAM quantity counts kits: total modules = modules per kit × kits.
 */
final class MotherboardRamCapacityRule extends Rule
{
    public function key(): string
    {
        return 'motherboard_ram_capacity';
    }

    public function title(): string
    {
        return 'Số khe và dung lượng RAM';
    }

    public function involves(): array
    {
        return ['motherboard', 'ram'];
    }

    public function check(BuildConfiguration $config): CompatibilityResult
    {
        $board = $config->motherboard();
        $ram = $config->ram();
        $kits = $config->quantityOf('ram');

        $modules = $ram->totalModules($kits);
        $capacity = $ram->totalCapacityGb($kits);
        $details = [
            'modules' => $modules,
            'ram_slots' => $board->ramSlots(),
            'capacity_gb' => $capacity,
            'max_ram_gb' => $board->maxRamGb(),
        ];

        $problems = [];

        if (! (new FitsWithin($board->ramSlots()))->isSatisfiedBy($modules)) {
            $problems[] = "Cần {$modules} khe RAM nhưng bo mạch chủ chỉ có {$board->ramSlots()} khe.";
        }

        if (! (new FitsWithin($board->maxRamGb()))->isSatisfiedBy($capacity)) {
            $problems[] = "Tổng dung lượng {$capacity}GB vượt mức tối đa {$board->maxRamGb()}GB của bo mạch chủ.";
        }

        if ($problems !== []) {
            return $this->incompatible(implode(' ', $problems), $details);
        }

        return $this->compatible("{$modules} thanh RAM, tổng {$capacity}GB, nằm trong giới hạn của bo mạch chủ.", $details);
    }
}
