<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\BuildController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompareController;
use App\Http\Controllers\Api\ComponentController;
use App\Support\Http\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (prefix /api) — design in docs/API.md
|--------------------------------------------------------------------------
| Public routes: stateless reads and stateless analysis, no login (D-001).
| Admin routes: Sanctum bearer tokens (D-023), added in Phase 6.
| Rate limiters are defined in AppServiceProvider (config/api.php).
*/

Route::get('/status', fn () => ApiResponse::success([
    'name' => config('app.name'),
]))->name('status');

Route::middleware('throttle:public')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category:slug}/filters', [CategoryController::class, 'filters'])->name('categories.filters');

    Route::get('/components', [ComponentController::class, 'index'])->name('components.index');
    Route::get('/components/{slug}', [ComponentController::class, 'show'])->name('components.show');

    Route::get('/builds', [BuildController::class, 'index'])->name('builds.index');
    Route::get('/builds/{slug}', [BuildController::class, 'show'])->name('builds.show');
});

// Endpoints that run the analysis engine
Route::middleware('throttle:analysis')->group(function () {
    Route::get('/builds/{slug}/analysis', [AnalysisController::class, 'show'])->name('builds.analysis');

    Route::post('/builder/options', [BuilderController::class, 'options'])->name('builder.options');
    Route::post('/builder/analyze', [BuilderController::class, 'analyze'])->name('builder.analyze');

    Route::post('/compare', CompareController::class)->name('compare');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});
