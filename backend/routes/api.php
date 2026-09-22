<?php

use App\Support\Http\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (prefix /api) — design in docs/API.md
|--------------------------------------------------------------------------
| Public routes: stateless reads and stateless analysis, no login (D-001).
| Admin routes: Sanctum bearer tokens (D-023).
| Endpoints are added in Phase 4 (public) and Phase 6 (admin).
*/

Route::get('/status', fn () => ApiResponse::success([
    'name' => config('app.name'),
]))->name('status');

// Public: categories, components, builds, builder, compare (Phase 4)

Route::prefix('admin')->name('admin.')->group(function () {
    // POST /admin/login (Phase 6)

    Route::middleware('auth:sanctum')->group(function () {
        // Admin CRUD (Phase 6)
    });
});
