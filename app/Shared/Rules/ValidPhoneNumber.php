<?php

namespace App\Shared\Rules;

use App\Shared\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNumber::isValid($value)) {
            $fail('Le numéro de téléphone n\'est pas valide.');
        }
    }
}
