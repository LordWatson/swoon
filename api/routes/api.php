<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| API routes
|------------------------------------------------------------------------------
|
| Module routes are NOT registered here — each module owns an
| app/Modules/<Module>/routes.php, discovered by ModuleServiceProvider. Keep
| this file for cross-cutting, module-less endpoints only.
|
*/

/**
 * Health check.
 *
 * Deliberately unauthenticated and dependency-light: the mobile app calls it on
 * boot to confirm it is pointed at a reachable API, and it is the first thing
 * checked after a Herd or network change. It reports the product name from
 * config so a misconfigured environment is obvious at a glance.
 */
Route::get('/health', function (): JsonResponse {
    return response()->json([
        'status' => 'ok',
        'app' => config('swoon.name'),
        'environment' => app()->environment(),
        'version' => config('app.version', 'dev'),
        'time' => now()->toIso8601String(),
    ]);
})->name('health');
