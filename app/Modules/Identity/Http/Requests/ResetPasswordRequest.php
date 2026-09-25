<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Validation\Rules\Password;

/**
 * "Mot de passe oublié", step 2: identifier, received code and new password.
 */
class ResetPasswordRequest extends ForgotPasswordRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'code' => ['required', 'string', 'digits:'.config('moncolis.otp.length')],
            'password' => ['required', 'string', Password::min(8)],
            'passwordConfirmation' => ['required', 'same:password'],
            'deviceName' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'passwordConfirmation.same' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }
}
