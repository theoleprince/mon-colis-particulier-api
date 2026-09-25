<?php

namespace App\Modules\Profile\Http\Requests;

use App\Modules\Profile\Enums\SavedPlaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create (POST: all required fields) and update (PATCH/PUT: partial) of a saved place.
 */
class SavedPlaceRequest extends FormRequest
{
    private const FIELDS = [
        'type' => 'type',
        'label' => 'label',
        'address' => 'address',
        'addressDetails' => 'address_details',
        'instructions' => 'instructions',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'type' => [$presence, Rule::enum(SavedPlaceType::class)],
            'label' => ['sometimes', 'nullable', 'string', 'max:60'],
            'address' => [$presence, 'string', 'max:255'],
            'addressDetails' => ['sometimes', 'nullable', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string', 'max:500'],
            'latitude' => [$presence, 'numeric', 'between:-90,90'],
            'longitude' => [$presence, 'numeric', 'between:-180,180'],
        ];
    }

    public function placeAttributes(): array
    {
        $validated = $this->validated();
        $attributes = [];

        foreach (self::FIELDS as $field => $column) {
            if (array_key_exists($field, $validated)) {
                $attributes[$column] = $validated[$field];
            }
        }

        if (isset($attributes['type'])) {
            $attributes['type'] = SavedPlaceType::from($attributes['type']);
        }

        return $attributes;
    }
}
