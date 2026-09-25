<?php

namespace App\Modules\Profile\Http\Requests;

use App\Modules\Profile\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /** camelCase API field => users column */
    private const FIELDS = [
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'username' => 'username',
        'email' => 'email',
        'gender' => 'gender',
        'birthDate' => 'birth_date',
        'countryCode' => 'country_code',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
        if ($this->filled('countryCode')) {
            $this->merge(['countryCode' => strtoupper((string) $this->input('countryCode'))]);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'firstName' => ['sometimes', 'nullable', 'string', 'max:80'],
            'lastName' => ['sometimes', 'nullable', 'string', 'max:80'],
            'username' => ['sometimes', 'nullable', 'string', 'min:3', 'max:60', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId)],
            'gender' => ['sometimes', 'nullable', Rule::enum(Gender::class)],
            'birthDate' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'countryCode' => ['sometimes', 'nullable', 'string', 'size:2', 'alpha'],
            // The phone number is the account identity: it only changes through the SMS code flow.
            'phone' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.prohibited' => 'Le numéro se modifie via la vérification par SMS (/api/profile/phone/otp).',
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
        ];
    }

    /**
     * @return array<string, mixed> only the fields actually sent, as users columns
     */
    public function profileAttributes(): array
    {
        $validated = $this->validated();
        $attributes = [];

        foreach (self::FIELDS as $field => $column) {
            if (array_key_exists($field, $validated)) {
                $attributes[$column] = $validated[$field];
            }
        }

        return $attributes;
    }
}
