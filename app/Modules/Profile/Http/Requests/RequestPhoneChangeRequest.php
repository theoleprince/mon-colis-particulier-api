<?php

namespace App\Modules\Profile\Http\Requests;

use App\Modules\Identity\Rules\PhoneNotTaken;
use App\Shared\Rules\ValidPhoneNumber;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new ValidPhoneNumber, new PhoneNotTaken($this->user()->id)],
        ];
    }

    public function normalizedPhone(): string
    {
        return PhoneNumber::normalize($this->validated('phone'));
    }
}
