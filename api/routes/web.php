<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| Web routes
|------------------------------------------------------------------------------
|
| This is an API-only application — there is no Blade front end and no Vite
| build. The only web routes that belong here are ones a browser must reach:
| invite deep-link fallbacks, OAuth callbacks, and legal redirects.
|
*/

Route::get('/', fn (): JsonResponse => response()->json([
    'app' => config('swoon.name'),
    'api' => url('/api/health'),
]));
