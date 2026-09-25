<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Paid option (express, fragile, assurance...). pricing_type:
 *  - fixed: amount FCFA
 *  - fare_percent: amount % of the ride fare
 *  - value_percent: amount % of the declared value (at least min_amount)
 */
class DeliveryOption extends Model
{
    public const FIXED = 'fixed';

    public const FARE_PERCENT = 'fare_percent';

    public const VALUE_PERCENT = 'value_percent';

    protected $fillable = ['code', 'label', 'description', 'pricing_type', 'amount', 'min_amount', 'sort', 'active'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'min_amount' => 'integer', 'active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort');
    }

    public function priceFor(int $fare, int $declaredValue): int
    {
        $price = match ($this->pricing_type) {
            self::FARE_PERCENT => $fare * $this->amount / 100,
            self::VALUE_PERCENT => $declaredValue * $this->amount / 100,
            default => $this->amount,
        };

        return (int) ceil(max($price, $this->min_amount));
    }
}
