<?php

namespace App\Modules\Identity\Rules;

use App\Models\User;
use App\Shared\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Uniqueness check on the normalized (E.164) phone number.
 */
class PhoneNotTaken implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $taken = User::withTrashed()
            ->where('phone', PhoneNumber::normalize((string) $value))
            ->when($this->ignoreUserId, fn ($q) => $q->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($taken) {
            $fail('Un compte existe déjà avec ce numéro de téléphone.');
        }
    }
}
