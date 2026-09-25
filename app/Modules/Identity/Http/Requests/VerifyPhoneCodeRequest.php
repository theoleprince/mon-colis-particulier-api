<?php

namespace App\Modules\Identity\Http\Requests;

/**
 * Step 2 of the SMS sign-in flow: the phone number and the received code.
 */
class VerifyPhoneCodeRequest extends PhoneCodeRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'code' => ['required', 'string', 'digits:'.config('moncolis.otp.length')],
            'deviceName' => ['nullable', 'string', 'max:100'],
        ];
    }
}
