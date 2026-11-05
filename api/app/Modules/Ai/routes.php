<?php

declare(strict_types=1);

use App\Modules\Ai\Http\Controllers\AllowanceController;
use Illuminate\Support\Facades\Route;

/*
|------------------------------------------------------------------------------
| AI Platform routes
|------------------------------------------------------------------------------
|
| Loaded by App\Providers\ModuleServiceProvider inside the "api" middleware
| group with an /api prefix. See app/Modules/Ai/module.md for the endpoint
| table this file implements, and tests/Feature/Authorisation for the sweep
| that fails if a route here has no recorded decision about who may reach it.
|
| There is deliberately **no endpoint that takes a prompt**. Everything here
| is about a couple's own allowance and their own past generations; the model
| is reached only from a queued job.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('ai/allowance', AllowanceController::class)->name('ai.allowance');
});
