<?php

namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PackageNature extends Model
{
    protected $fillable = ['code', 'label', 'description', 'unit_billing', 'sort', 'active'];

    protected function casts(): array
    {
        return ['unit_billing' => 'boolean', 'active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('sort');
    }
}
