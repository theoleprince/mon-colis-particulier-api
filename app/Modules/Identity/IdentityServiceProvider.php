<?php

namespace App\Modules\Identity;

use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Identity module — accounts, authentication, tokens, OTP codes.
 * Flutter counterpart: lib/features/authentication.
 */
class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function bootModule(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::lower((string) $request->input('username'));

            return Limit::perMinute(config('moncolis.auth.login_attempts_per_minute', 5))
                ->by($identifier.'|'.$request->ip());
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Code checks: the OTP itself allows 5 attempts per code, this only caps brute force per IP.
        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(15)->by($request->ip()));

        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(5)->by(
            ($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()
        ));
    }
}
