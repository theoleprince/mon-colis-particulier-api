<?php

namespace App\Modules\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                Rule::excludeIf(fn () => ! $this->hasPassword()),
                'required', 'string', 'current_password:sanctum',
            ],
            // Account without password (created by SMS code): explicit confirmation instead.
            'confirm' => [
                Rule::excludeIf(fn () => $this->hasPassword()),
                'required', 'accepted',
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * `true` outside a request (API docs generation): documents the password variant.
     */
    private function hasPassword(): bool
    {
        return $this->user()?->hasPassword() ?? true;
    }

    public function messages(): array
    {
        return [
            'password.current_password' => 'Mot de passe incorrect.',
            'confirm.required' => 'Confirmez la suppression de votre compte.',
        ];
    }
}
