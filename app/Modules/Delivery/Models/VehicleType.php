<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vehicle offered to the customer (moto, tricycle, camion) with its tariff grid.
 */
class VehicleType extends Model
{
    protected $fillable = [
        'code', 'label', 'description', 'max_weight_kg', 'base_fare', 'per_km',
        'included_weight_kg', 'per_extra_kg', 'min_fare', 'speed_kmh', 'pickup_eta_minutes',
        'sort', 'active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight_kg' => 'float',
            'included_weight_kg' => 'float',
            'base_fare' => 'integer',
            'per_km' => 'integer',
            'per_extra_kg' => 'integer',
            'min_fare' => 'integer',
            'speed_kmh' => 'integer',
            'pickup_eta_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort');
    }
}
