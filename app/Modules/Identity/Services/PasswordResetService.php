<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\OtpPurpose;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Mot de passe oublié": a code is sent to the phone number (SMS) or e-mail
 * address typed by the user, then a new password is set and the user is
 * signed in. The answer is identical whether the account exists or not.
 */
class PasswordResetService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly AuthService $auth,
    ) {
    }

    public function requestCode(string $identifier): OtpChallenge
    {
        $destination = $this->destination($identifier);
        $user = $this->findUser($destination);

        if ($user === null) {
            // Same response shape as a real sending: no account enumeration.
            return new OtpChallenge(
                channel: OtpService::isEmail($destination) ? 'email' : 'sms',
                maskedDestination: OtpService::mask($destination),
                expiresIn: (int) config('moncolis.otp.ttl_seconds'),
                resendIn: (int) config('moncolis.otp.resend_cooldown_seconds'),
            );
        }

        return $this->otp->send($destination, OtpPurpose::PasswordReset, $user);
    }

    /**
     * @return array{user: User, token: string, expiresAt: Carbon}
     */
    public function reset(string $identifier, string $code, string $newPassword, ?string $deviceName = null): array
    {
        $destination = $this->destination($identifier);
        $user = $this->findUser($destination);

        if ($user === null) {
            throw new BusinessException('Ce code a expiré. Demandez un nouveau code.', 'OTP_EXPIRED');
        }

        $this->otp->verify($destination, OtpPurpose::PasswordReset, $code, $user);

        DB::transaction(function () use ($user, $destination, $newPassword) {
            $user->forceFill([
                'password' => $newPassword,
                // The code reached this phone / mailbox: it is verified.
                OtpService::isEmail($destination) ? 'email_verified_at' : 'phone_verified_at' => now(),
            ])->save();

            // Anyone who knew the old password is signed out everywhere.
            $user->tokens()->delete();
        });

        return ['user' => $user] + $this->auth->startSession($user, $deviceName);
    }

    private function destination(string $identifier): string
    {
        $identifier = trim($identifier);

        return OtpService::isEmail($identifier)
            ? mb_strtolower($identifier)
            : PhoneNumber::normalize($identifier);
    }

    private function findUser(string $destination): ?User
    {
        return User::query()
            ->where(OtpService::isEmail($destination) ? 'email' : 'phone', $destination)
            ->first();
    }
}
