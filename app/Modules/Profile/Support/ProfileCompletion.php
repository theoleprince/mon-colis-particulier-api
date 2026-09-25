<?php

namespace App\Modules\Profile\Support;

use App\Models\User;

/**
 * Profile completion gauge shown on the account screen ("Complétez votre profil — 60 %").
 */
final class ProfileCompletion
{
    /**
     * @return array{percent: int, missing: list<string>}
     */
    public static function for(User $user): array
    {
        $checks = [
            'firstName' => filled($user->first_name),
            'lastName' => filled($user->last_name),
            'phoneVerified' => $user->phone_verified_at !== null,
            'email' => filled($user->email),
            'avatar' => filled($user->avatar_path),
        ];

        $missing = array_keys(array_filter($checks, fn (bool $ok) => ! $ok));

        return [
            'percent' => (int) round(100 * (count($checks) - count($missing)) / count($checks)),
            'missing' => $missing,
        ];
    }
}
