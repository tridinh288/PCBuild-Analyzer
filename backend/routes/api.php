<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\BuildController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComponentController;
use App\Support\Http\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (prefix /api) — design in docs/API.md
|--------------------------------------------------------------------------
| Public routes: stateless reads and stateless analysis, no login (D-001).
| Admin routes: Sanctum bearer tokens (D-023), added in Phase 6.
*/

Route::get('/status', fn () => ApiResponse::success([
    'name' => config('app.name'),
]))->name('status');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category:slug}/filters', [CategoryController::class, 'filters'])->name('categories.filters');

Route::get('/components', [ComponentController::class, 'index'])->name('components.index');
Route::get('/components/{slug}', [ComponentController::class, 'show'])->name('components.show');

Route::get('/builds', [BuildController::class, 'index'])->name('builds.index');
Route::get('/builds/{slug}', [BuildController::class, 'show'])->name('builds.show');
Route::get('/builds/{slug}/analysis', [AnalysisController::class, 'show'])->name('builds.analysis');

Route::prefix('admin')->name('admin.')->group(function () {
    // POST /admin/login (Phase 6)

    Route::middleware('auth:sanctum')->group(function () {
        // Admin CRUD (Phase 6)
    });
});
