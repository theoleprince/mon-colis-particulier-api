<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Rules\PhoneNotTaken;
use App\Shared\Rules\ValidPhoneNumber;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'firstName' => ['nullable', 'string', 'max:80'],
            'lastName' => ['nullable', 'string', 'max:80'],
            'username' => ['nullable', 'string', 'min:3', 'max:60', 'alpha_dash', 'unique:users,username'],
            'phone' => ['nullable', 'required_without:email', 'string', new ValidPhoneNumber, new PhoneNotTaken],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'deviceName' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'phone.required_without' => 'Renseignez un numéro de téléphone ou une adresse e-mail.',
            'email.required_without' => 'Renseignez un numéro de téléphone ou une adresse e-mail.',
        ];
    }

    /**
     * @return array<string, mixed> snake_case attributes ready for the User model
     */
    public function userAttributes(): array
    {
        return [
            'first_name' => $this->validated('firstName'),
            'last_name' => $this->validated('lastName'),
            'username' => $this->validated('username'),
            'phone' => PhoneNumber::normalize($this->validated('phone')),
            'email' => $this->validated('email'),
            'password' => $this->validated('password'),
        ];
    }
}
