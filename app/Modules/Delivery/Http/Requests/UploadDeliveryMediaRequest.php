<?php

namespace App\Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDeliveryMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:7'],
            // Photos up to 10 MB, videos (mp4 / mov) up to 50 MB.
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:51200'],
        ];
    }
}
