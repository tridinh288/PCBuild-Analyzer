<?php

namespace Tests\Unit\Domain\Compatibility;

use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Compatibility\Contracts\PresenceRule;
use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Domain\Compatibility\Rules\CpuMotherboardSocketRule;
use App\Domain\Compatibility\Rules\MotherboardRamCapacityRule;
use App\Domain\Compatibility\Rules\MotherboardRamTypeRule;
use App\Domain\Compatibility\Rules\Rule;
use App\Domain\Configuration\BuildConfiguration;
use App\Enums\CompatibilityStatus;
use Tests\Unit\Domain\DomainTestCase;

class CompatibilityEngineTest extends DomainTestCase
{
    private function engine(): CompatibilityEngine
    {
        return new CompatibilityEngine([
            new CpuMotherboardSocketRule($this->labels()),
            new MotherboardRamTypeRule($this->labels()),
            new MotherboardRamCapacityRule,
        ]);
    }

    public function test_empty_configuration_skips_every_rule_and_is_compatible(): void
    {
        $report = $this->engine()->check(BuildConfiguration::empty($this->slotRules()));

        $this->assertCount(3, $report->results);
        $this->assertSame([CompatibilityStatus::Skipped], array_values(array_unique(
            array_map(fn ($r) => $r->status, $report->results), SORT_REGULAR)));
        $this->assertSame(CompatibilityStatus::Compatible, $report->status());
        $this->assertSame(0, $report->errors());
    }

    public function test_report_counts_and_highlights_problem_slots(): void
    {
        $config = $this->configuration(
            $this->component('cpu', ['socket' => 'lga1700']),
            $this->component('motherboard', ['socket' => 'am5', 'ram_type' => 'ddr5']),
            $this->component('ram', ['ram_type' => 'ddr5']),
        );

        $report = $this->engine()->check($config);

        $this->assertSame(CompatibilityStatus::Incompatible, $report->status());
        $this->assertSame(1, $report->errors());
        $this->assertSame(['cpu' => ['cpu_motherboard_socket'], 'motherboard' => ['cpu_motherboard_socket']],
            $report->problemsByCategory());
    }

    public function test_result_does_not_depend_on_selection_order(): void
    {
        $cpu = $this->component('cpu', ['socket' => 'lga1700']);
        $board = $this->component('motherboard', ['socket' => 'am5']);

        $first = $this->engine()->check($this->configuration($cpu, $board))->toArray();
        $second = $this->engine()->check($this->configuration($board, $cpu))->toArray();

        $this->assertEquals($first, $second);
    }

    public function test_candidate_ram_is_not_blamed_for_an_existing_cpu_motherboard_mismatch(): void
    {
        $current = $this->configuration(
            $this->component('cpu', ['socket' => 'lga1700']),
            $this->component('motherboard', ['socket' => 'am5', 'ram_type' => 'ddr5']),
        );
        $candidate = $this->component('ram', ['ram_type' => 'ddr5']);

        $report = $this->engine()->checkCandidate($current->with($candidate), 'ram');

        $this->assertSame(['motherboard_ram_type', 'motherboard_ram_capacity'], array_map(fn ($r) => $r->rule, $report->results));
        $this->assertSame(CompatibilityStatus::Compatible, $report->status());
    }

    public function test_candidate_with_wrong_ram_type_is_incompatible(): void
    {
        $current = $this->configuration($this->component('motherboard', ['ram_type' => 'ddr5']));

        $report = $this->engine()->checkCandidate($current->with($this->component('ram', ['ram_type' => 'ddr4'])), 'ram');

        $this->assertSame(CompatibilityStatus::Incompatible, $report->status());
    }

    public function test_presence_rules_run_in_full_check_but_not_for_candidates(): void
    {
        $presence = new class extends Rule implements PresenceRule
        {
            public function key(): string
            {
                return 'needs_gpu';
            }

            public function title(): string
            {
                return 'Test';
            }

            public function involves(): array
            {
                return ['cpu', 'gpu'];
            }

            public function appliesTo(BuildConfiguration $config): bool
            {
                return $config->has('cpu');
            }

            public function check(BuildConfiguration $config): CompatibilityResult
            {
                return $this->incompatible('Missing GPU.');
            }
        };
        $engine = new CompatibilityEngine([$presence]);
        $config = $this->configuration($this->component('cpu'));

        $this->assertSame(1, $engine->check($config)->errors());
        $this->assertSame([], $engine->checkCandidate($config, 'cpu')->results);
    }
}
