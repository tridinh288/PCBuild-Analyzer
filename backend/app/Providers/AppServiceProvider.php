<?php

namespace App\Providers;

use App\Repositories\Contracts\BuildRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\BuildRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Support\Hardware\SpecSchema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Named limiters used by routes/api.php; limits in config/api.php.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(config('api.rate_limits.public'))
            ->by($request->ip()));

        RateLimiter::for('analysis', fn (Request $request) => Limit::perMinute(config('api.rate_limits.analysis'))
            ->by($request->ip()));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(config('api.rate_limits.login'))
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
