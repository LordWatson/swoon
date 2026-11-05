<?php

declare(strict_types=1);

use App\Modules\Accounts\Http\Controllers\AuthController;
use App\Modules\Accounts\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| Accounts & Couple Linking routes
|------------------------------------------------------------------------------
|
| Loaded by App\Providers\ModuleServiceProvider inside the "api" middleware
| group with an /api prefix. See app/Modules/Accounts/module.md for the endpoint
| table this file implements.
|
| The auth routes carry the `auth` rate limiter, which throttles by IP *and* by
| email+IP — see AppServiceProvider for why the email limit is not keyed on the
| address alone.
|
*/

Route::middleware('throttle:auth')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', [MeController::class, 'show'])->name('me.show');
});
