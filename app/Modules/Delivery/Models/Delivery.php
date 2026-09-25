<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'quote_id', 'status', 'vehicle_code',
        'pickup_address', 'pickup_details', 'pickup_instructions', 'pickup_latitude', 'pickup_longitude',
        'delivery_address', 'delivery_details', 'delivery_instructions', 'delivery_latitude', 'delivery_longitude',
        'sender_name', 'sender_phone', 'receiver_name', 'receiver_phone',
        'distance_km', 'eta_minutes', 'options', 'breakdown', 'total_amount', 'currency',
        'payment_method', 'payment_status', 'payment_phone',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'payment_method' => PaymentMethod::class,
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'distance_km' => 'float',
            'eta_minutes' => 'integer',
            'options' => 'array',
            'breakdown' => 'array',
            'total_amount' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(DeliveryPackage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DeliveryMedia::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_code', 'code');
    }
}
