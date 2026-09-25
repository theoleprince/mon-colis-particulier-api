<?php

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base provider of a business module (app/Modules/<Module>).
 *
 * Convention, relative to the module folder:
 *   - Routes/api.php          loaded under the `/api` prefix and `api` middleware group
 *   - Database/Migrations/    loaded automatically
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $path = $this->modulePath();

        if (is_dir($path.'/Database/Migrations')) {
            $this->loadMigrationsFrom($path.'/Database/Migrations');
        }

        if (! $this->app->routesAreCached() && is_file($path.'/Routes/api.php')) {
            Route::middleware('api')->prefix('api')->group($path.'/Routes/api.php');
        }

        $this->bootModule();
    }

    /**
     * Module specific boot logic (rate limiters, policies, observers...).
     */
    protected function bootModule(): void
    {
    }

    protected function modulePath(): string
    {
        return dirname((new ReflectionClass(static::class))->getFileName());
    }
}
