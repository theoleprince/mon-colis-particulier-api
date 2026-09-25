<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Services\OtpService;
use App\Shared\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * "Mot de passe oublié", step 1: `identifier` = phone number or e-mail address.
 */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'identifier' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail) {
                $value = trim((string) $value);
                $valid = OtpService::isEmail($value)
                    ? filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                    : PhoneNumber::isValid($value);
                if (! $valid) {
                    $fail('Saisissez un numéro de téléphone ou une adresse e-mail valide.');
                }
            }],
        ];

        return $rules;
    }
}
