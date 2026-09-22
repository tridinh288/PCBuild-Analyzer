<?php

namespace Tests\Feature\Services;

use App\Domain\Analysis\PerformanceAnalyzer;
use App\Domain\Analysis\PriceAnalyzer;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Enums\BuildPurpose;
use App\Enums\CompatibilityStatus;
use App\Models\Build;
use App\Services\BuildConfigurationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The container builds the engine with the registered rules, and the seeded templates
 * behave as the sample data plan says.
 */
class AnalysisWiringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_engine_is_resolved_with_the_registered_rules(): void
    {
        $engine = $this->app->make(CompatibilityEngine::class);

        $this->assertCount(13, $engine->rules());
        $this->assertSame($engine, $this->app->make(CompatibilityEngine::class));
    }

    public function test_performance_analyzer_has_a_strategy_for_every_purpose(): void
    {
        $config = $this->app->make(BuildConfigurationFactory::class)
            ->fromBuild(Build::where('slug', 'gaming-4k-cao-cap')->first());
        $analyzer = $this->app->make(PerformanceAnalyzer::class);

        foreach (BuildPurpose::cases() as $purpose) {
            $result = $analyzer->analyze($config, $purpose);

            $this->assertSame($purpose, $result->profile);
            $this->assertGreaterThan(0, $result->score);
        }
    }

    /**
     * D-032: the SQL total (list, sort, price filter) and the engine total (detail, analysis)
     * are computed in two places and must never disagree.
     */
    public function test_sql_total_price_matches_the_price_analyzer_for_every_template(): void
    {
        $factory = $this->app->make(BuildConfigurationFactory::class);
        $analyzer = $this->app->make(PriceAnalyzer::class);

        foreach (Build::query()->withTotalPrice()->get() as $build) {
            $this->assertSame(
                $analyzer->analyze($factory->fromBuild($build))->total,
                (int) $build->total_price,
                $build->slug,
            );
        }
    }

    public function test_the_psu_demo_template_has_exactly_one_warning(): void
    {
        $build = Build::where('slug', 'gaming-1440p-nguon-sat-gioi-han')->first();
        $report = $this->app->make(CompatibilityEngine::class)
            ->check($this->app->make(BuildConfigurationFactory::class)->fromBuild($build));

        $this->assertSame(CompatibilityStatus::Warning, $report->status());
        $this->assertSame(['psu_wattage'], array_map(fn ($r) => $r->rule, $report->problems()));
    }

    public function test_seeded_templates_have_no_incompatible_parts(): void
    {
        $engine = $this->app->make(CompatibilityEngine::class);
        $factory = $this->app->make(BuildConfigurationFactory::class);

        foreach (Build::all() as $build) {
            $report = $engine->check($factory->fromBuild($build));

            $this->assertNotSame(CompatibilityStatus::Incompatible, $report->status(), $build->slug.': '.json_encode(
                array_map(fn ($r) => $r->message, $report->problems()), JSON_UNESCAPED_UNICODE));
        }
    }
}
