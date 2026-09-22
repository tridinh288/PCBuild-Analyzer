<?php

namespace App\Providers;

use App\Domain\Configuration\SlotRules;
use App\Domain\Hardware\HardwareFactory;
use App\Support\Hardware\SpecSchema;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the analysis engine. Domain classes never call config(); their configuration
 * values are passed in here (D-030).
 */
class AnalysisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HardwareFactory::class);

        $this->app->singleton(SlotRules::class, fn ($app) => new SlotRules($app->make(SpecSchema::class)->slots()));
    }
}
