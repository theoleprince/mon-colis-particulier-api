<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| Business routes live in each module: app/Modules/<Module>/Routes/api.php,
| loaded by App\Shared\Providers\ModuleServiceProvider (prefix /api).
*/

Route::get('/ping', fn () => response()->json([
    'data' => ['app' => config('app.name'), 'time' => now()->toIso8601String()],
]));
