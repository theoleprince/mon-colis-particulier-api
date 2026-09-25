<?php

namespace App\Modules\Profile\Services;

use App\Models\User;
use App\Modules\Identity\Enums\OtpPurpose;
use App\Modules\Identity\Services\OtpChallenge;
use App\Modules\Identity\Services\OtpService;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Sensitive account operations: password, phone number (SMS code), deletion.
 */
class AccountSecurityService
{
    public function __construct(private readonly OtpService $otp)
    {
    }

    public function changePassword(User $user, string $newPassword, bool $logoutOtherDevices = true): void
    {
        $user->forceFill(['password' => $newPassword])->save();

        if ($logoutOtherDevices) {
            $current = $user->currentAccessToken();
            $user->tokens()
                ->when($current?->getKey(), fn ($q, $id) => $q->whereKeyNot($id))
                ->delete();
        }
    }

    public function requestPhoneChange(User $user, string $newPhone): OtpChallenge
    {
        if ($newPhone === $user->phone) {
            throw new BusinessException('C\'est déjà votre numéro actuel.', 'SAME_PHONE');
        }

        return $this->otp->send($newPhone, OtpPurpose::PhoneChange, $user);
    }

    public function confirmPhoneChange(User $user, string $newPhone, string $code): User
    {
        $this->otp->verify($newPhone, OtpPurpose::PhoneChange, $code, $user);

        $user->forceFill([
            'phone' => $newPhone,
            'phone_verified_at' => now(),
        ])->save();

        return $user;
    }

    /**
     * Right to be forgotten (required by the stores): identifying data is wiped so the
     * phone / e-mail can be reused, the row is soft-deleted to keep the delivery history.
     */
    public function deleteAccount(User $user, ?string $reason = null): void
    {
        $avatar = $user->avatar_path;

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->savedPlaces()->delete();
            $user->preferences()->delete();

            $user->forceFill([
                'first_name' => null,
                'last_name' => null,
                'username' => null,
                'phone' => null,
                'phone_verified_at' => null,
                'email' => null,
                'email_verified_at' => null,
                'avatar_path' => null,
                'birth_date' => null,
                'gender' => null,
                'remember_token' => null,
            ])->save();

            $user->delete();
        });

        if ($avatar) {
            Storage::disk(config('moncolis.profile.avatar_disk'))->delete($avatar);
        }

        Log::info('Account deleted', ['user_id' => $user->id, 'reason' => $reason]);
    }
}
