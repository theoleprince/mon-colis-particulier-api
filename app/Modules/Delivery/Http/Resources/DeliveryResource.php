<?php

namespace App\Modules\Delivery\Http\Resources;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryEvent;
use App\Modules\Delivery\Models\DeliveryMedia;
use App\Modules\Delivery\Models\DeliveryPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Full delivery (detail, tracking screen).
 *
 * @mixin Delivery
 */
class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disk = Storage::disk(config('moncolis.delivery.media_disk'));

        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'canCancel' => $this->status->isCancellableByCustomer(),
            'vehicle' => [
                'code' => $this->vehicle_code,
                'label' => $this->vehicle?->label,
            ],
            'pickup' => $this->point('pickup'),
            'delivery' => $this->point('delivery'),
            'sender' => ['name' => $this->sender_name, 'phone' => $this->sender_phone],
            'receiver' => ['name' => $this->receiver_name, 'phone' => $this->receiver_phone],
            'packages' => $this->packages->map(fn (DeliveryPackage $p) => [
                'nature' => ['code' => $p->nature?->code, 'label' => $p->nature?->label],
                'description' => $p->description,
                'weightKg' => $p->weight_kg,
                'quantity' => $p->quantity,
                'declaredValue' => $p->declared_value,
                'lengthCm' => $p->length_cm,
                'widthCm' => $p->width_cm,
                'heightCm' => $p->height_cm,
            ])->all(),
            'options' => $this->options,
            'distanceKm' => $this->distance_km,
            'etaMinutes' => $this->eta_minutes,
            'price' => [
                'breakdown' => $this->breakdown,
                'total' => $this->total_amount,
                'currency' => $this->currency,
            ],
            'payment' => [
                'method' => $this->payment_method->value,
                'methodLabel' => $this->payment_method->label(),
                'status' => $this->payment_status,
                'phone' => $this->payment_phone,
            ],
            'media' => $this->media->map(fn (DeliveryMedia $m) => [
                'type' => $m->type,
                'url' => $disk->url($m->path),
            ])->all(),
            'events' => $this->events->map(fn (DeliveryEvent $e) => [
                'status' => $e->status->value,
                'label' => $e->status->label(),
                'actor' => $e->actor,
                'note' => $e->note,
                'at' => $e->created_at?->toIso8601String(),
            ])->all(),
            'cancelReason' => $this->cancel_reason,
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function point(string $prefix): array
    {
        return [
            'address' => $this->{"{$prefix}_address"},
            'details' => $this->{"{$prefix}_details"},
            'instructions' => $this->{"{$prefix}_instructions"},
            'latitude' => $this->{"{$prefix}_latitude"},
            'longitude' => $this->{"{$prefix}_longitude"},
        ];
    }
}
