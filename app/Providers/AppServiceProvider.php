<?php

namespace App\Providers;

use App\Shared\Sms\LogSmsGateway;
use App\Shared\Sms\SmsGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);
    }

    public function boot(): void
    {
        // Catch lazy loading / silently discarded attributes early, outside production.
        // (preventAccessingMissingAttributes is left off: freshly created models do not carry
        // their nullable columns and would throw on every read.)
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
