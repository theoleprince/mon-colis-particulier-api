<?php

namespace App\Modules\Profile\Http\Resources;

use App\Modules\Profile\Models\SavedPlace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavedPlace
 */
class SavedPlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->label,
            'address' => $this->address,
            'addressDetails' => $this->address_details,
            'instructions' => $this->instructions,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lastUsedAt' => $this->last_used_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
