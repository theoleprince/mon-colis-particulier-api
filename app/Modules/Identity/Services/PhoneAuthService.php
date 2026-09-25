<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\OtpPurpose;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Yango-like sign-in: phone number → SMS code → connected. The same flow
 * signs up unknown numbers (account created after the code is checked),
 * so nothing reveals whether a number already has an account before that.
 */
class PhoneAuthService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
    ) {
    }

    public function requestCode(string $e164Phone): OtpChallenge
    {
        return $this->otp->send($e164Phone, OtpPurpose::Login);
    }

    /**
     * @return array{user: User, token: string, expiresAt: Carbon, isNewUser: bool}
     */
    public function verifyCode(string $e164Phone, string $code, ?string $deviceName = null): array
    {
        $this->otp->verify($e164Phone, OtpPurpose::Login, $code);

        $user = User::query()->where('phone', $e164Phone)->first();
        $isNewUser = $user === null;

        if ($isNewUser) {
            $user = DB::transaction(function () use ($e164Phone) {
                $user = new User(['phone' => $e164Phone]);
                // Explicit: a freshly created model does not carry the DB defaults.
                $user->status = User::STATUS_ACTIVE;
                $user->save();
                $user->preferences()->create();

                return $user;
            });
        }

        // Receiving the code proves the number belongs to the user.
        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return ['user' => $user, 'isNewUser' => $isNewUser] + $this->auth->startSession($user, $deviceName);
    }
}
