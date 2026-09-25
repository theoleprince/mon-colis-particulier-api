<?php

namespace App\Modules\Profile\Http\Requests;

use App\Modules\Profile\Models\UserPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    private const FIELDS = [
        'language' => 'language',
        'theme' => 'theme',
        'notifyPush' => 'notify_push',
        'notifySms' => 'notify_sms',
        'notifyEmail' => 'notify_email',
        'marketingOptIn' => 'marketing_opt_in',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['sometimes', 'required', Rule::in(UserPreference::LANGUAGES)],
            'theme' => ['sometimes', 'nullable', 'string', 'max:30'],
            'notifyPush' => ['sometimes', 'boolean'],
            'notifySms' => ['sometimes', 'boolean'],
            'notifyEmail' => ['sometimes', 'boolean'],
            'marketingOptIn' => ['sometimes', 'boolean'],
        ];
    }

    public function preferenceAttributes(): array
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
