<?php

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VehicleType
 */
class VehicleTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'maxWeightKg' => $this->max_weight_kg,
            // "À partir de" price shown on the vehicle card before any estimate.
            'minFare' => $this->min_fare,
        ];
    }
}
