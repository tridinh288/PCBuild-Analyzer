<?php

namespace Tests\Unit\Domain\Analysis;

use App\Domain\Analysis\PowerCalculator;
use App\Domain\Configuration\BuildConfiguration;
use Tests\Unit\Domain\DomainTestCase;

class PowerCalculatorTest extends DomainTestCase
{
    private function calculator(): PowerCalculator
    {
        return new PowerCalculator(self::hardwareConfig()['power']);
    }

    public function test_estimate_adds_every_component_and_the_platform_constants(): void
    {
        $config = $this->configuration(
            $this->component('cpu', ['tdp' => 65]),
            $this->component('gpu', ['tdp' => 245]),
            [$this->component('ram', ['modules' => 2]), 1],
            $this->component('storage', ['interface' => 'nvme', 'form' => 'm2']),
            [$this->component('storage', ['interface' => 'sata', 'form' => '2_5']), 2],
            $this->component('psu', ['wattage' => 450]),
        );

        $result = $this->calculator()->calculate($config);

        // 65 + 245 + 2×5 + 7 + 2×5 + 50 + 15
        $this->assertSame(402, $result->estimatedWatts);
        $this->assertSame(['cpu' => 65, 'gpu' => 245, 'ram' => 10, 'storage' => 17, 'motherboard' => 50, 'fans' => 15],
            $result->breakdown);
        $this->assertSame(450, $result->selectedPsuWatts);
    }

    public function test_recommended_psu_applies_the_safety_factor_and_rounds_up_to_a_common_size(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(550, $calculator->recommendedPsu(392));   // 490 → 550
        $this->assertSame(450, $calculator->recommendedPsu(360));   // exactly 450 → 450
        $this->assertSame(550, $calculator->recommendedPsu(361));   // 451.25 → 550
        $this->assertSame(1000, $calculator->recommendedPsu(700));  // 875 → 1000
    }

    public function test_recommendation_beyond_the_largest_step_rounds_to_100_watts(): void
    {
        $this->assertSame(1700, $this->calculator()->recommendedPsu(1300)); // 1625 → 1700
    }

    public function test_ram_power_counts_every_module_of_every_kit(): void
    {
        $result = $this->calculator()->calculate($this->configuration([$this->component('ram', ['modules' => 2]), 2]));

        $this->assertSame(20, $result->breakdown['ram']);
    }

    public function test_cpu_without_gpu_has_no_gpu_power(): void
    {
        $result = $this->calculator()->calculate($this->configuration($this->component('cpu', ['tdp' => 65])));

        $this->assertArrayNotHasKey('gpu', $result->breakdown);
        $this->assertSame(130, $result->estimatedWatts);
        $this->assertNull($result->selectedPsuWatts);
    }

    public function test_empty_configuration_needs_no_power(): void
    {
        $result = $this->calculator()->calculate(BuildConfiguration::empty($this->slotRules()));

        $this->assertSame(0, $result->estimatedWatts);
        $this->assertSame(0, $result->recommendedPsuWatts);
    }
}
