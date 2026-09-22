<?php

namespace Tests\Unit\Domain;

use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Compatibility\Contracts\CompatibilityRule;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Configuration\BuildConfiguration;
use App\Domain\Configuration\SlotRules;
use App\Domain\Hardware\Components\HardwareComponent;
use App\Domain\Hardware\EnumLabels;
use App\Domain\Hardware\HardwareFactory;
use App\Enums\CompatibilityStatus;
use PHPUnit\Framework\TestCase;

/**
 * Base for Domain unit tests: plain PHPUnit, no Laravel boot, no database (D-027, D-030).
 * Provides valid default specs per category; tests override only what they check.
 */
abstract class DomainTestCase extends TestCase
{
    private const DEFAULT_SPECS = [
        'cpu' => ['socket' => 'am5', 'cores' => 6, 'threads' => 12, 'tdp' => 65,
            'has_integrated_graphics' => true, 'includes_cooler' => true, 'performance_tier' => 60],
        'motherboard' => ['socket' => 'am5', 'ram_type' => 'ddr5', 'ram_slots' => 4, 'max_ram_gb' => 192,
            'form_factor' => 'atx', 'm2_slots' => 2, 'sata_ports' => 4],
        'ram' => ['ram_type' => 'ddr5', 'capacity_gb' => 32, 'modules' => 2, 'speed_mhz' => 6000],
        'gpu' => ['vram_gb' => 8, 'length_mm' => 250, 'tdp' => 150, 'performance_tier' => 60],
        'storage' => ['interface' => 'nvme', 'form' => 'm2', 'capacity_gb' => 1000],
        'psu' => ['wattage' => 750, 'form_factor' => 'atx', 'efficiency_rating' => 'gold'],
        'case' => ['supported_form_factors' => ['atx', 'matx', 'itx'], 'max_gpu_length_mm' => 350,
            'max_cooler_height_mm' => 165, 'supported_psu_form_factors' => ['atx']],
        'cooler' => ['type' => 'air', 'supported_sockets' => ['am4', 'am5', 'lga1700'], 'max_tdp' => 200,
            'height_mm' => 155],
    ];

    private static int $nextId = 1;

    /**
     * @return array<string, mixed>
     */
    protected static function hardwareConfig(): array
    {
        return require __DIR__.'/../../../config/hardware.php';
    }

    protected function labels(): EnumLabels
    {
        return new EnumLabels(self::hardwareConfig()['enums']);
    }

    /**
     * Runs one rule through the engine (so "skipped" is covered too) and checks its status.
     */
    protected function assertRuleStatus(
        CompatibilityStatus $expected,
        CompatibilityRule $rule,
        BuildConfiguration $config,
    ): CompatibilityResult {
        $result = (new CompatibilityEngine([$rule]))->check($config)->results[0];

        $this->assertSame($expected, $result->status, "{$rule->key()}: {$result->message}");

        return $result;
    }

    protected function slotRules(): SlotRules
    {
        return new SlotRules(self::hardwareConfig()['slots']);
    }

    /**
     * A configuration from components; pass [component, quantity] for more than one unit.
     *
     * @param  HardwareComponent|array{0: HardwareComponent, 1: int}  ...$parts
     */
    protected function configuration(HardwareComponent|array ...$parts): BuildConfiguration
    {
        $config = BuildConfiguration::empty($this->slotRules());

        foreach ($parts as $part) {
            $config = is_array($part) ? $config->with($part[0], $part[1]) : $config->with($part);
        }

        return $config;
    }

    /**
     * A complete, compatible configuration built from the default specs (GPU and cooler included).
     */
    protected function completeConfiguration(): BuildConfiguration
    {
        return $this->configuration(...array_map(fn (string $category) => $this->component($category),
            array_keys(self::DEFAULT_SPECS)));
    }

    /**
     * @param  array<string, mixed>  $specs  Overrides merged into the category defaults.
     */
    protected function component(string $category, array $specs = [], int $price = 1_000_000, ?int $id = null): HardwareComponent
    {
        $id ??= self::$nextId++;

        return (new HardwareFactory)->make($category, [
            'id' => $id,
            'name' => "{$category} #{$id}",
            'price' => $price,
            'specs' => [...self::DEFAULT_SPECS[$category], ...$specs],
        ]);
    }
}
