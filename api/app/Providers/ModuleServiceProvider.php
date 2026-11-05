<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the light modular structure under app/Modules.
 *
 * Each module is a plain folder holding a routes.php and a module.md, plus the
 * usual Laravel folders — Models, Http/Controllers, Http/Requests,
 * Http/Resources, Actions, Jobs, Policies — created as the first file in each
 * actually lands, not up front. Everything is autoloaded by Composer under the
 * App\Modules namespace. There is no modules package and no per-module service
 * provider: the only thing a module needs wiring for is its routes, which this
 * provider discovers.
 *
 * A module's routes.php is loaded inside the "api" middleware group with an
 * /api prefix, matching routes/api.php. Modules that need different middleware
 * declare it in their own route group inside that file.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // With a warm route cache the routes are already loaded, so reading and
        // re-registering every module's routes.php would be pure waste on each
        // request — and would make `route:cache` pointless for the app's own
        // routes. Laravel's own route provider guards the same way.
        if ($this->app->routesAreCached()) {
            return;
        }

        foreach ($this->moduleRouteFiles() as $routeFile) {
            Route::middleware('api')
                ->prefix('api')
                ->group($routeFile);
        }
    }

    /**
     * Every app/Modules/<Module>/routes.php, sorted so route registration order
     * is deterministic across machines (glob order is filesystem-dependent).
     *
     * @return list<string>
     */
    private function moduleRouteFiles(): array
    {
        $files = glob(app_path('Modules/*/routes.php')) ?: [];

        // sort() reindexes in place, so the result is already a list.
        sort($files);

        return $files;
    }
}
