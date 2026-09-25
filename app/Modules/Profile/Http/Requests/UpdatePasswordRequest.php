<?php

namespace App\Modules\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Accounts created by SMS code have no password yet: the first one is simply set.
            'currentPassword' => [
                Rule::requiredIf(fn () => (bool) $this->user()?->hasPassword()),
                'nullable', 'string', 'current_password:sanctum',
            ],
            'newPassword' => ['required', 'string', Password::min(8), 'different:currentPassword'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
            // Yango-like "log out other devices" (default: yes).
            'logoutOtherDevices' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'currentPassword.current_password' => 'Le mot de passe actuel est incorrect.',
            'newPassword.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
            'newPasswordConfirmation.same' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }
}
