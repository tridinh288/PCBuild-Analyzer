<?php

namespace Tests\Unit\Domain\Analysis;

use App\Domain\Analysis\PerformanceAnalyzer;
use App\Domain\Analysis\Strategies\GamingStrategy;
use App\Domain\Analysis\Strategies\GeneralUseStrategy;
use App\Domain\Analysis\Strategies\ProgrammingStrategy;
use App\Domain\Analysis\Strategies\WorkstationStrategy;
use App\Domain\Analysis\SubScoreCalculator;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\BuildPurpose;
use Tests\Unit\Domain\DomainTestCase;

class PerformanceAnalysisTest extends DomainTestCase
{
    private function subScores(): SubScoreCalculator
    {
        return new SubScoreCalculator(self::hardwareConfig()['scoring']);
    }

    private function analyzer(): PerformanceAnalyzer
    {
        $scoring = self::hardwareConfig()['scoring'];
        $sub = $this->subScores();

        return new PerformanceAnalyzer([
            new GamingStrategy($sub, $scoring['weights']['gaming'], $scoring['adjustments']['gaming']),
            new ProgrammingStrategy($sub, $scoring['weights']['programming'], $scoring['adjustments']['programming']),
            new WorkstationStrategy($sub, $scoring['weights']['workstation'], $scoring['adjustments']['workstation']),
            new GeneralUseStrategy($sub, $scoring['weights']['general_use']),
        ]);
    }

    public function test_sub_scores_of_the_default_complete_configuration(): void
    {
        // CPU tier 60, GPU tier 60, RAM 32GB DDR5 = 85 + 10, NVMe 80 + 1TB bonus 10
        $this->assertSame(['cpu' => 60, 'gpu' => 60, 'ram' => 95, 'storage' => 90],
            $this->subScores()->all($this->completeConfiguration()));
    }

    public function test_gpu_sub_score_uses_integrated_graphics_or_is_missing(): void
    {
        $igpu = $this->configuration($this->component('cpu', ['has_integrated_graphics' => true]));
        $noDisplay = $this->configuration($this->component('cpu', ['has_integrated_graphics' => false]));

        $this->assertSame(15, $this->subScores()->gpu($igpu));
        $this->assertNull($this->subScores()->gpu($noDisplay));
    }

    public function test_ram_sub_score_counts_kits_scales_below_minimum_and_is_capped(): void
    {
        $ddr4 = fn (int $gb) => $this->component('ram', ['ram_type' => 'ddr4', 'capacity_gb' => $gb]);

        $this->assertSame(60, $this->subScores()->ram($this->configuration([$ddr4(8), 2])));  // 16 GB
        $this->assertSame(15, $this->subScores()->ram($this->configuration($ddr4(4))));       // half of the 8 GB score
        $this->assertSame(100, $this->subScores()->ram($this->configuration(
            [$this->component('ram', ['ram_type' => 'ddr5', 'capacity_gb' => 64]), 2])));      // 100 + 10 capped
    }

    public function test_storage_sub_score_uses_the_fastest_drive_and_total_capacity(): void
    {
        $config = $this->configuration(
            $this->component('storage', ['interface' => 'sata', 'form' => '3_5', 'capacity_gb' => 2000]),
            $this->component('storage', ['interface' => 'nvme', 'form' => 'm2', 'capacity_gb' => 500]),
        );

        $this->assertSame(100, $this->subScores()->storage($config)); // NVMe 80 + 2.5TB bonus 20
    }

    /**
     * Weights from config: gaming .30/.45/.15/.10, programming .40/.10/.30/.20,
     * workstation .40/.20/.30/.10, general use .25 each; sub-scores 60/60/95/90.
     */
    public function test_each_strategy_weights_the_same_build_differently(): void
    {
        $config = $this->completeConfiguration();
        $score = fn (BuildPurpose $profile) => $this->analyzer()->analyze($config, $profile)->score;

        $this->assertSame(68, $score(BuildPurpose::Gaming));        // 68.25
        $this->assertSame(77, $score(BuildPurpose::Programming));   // 76.5 rounds up
        $this->assertSame(74, $score(BuildPurpose::Workstation));   // 73.5 rounds up
        $this->assertSame(76, $score(BuildPurpose::GeneralUse));    // 76.25
    }

    public function test_general_use_is_the_default_profile_and_the_disclaimer_is_always_added(): void
    {
        $result = $this->analyzer()->analyze($this->completeConfiguration());

        $this->assertSame(BuildPurpose::GeneralUse, $result->profile);
        $this->assertContains(PerformanceAnalyzer::DISCLAIMER, $result->notes);
    }

    public function test_gaming_penalizes_a_large_cpu_gpu_gap(): void
    {
        $balanced = $this->configuration($this->component('cpu', ['performance_tier' => 60]), $this->component('gpu', ['performance_tier' => 80]));
        $bottleneck = $this->configuration($this->component('cpu', ['performance_tier' => 35]), $this->component('gpu', ['performance_tier' => 88]));

        $this->assertSame([], $this->analyzer()->analyze($balanced, BuildPurpose::Gaming)->adjustments);

        $result = $this->analyzer()->analyze($bottleneck, BuildPurpose::Gaming);
        $this->assertCount(1, $result->adjustments);
        $this->assertSame(10, $result->adjustments[0]['penalty']);
        $this->assertStringContainsString('CPU có thể gây nghẽn cổ chai', $result->adjustments[0]['reason']);

        // Same parts, other profile: no bottleneck penalty
        $this->assertSame([], $this->analyzer()->analyze($bottleneck, BuildPurpose::GeneralUse)->adjustments);
    }

    public function test_programming_and_workstation_penalize_low_ram_with_their_own_threshold(): void
    {
        $sixteen = $this->configuration($this->component('ram', ['capacity_gb' => 16]));

        $this->assertSame([], $this->analyzer()->analyze($sixteen, BuildPurpose::Programming)->adjustments);
        $this->assertCount(1, $this->analyzer()->analyze($sixteen, BuildPurpose::Workstation)->adjustments);

        $eight = $this->configuration($this->component('ram', ['capacity_gb' => 8]));
        $this->assertCount(1, $this->analyzer()->analyze($eight, BuildPurpose::Programming)->adjustments);
    }

    public function test_penalty_is_subtracted_from_the_score(): void
    {
        $config = $this->configuration($this->component('cpu', ['performance_tier' => 35]), $this->component('gpu', ['performance_tier' => 88]));

        $result = $this->analyzer()->analyze($config, BuildPurpose::Gaming);

        // .30×35 + .45×88 = 10.5 + 39.6 = 50.1 → 50, minus 10
        $this->assertSame(40, $result->score);
    }

    public function test_missing_parts_count_as_zero_and_are_listed(): void
    {
        $result = $this->analyzer()->analyze(BuildConfiguration::empty($this->slotRules()));

        $this->assertSame(0, $result->score);
        $this->assertSame(['cpu', 'gpu', 'ram', 'storage'], $result->missingParts());
    }
}
