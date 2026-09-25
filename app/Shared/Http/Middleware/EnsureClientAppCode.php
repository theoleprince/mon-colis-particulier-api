<?php

namespace App\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the `client-app-code` header sent by the mobile apps.
 * Disabled when no code is configured (MONCOLIS_CLIENT_APP_CODES empty).
 */
class EnsureClientAppCode
{
    public const HEADER = 'client-app-code';

    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('moncolis.client_app_codes', []);

        if ($allowed !== [] && ! in_array($request->header(self::HEADER), $allowed, true)) {
            return response()->json([
                'message' => 'Application cliente non reconnue.',
                'code' => 'INVALID_CLIENT_APP',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
