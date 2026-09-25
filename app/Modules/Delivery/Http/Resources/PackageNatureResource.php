<?php

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\PackageNature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PackageNature
 */
class PackageNatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'unitBilling' => $this->unit_billing,
        ];
    }
}
