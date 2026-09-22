<?php

namespace App\Providers;

use App\Repositories\Contracts\BuildRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\BuildRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Support\Hardware\SpecSchema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Interface => implementation. Services depend on the interfaces, so tests can swap them.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        BuildRepositoryInterface::class => BuildRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(SpecSchema::class, fn () => new SpecSchema(config('hardware')));
    }

    public function boot(): void
    {
        //
    }
}
