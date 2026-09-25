<?php

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Row of "Mes livraisons".
 *
 * @mixin Delivery
 */
class DeliverySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'vehicle' => $this->vehicle_code,
            'pickupAddress' => $this->pickup_address,
            'deliveryAddress' => $this->delivery_address,
            'receiverName' => $this->receiver_name,
            'total' => $this->total_amount,
            'currency' => $this->currency,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
