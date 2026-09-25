<?php

use App\Modules\Profile\Http\Controllers\AccountSecurityController;
use App\Modules\Profile\Http\Controllers\PreferenceController;
use App\Modules\Profile\Http\Controllers\ProfileController;
use App\Modules\Profile\Http\Controllers\SavedPlaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show']);
    Route::patch('/', [ProfileController::class, 'update']);
    Route::delete('/', [ProfileController::class, 'destroy']);

    Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/avatar', [ProfileController::class, 'destroyAvatar']);

    Route::put('/password', [AccountSecurityController::class, 'updatePassword']);
    Route::post('/phone/otp', [AccountSecurityController::class, 'requestPhoneChange'])->middleware('throttle:otp');
    Route::post('/phone/verify', [AccountSecurityController::class, 'confirmPhoneChange'])->middleware('throttle:otp-verify');
    Route::post('/email/otp', [AccountSecurityController::class, 'requestEmailVerification'])->middleware('throttle:otp');
    Route::post('/email/verify', [AccountSecurityController::class, 'confirmEmailVerification'])->middleware('throttle:otp-verify');

    Route::get('/preferences', [PreferenceController::class, 'show']);
    Route::patch('/preferences', [PreferenceController::class, 'update']);

    Route::apiResource('places', SavedPlaceController::class)->whereNumber('place');
});
