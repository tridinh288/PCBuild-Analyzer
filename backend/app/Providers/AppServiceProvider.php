<?php

namespace App\Providers;

use App\Support\Hardware\SpecSchema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SpecSchema::class, fn () => new SpecSchema(config('hardware')));
    }

    public function boot(): void
    {
        //
    }
}
