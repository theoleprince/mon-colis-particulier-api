<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\PasswordResetController;
use App\Modules\Identity\Http\Controllers\PhoneAuthController;
use Illuminate\Support\Facades\Route;

// Path kept identical to the former Symfony API: the Flutter app already calls /api/login_check.
Route::post('/login_check', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');

// Yango-like sign-in / sign-up by SMS code.
Route::post('/auth/otp/request', [PhoneAuthController::class, 'requestCode'])
    ->middleware('throttle:otp')->name('auth.otp.request');
Route::post('/auth/otp/verify', [PhoneAuthController::class, 'verifyCode'])
    ->middleware('throttle:otp-verify')->name('auth.otp.verify');

// Forgotten password (code by SMS or e-mail).
Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])
    ->middleware('throttle:otp')->name('password.forgot');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:otp-verify')->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
