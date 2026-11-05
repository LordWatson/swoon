<?php

declare(strict_types=1);

use App\Modules\Accounts\Http\Controllers\AuthController;
use App\Modules\Accounts\Http\Controllers\CoupleController;
use App\Modules\Accounts\Http\Controllers\InviteController;
use App\Modules\Accounts\Http\Controllers\MeController;
use App\Modules\Accounts\Http\Controllers\PrivacyController;
use App\Modules\Accounts\Http\Controllers\SocialAuthController;
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
    Route::post('auth/apple', [SocialAuthController::class, 'apple'])->name('auth.apple');
    Route::post('auth/google', [SocialAuthController::class, 'google'])->name('auth.google');
});

// Unauthenticated on purpose: the link lands here before sign-up, and it
// returns a first name and nothing else (InvitePreviewResource).
Route::get('invites/{code}', [InviteController::class, 'show'])
    ->middleware('throttle:invite-preview')
    ->name('invites.show');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', [MeController::class, 'show'])->name('me.show');

    Route::post('invites', [InviteController::class, 'store'])->name('invites.store');
    Route::post('invites/{code}/accept', [InviteController::class, 'accept'])->name('invites.accept');
    Route::delete('invites/{invite}', [InviteController::class, 'destroy'])->name('invites.destroy');

    Route::get('couple', [CoupleController::class, 'show'])->name('couple.show');
    Route::patch('couple', [CoupleController::class, 'update'])->name('couple.update');
    // Unlink. No couple id: a user has at most one, so taking an id would only
    // create a way to ask about somebody else's.
    Route::delete('couple', [CoupleController::class, 'destroy'])->name('couple.destroy');

    Route::post('me/export', [PrivacyController::class, 'export'])->name('me.export');
    Route::delete('me', [PrivacyController::class, 'destroy'])->name('me.destroy');
});

// The export download. `signed` proves we minted the URL and that it has not
// expired; the controller still checks the caller owns the export, because
// these links travel by email.
Route::get('me/export/{user}/{path}', [PrivacyController::class, 'download'])
    ->middleware(['auth:sanctum', 'signed'])
    ->name('me.export.download');
