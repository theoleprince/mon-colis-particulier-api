<?php

namespace App\Modules\Delivery\Http\Requests;

use App\Modules\Delivery\Enums\PaymentMethod;
use App\Shared\Rules\ValidPhoneNumber;
use App\Shared\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sender defaults to the connected user; phone numbers are normalized (+237...).
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $sender = (array) $this->input('sender', []);
        $sender['name'] ??= $user?->fullName() ?? $user?->displayName();
        $sender['phone'] ??= $user?->phone;

        $this->merge(['sender' => $sender]);
    }

    public function rules(): array
    {
        $point = fn (string $key) => [
            "{$key}" => ['required', 'array'],
            "{$key}.address" => ['required', 'string', 'max:255'],
            "{$key}.details" => ['nullable', 'string', 'max:255'],
            "{$key}.instructions" => ['nullable', 'string', 'max:500'],
            "{$key}.latitude" => ['required', 'numeric', 'between:-90,90'],
            "{$key}.longitude" => ['required', 'numeric', 'between:-180,180'],
        ];

        return [
            'quoteId' => ['required', 'uuid'],
            ...$point('pickup'),
            ...$point('delivery'),
            'sender' => ['required', 'array'],
            'sender.name' => ['required', 'string', 'max:120'],
            'sender.phone' => ['required', 'string', new ValidPhoneNumber],
            'receiver' => ['required', 'array'],
            'receiver.name' => ['required', 'string', 'max:120'],
            'receiver.phone' => ['required', 'string', new ValidPhoneNumber],
            'packages' => ['required', 'array', 'min:1', 'max:20'],
            'packages.*.natureCode' => ['required', 'string', Rule::exists('package_natures', 'code')->where('active', true)],
            'packages.*.description' => ['nullable', 'string', 'max:255'],
            'packages.*.weightKg' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'packages.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'packages.*.declaredValue' => ['nullable', 'integer', 'min:0'],
            'packages.*.lengthCm' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'packages.*.widthCm' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'packages.*.heightCm' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'paymentPhone' => ['nullable', 'required_if:paymentMethod,mobile_money', 'string', new ValidPhoneNumber],
        ];
    }

    public function messages(): array
    {
        return [
            'packages.*.natureCode.exists' => 'Nature de colis inconnue.',
            'paymentPhone.required_if' => 'Indiquez le numéro Mobile Money à débiter.',
            'sender.phone.required' => 'Indiquez le numéro de l\'expéditeur.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deliveryData(): array
    {
        $data = $this->validated();
        $data['sender']['phone'] = PhoneNumber::normalize($data['sender']['phone']);
        $data['receiver']['phone'] = PhoneNumber::normalize($data['receiver']['phone']);
        if (! empty($data['paymentPhone'])) {
            $data['paymentPhone'] = PhoneNumber::normalize($data['paymentPhone']);
        }

        return $data;
    }
}
