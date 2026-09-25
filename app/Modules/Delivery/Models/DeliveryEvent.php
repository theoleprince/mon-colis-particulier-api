<?php

namespace App\Modules\Delivery\Models;

use App\Modules\Delivery\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * One step of the delivery history. actor: customer | courier | system.
 */
class DeliveryEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['status', 'actor', 'note', 'created_at'];

    protected function casts(): array
    {
        return ['status' => DeliveryStatus::class, 'created_at' => 'datetime'];
    }
}
