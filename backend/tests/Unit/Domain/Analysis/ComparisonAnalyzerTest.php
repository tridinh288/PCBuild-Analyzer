<?php

namespace Tests\Unit\Domain\Analysis;

use App\Domain\Analysis\BuildAnalyzer;
use App\Domain\Analysis\ComparisonAnalyzer;
use App\Domain\Analysis\PerformanceAnalyzer;
use App\Domain\Analysis\PowerCalculator;
use App\Domain\Analysis\PriceAnalyzer;
use App\Domain\Analysis\Strategies\GeneralUseStrategy;
use App\Domain\Analysis\SubScoreCalculator;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Configuration\BuildConfiguration;
use Tests\Unit\Domain\DomainTestCase;

class ComparisonAnalyzerTest extends DomainTestCase
{
    /**
     * @return array{label: string, configuration: BuildConfiguration, analysis: mixed}
     */
    private function entry(string $label, BuildConfiguration $config): array
    {
        $scoring = self::hardwareConfig()['scoring'];
        $analyzer = new BuildAnalyzer(
            new CompatibilityEngine([]),
            new PowerCalculator(self::hardwareConfig()['power']),
            new PriceAnalyzer,
            new PerformanceAnalyzer([new GeneralUseStrategy(new SubScoreCalculator($scoring), $scoring['weights']['general_use'])]),
        );

        return ['label' => $label, 'configuration' => $config, 'analysis' => $analyzer->analyze($config)];
    }

    public function test_detects_changed_slots_and_metric_deltas(): void
    {
        $board = $this->component('motherboard', price: 4_000_000);
        $ram = $this->component('ram', ['capacity_gb' => 16], price: 1_500_000);
        $cpu5 = $this->component('cpu', ['tdp' => 65, 'performance_tier' => 62], price: 5_000_000);
        $cpu7 = $this->component('cpu', ['tdp' => 120, 'performance_tier' => 85], price: 8_000_000);

        $result = (new ComparisonAnalyzer)->compare([
            $this->entry('A', $this->configuration($cpu5, $board, $ram)),
            $this->entry('B', $this->configuration($cpu7, $board, [$ram, 2])),
        ]);

        $changes = $result->differences[0]['changes'];
        $slotChanges = array_values(array_filter($changes, fn ($c) => $c['type'] === 'slot'));
        $metricChanges = array_column(array_filter($changes, fn ($c) => $c['type'] === 'metric'), null, 'metric');

        $this->assertSame(['cpu', 'ram'], array_column($slotChanges, 'category'));
        $this->assertSame([$cpu5->name()], $slotChanges[0]['from']);
        $this->assertSame([$cpu7->name()], $slotChanges[0]['to']);
        $this->assertSame(["{$ram->name()} × 2"], $slotChanges[1]['to']);

        $this->assertSame(55 + 10, $metricChanges['estimated_watts']['delta']); // +55 W CPU, +2 modules × 5 W
        $this->assertSame(4_500_000, $metricChanges['total_price']['delta']);
        $this->assertArrayHasKey('score', $metricChanges);
    }

    public function test_identical_configurations_have_no_differences(): void
    {
        $config = $this->completeConfiguration();

        $result = (new ComparisonAnalyzer)->compare([$this->entry('A', $config), $this->entry('B', $config)]);

        $this->assertSame([], $result->differences[0]['changes']);
    }

    public function test_three_configurations_give_two_neighbour_comparisons_and_no_winner(): void
    {
        $config = $this->completeConfiguration();

        $result = (new ComparisonAnalyzer)->compare([
            $this->entry('A', $config), $this->entry('B', $config), $this->entry('C', $config),
        ]);

        $this->assertSame([[0, 1], [1, 2]], array_map(fn ($d) => [$d['from'], $d['to']], $result->differences));
        $this->assertSame(['A', 'B', 'C'], array_column($result->configurations, 'label'));
        $this->assertStringNotContainsString('winner', json_encode($result->toArray()));
        $this->assertCount(8, $result->slots);
    }
}
