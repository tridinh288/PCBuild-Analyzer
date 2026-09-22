<?php

namespace App\Providers;

use App\Domain\Analysis\PerformanceAnalyzer;
use App\Domain\Analysis\PowerCalculator;
use App\Domain\Analysis\Strategies\GamingStrategy;
use App\Domain\Analysis\Strategies\GeneralUseStrategy;
use App\Domain\Analysis\Strategies\ProgrammingStrategy;
use App\Domain\Analysis\Strategies\WeightedStrategy;
use App\Domain\Analysis\Strategies\WorkstationStrategy;
use App\Domain\Analysis\SubScoreCalculator;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Compatibility\Rules\CoolerCaseHeightRule;
use App\Domain\Compatibility\Rules\CoolerCpuSocketRule;
use App\Domain\Compatibility\Rules\CoolerCpuTdpRule;
use App\Domain\Compatibility\Rules\CpuCoolingRule;
use App\Domain\Compatibility\Rules\CpuMotherboardSocketRule;
use App\Domain\Compatibility\Rules\DisplayOutputRule;
use App\Domain\Compatibility\Rules\GpuCaseClearanceRule;
use App\Domain\Compatibility\Rules\MotherboardCaseFormFactorRule;
use App\Domain\Compatibility\Rules\MotherboardRamCapacityRule;
use App\Domain\Compatibility\Rules\MotherboardRamTypeRule;
use App\Domain\Compatibility\Rules\MotherboardStorageSlotsRule;
use App\Domain\Compatibility\Rules\PsuCaseFormFactorRule;
use App\Domain\Compatibility\Rules\PsuWattageRule;
use App\Domain\Configuration\SlotRules;
use App\Domain\Hardware\EnumLabels;
use App\Domain\Hardware\HardwareFactory;
use App\Enums\BuildPurpose;
use App\Support\Hardware\SpecSchema;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the analysis engine. Domain classes never call config(); their configuration
 * values are passed in here (D-030).
 */
class AnalysisServiceProvider extends ServiceProvider
{
    /**
     * Compatibility rules, in report order. Adding a rule = adding a class and a line here;
     * the engine and the controllers do not change (Open/Closed).
     *
     * @var list<class-string>
     */
    private const RULES = [
        CpuMotherboardSocketRule::class,
        MotherboardRamTypeRule::class,
        MotherboardRamCapacityRule::class,
        MotherboardCaseFormFactorRule::class,
        MotherboardStorageSlotsRule::class,
        GpuCaseClearanceRule::class,
        CoolerCpuSocketRule::class,
        CoolerCpuTdpRule::class,
        CoolerCaseHeightRule::class,
        PsuCaseFormFactorRule::class,
        PsuWattageRule::class,
        DisplayOutputRule::class,
        CpuCoolingRule::class,
    ];

    /**
     * @var array<string, class-string<WeightedStrategy>>
     */
    private const STRATEGIES = [
        'gaming' => GamingStrategy::class,
        'programming' => ProgrammingStrategy::class,
        'workstation' => WorkstationStrategy::class,
        'general_use' => GeneralUseStrategy::class,
    ];

    public function register(): void
    {
        $this->app->singleton(HardwareFactory::class);

        $this->app->singleton(SlotRules::class, fn (Application $app) => new SlotRules($app->make(SpecSchema::class)->slots()));

        $this->app->singleton(EnumLabels::class, fn (Application $app) => new EnumLabels($app->make(SpecSchema::class)->enums()));

        $this->app->singleton(PowerCalculator::class, fn (Application $app) => new PowerCalculator($app->make(SpecSchema::class)->power()));

        $this->app->singleton(SubScoreCalculator::class, fn (Application $app) => new SubScoreCalculator($app->make(SpecSchema::class)->scoring()));

        // One strategy per purpose; weights and adjustments come from config (D-031).
        $this->app->singleton(PerformanceAnalyzer::class, function (Application $app) {
            $scoring = $app->make(SpecSchema::class)->scoring();
            $subScores = $app->make(SubScoreCalculator::class);

            return new PerformanceAnalyzer(array_map(
                fn (BuildPurpose $purpose) => new (self::STRATEGIES[$purpose->value])(
                    $subScores,
                    $scoring['weights'][$purpose->value],
                    $scoring['adjustments'][$purpose->value] ?? [],
                ),
                BuildPurpose::cases(),
            ));
        });

        // Rules are resolved by the container, so their own dependencies are auto-wired.
        $this->app->singleton(CompatibilityEngine::class, fn (Application $app) => new CompatibilityEngine(
            array_map(fn (string $rule) => $app->make($rule), self::RULES),
        ));
    }
}
