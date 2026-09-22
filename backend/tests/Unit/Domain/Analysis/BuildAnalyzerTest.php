<?php

namespace Tests\Unit\Domain\Analysis;

use App\Domain\Analysis\BuildAnalyzer;
use App\Domain\Analysis\PerformanceAnalyzer;
use App\Domain\Analysis\PowerCalculator;
use App\Domain\Analysis\PriceAnalyzer;
use App\Domain\Analysis\Strategies\GamingStrategy;
use App\Domain\Analysis\Strategies\GeneralUseStrategy;
use App\Domain\Analysis\SubScoreCalculator;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Compatibility\Rules\CpuMotherboardSocketRule;
use App\Enums\BuildPurpose;
use App\Enums\CompatibilityStatus;
use Tests\Unit\Domain\DomainTestCase;

class BuildAnalyzerTest extends DomainTestCase
{
    private function analyzer(): BuildAnalyzer
    {
        $config = self::hardwareConfig();
        $sub = new SubScoreCalculator($config['scoring']);

        return new BuildAnalyzer(
            new CompatibilityEngine([new CpuMotherboardSocketRule($this->labels())]),
            new PowerCalculator($config['power']),
            new PriceAnalyzer,
            new PerformanceAnalyzer([
                new GamingStrategy($sub, $config['scoring']['weights']['gaming'], $config['scoring']['adjustments']['gaming']),
                new GeneralUseStrategy($sub, $config['scoring']['weights']['general_use']),
            ]),
        );
    }

    public function test_complete_compatible_build_has_every_part_of_the_analysis(): void
    {
        $analysis = $this->analyzer()->analyze($this->completeConfiguration(), BuildPurpose::Gaming);

        $this->assertSame(CompatibilityStatus::Compatible, $analysis->compatibility->status());
        $this->assertGreaterThan(0, $analysis->power->estimatedWatts);
        $this->assertSame(8_000_000, $analysis->price->total);
        $this->assertSame(BuildPurpose::Gaming, $analysis->performance->profile);
        $this->assertTrue($analysis->isComplete());
        $this->assertSame([PerformanceAnalyzer::DISCLAIMER], $analysis->performance->notes);
    }

    public function test_incompatible_build_is_still_fully_analyzed_with_a_note(): void
    {
        $config = $this->completeConfiguration()->with($this->component('cpu', ['socket' => 'lga1700']));

        $analysis = $this->analyzer()->analyze($config);

        $this->assertSame(1, $analysis->compatibility->errors());
        $this->assertGreaterThan(0, $analysis->performance->score);
        $this->assertGreaterThan(0, $analysis->price->total);
        $this->assertContains(BuildAnalyzer::INCOMPATIBLE_NOTE, $analysis->performance->notes);
    }

    public function test_incomplete_build_lists_missing_slots_with_a_note(): void
    {
        $analysis = $this->analyzer()->analyze($this->configuration($this->component('cpu')));

        $this->assertSame(['motherboard', 'ram', 'storage', 'psu', 'case'], $analysis->missingSlots);
        $this->assertFalse($analysis->isComplete());
        $this->assertContains(BuildAnalyzer::INCOMPLETE_NOTE, $analysis->performance->notes);
        $this->assertSame(['motherboard', 'ram', 'storage', 'psu', 'case'], $analysis->toArray()['missing_slots']);
    }
}
