<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @return array{user: User, token: string, expiresAt: Carbon}
     */
    public function login(string $identifier, string $password, ?string $deviceName = null): array
    {
        $identifier = trim($identifier);
        $phone = PhoneNumber::isValid($identifier) ? PhoneNumber::normalize($identifier) : null;

        $user = User::query()->whereIdentifier($identifier, $phone)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new BusinessException('Identifiant ou mot de passe incorrect.', 'INVALID_CREDENTIALS', 401);
        }

        if (! $user->isActive()) {
            throw new BusinessException('Ce compte est suspendu. Contactez le support.', 'ACCOUNT_SUSPENDED', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return ['user' => $user] + $this->issueToken($user, $deviceName);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{user: User, token: string, expiresAt: Carbon}
     */
    public function register(array $attributes, ?string $deviceName = null): array
    {
        return DB::transaction(function () use ($attributes, $deviceName) {
            $user = User::create($attributes);
            $user->forceFill(['last_login_at' => now()])->save();
            $user->preferences()->create();

            return ['user' => $user] + $this->issueToken($user, $deviceName);
        });
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * @return array{token: string, expiresAt: Carbon}
     */
    private function issueToken(User $user, ?string $deviceName): array
    {
        $expiresAt = now()->addDays(config('moncolis.auth.token_ttl_days', 30));

        $token = $user->createToken($deviceName ?: 'mobile', ['*'], $expiresAt)->plainTextToken;

        return ['token' => $token, 'expiresAt' => $expiresAt];
    }
}
