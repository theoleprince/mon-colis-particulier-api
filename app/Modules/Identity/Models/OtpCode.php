<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;

/**
 * One-time code sent by SMS. Only the hash of the code is stored.
 */
class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'destination',
        'purpose',
        'code_hash',
        'expires_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
