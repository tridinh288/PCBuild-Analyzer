<?php

use Illuminate\Support\Facades\Route;

// API-only application: the React frontend is a separate static site.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api'),
]));
