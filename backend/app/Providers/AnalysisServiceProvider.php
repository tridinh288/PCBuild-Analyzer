<?php

namespace App\Providers;

use App\Domain\Analysis\PowerCalculator;
use App\Domain\Compatibility\CompatibilityEngine;
use App\Domain\Compatibility\Rules\CpuMotherboardSocketRule;
use App\Domain\Compatibility\Rules\MotherboardCaseFormFactorRule;
use App\Domain\Compatibility\Rules\MotherboardRamCapacityRule;
use App\Domain\Compatibility\Rules\MotherboardRamTypeRule;
use App\Domain\Configuration\SlotRules;
use App\Domain\Hardware\EnumLabels;
use App\Domain\Hardware\HardwareFactory;
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
    ];

    public function register(): void
    {
        $this->app->singleton(HardwareFactory::class);

        $this->app->singleton(SlotRules::class, fn (Application $app) => new SlotRules($app->make(SpecSchema::class)->slots()));

        $this->app->singleton(EnumLabels::class, fn (Application $app) => new EnumLabels($app->make(SpecSchema::class)->enums()));

        $this->app->singleton(PowerCalculator::class, fn (Application $app) => new PowerCalculator($app->make(SpecSchema::class)->power()));

        // Rules are resolved by the container, so their own dependencies are auto-wired.
        $this->app->singleton(CompatibilityEngine::class, fn (Application $app) => new CompatibilityEngine(
            array_map(fn (string $rule) => $app->make($rule), self::RULES),
        ));
    }
}
