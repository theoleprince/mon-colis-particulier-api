<?php

namespace App\Modules\Identity\Http\Requests;

use App\Shared\Rules\ValidPhoneNumber;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Step 1 of the SMS sign-in flow: the phone number.
 */
class PhoneCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new ValidPhoneNumber],
        ];
    }

    public function normalizedPhone(): string
    {
        return PhoneNumber::normalize($this->validated('phone'));
    }
}
