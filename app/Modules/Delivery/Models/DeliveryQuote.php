<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryQuote extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'vehicle_code', 'pickup_latitude', 'pickup_longitude', 'delivery_latitude',
        'delivery_longitude', 'distance_km', 'route_source', 'weight_kg', 'declared_value',
        'options', 'breakdown', 'total', 'eta_minutes', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'distance_km' => 'float',
            'weight_kg' => 'float',
            'declared_value' => 'integer',
            'options' => 'array',
            'breakdown' => 'array',
            'total' => 'integer',
            'eta_minutes' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
