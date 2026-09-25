<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPackage extends Model
{
    protected $fillable = [
        'package_nature_id', 'description', 'weight_kg', 'quantity', 'declared_value',
        'length_cm', 'width_cm', 'height_cm',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'quantity' => 'integer',
            'declared_value' => 'integer',
        ];
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(PackageNature::class, 'package_nature_id');
    }
}
