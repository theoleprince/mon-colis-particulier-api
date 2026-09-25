<?php

namespace App\Modules\Profile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    public const LANGUAGES = ['fr', 'en'];

    protected $fillable = [
        'language',
        'theme',
        'notify_push',
        'notify_sms',
        'notify_email',
        'marketing_opt_in',
    ];

    /**
     * Mirrors the database defaults so a freshly created row is complete without a refresh.
     */
    protected $attributes = [
        'language' => 'fr',
        'notify_push' => true,
        'notify_sms' => true,
        'notify_email' => false,
        'marketing_opt_in' => false,
    ];

    protected function casts(): array
    {
        return [
            'notify_push' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_email' => 'boolean',
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
