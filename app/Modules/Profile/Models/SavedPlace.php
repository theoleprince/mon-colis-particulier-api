<?php

namespace App\Modules\Profile\Models;

use App\Models\User;
use App\Modules\Profile\Database\Factories\SavedPlaceFactory;
use App\Modules\Profile\Enums\SavedPlaceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saved address ("Maison", "Travail", favourites) reused as pickup / drop-off
 * point by the Expedier and Course modules.
 */
class SavedPlace extends Model
{
    /** @use HasFactory<SavedPlaceFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'label',
        'address',
        'address_details',
        'instructions',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'type' => SavedPlaceType::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SavedPlaceFactory
    {
        return SavedPlaceFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
