<?php

namespace App\Modules\Profile\Enums;

enum SavedPlaceType: string
{
    case Home = 'home';
    case Work = 'work';
    case Other = 'other';

    /**
     * Home and work are unique per user (one "Maison", one "Travail").
     */
    public function isUnique(): bool
    {
        return $this !== self::Other;
    }

    public function defaultLabel(): string
    {
        return match ($this) {
            self::Home => 'Maison',
            self::Work => 'Travail',
            self::Other => 'Adresse favorite',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Home => 0,
            self::Work => 1,
            self::Other => 2,
        };
    }
}
