<?php

namespace App\Modules\Delivery\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `options` : codes séparés par des virgules (`express,assurance`).
     */
    protected function prepareForValidation(): void
    {
        $options = $this->input('options');
        if (is_string($options)) {
            $this->merge(['options' => array_values(array_filter(array_map('trim', explode(',', $options))))]);
        }
    }

    public function rules(): array
    {
        return [
            'pickupLatitude' => ['required', 'numeric', 'between:-90,90'],
            'pickupLongitude' => ['required', 'numeric', 'between:-180,180'],
            'deliveryLatitude' => ['required', 'numeric', 'between:-90,90'],
            'deliveryLongitude' => ['required', 'numeric', 'between:-180,180'],
            // Total weight of the shipment (sum of weight × quantity).
            'weightKg' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'declaredValue' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', Rule::exists('delivery_options', 'code')->where('active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'options.*.exists' => 'Option inconnue.',
        ];
    }
}
