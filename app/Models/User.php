<?php

namespace App\Models;

use App\Modules\Profile\Enums\Gender;
use App\Modules\Profile\Models\SavedPlace;
use App\Modules\Profile\Models\UserPreference;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Customer account of MonColis Particulier.
 *
 * Kept in App\Models (Laravel convention expected by auth, Sanctum and factories);
 * profile related behaviour lives in App\Modules\Profile.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'phone',
        'email',
        'password',
        'gender',
        'birth_date',
        'country_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'birth_date' => 'date',
            'gender' => Gender::class,
            'password' => 'hashed',
        ];
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function savedPlaces(): HasMany
    {
        return $this->hasMany(SavedPlace::class);
    }

    /**
     * Login identifier: username, e-mail or phone number (normalized beforehand).
     */
    public function scopeWhereIdentifier(Builder $query, string $identifier, ?string $normalizedPhone): Builder
    {
        return $query->where(function (Builder $q) use ($identifier, $normalizedPhone) {
            $q->where('username', $identifier)
                ->orWhere('email', mb_strtolower($identifier));

            if ($normalizedPhone !== null) {
                $q->orWhere('phone', $normalizedPhone);
            }
        });
    }

    /**
     * Accounts created by SMS code have no password until the user sets one.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function fullName(): ?string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * Name shown in the app header ("Bonjour Awa").
     */
    public function displayName(): string
    {
        return $this->first_name ?: ($this->username ?: 'Utilisateur');
    }
}
