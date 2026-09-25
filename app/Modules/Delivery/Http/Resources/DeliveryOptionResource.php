<?php

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\DeliveryOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryOption
 */
class DeliveryOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            /** fixed : montant en FCFA · fare_percent : % de la course · value_percent : % de la valeur déclarée */
            'pricingType' => $this->pricing_type,
            'amount' => $this->amount,
            'minAmount' => $this->min_amount,
        ];
    }
}
