<?php

namespace App\Modules\Profile\Services;

use App\Models\User;
use App\Modules\Profile\Enums\SavedPlaceType;
use App\Modules\Profile\Models\SavedPlace;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Collection;

/**
 * "Mes adresses": home, work and favourite places.
 */
class SavedPlaceService
{
    /**
     * Home first, then work, then favourites (most recently used first).
     *
     * @return Collection<int, SavedPlace>
     */
    public function list(User $user): Collection
    {
        return $user->savedPlaces()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->sortBy(fn (SavedPlace $place) => $place->type->sortOrder())
            ->values();
    }

    /**
     * Setting "home" or "work" again replaces the existing one (Yango behaviour).
     *
     * @param  array<string, mixed>  $attributes
     * @return array{place: SavedPlace, created: bool}
     */
    public function save(User $user, array $attributes): array
    {
        /** @var SavedPlaceType $type */
        $type = $attributes['type'];
        $attributes['label'] ??= $type->defaultLabel();

        if ($type->isUnique()) {
            $existing = $user->savedPlaces()->where('type', $type)->first();
            if ($existing !== null) {
                $existing->update($attributes);

                return ['place' => $existing, 'created' => false];
            }
        }

        if ($user->savedPlaces()->count() >= config('moncolis.profile.max_saved_places')) {
            throw new BusinessException(
                'Vous avez atteint le nombre maximum d\'adresses enregistrées.',
                'SAVED_PLACES_LIMIT',
            );
        }

        return ['place' => $user->savedPlaces()->create($attributes), 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(SavedPlace $place, array $attributes): SavedPlace
    {
        $type = $attributes['type'] ?? null;

        if ($type instanceof SavedPlaceType && $type !== $place->type && $type->isUnique()) {
            $taken = SavedPlace::query()
                ->where('user_id', $place->user_id)
                ->where('type', $type)
                ->exists();

            if ($taken) {
                throw new BusinessException(
                    'Vous avez déjà une adresse "'.$type->defaultLabel().'". Modifiez-la directement.',
                    'SAVED_PLACE_TYPE_TAKEN',
                    409,
                );
            }
        }

        if (array_key_exists('label', $attributes) && blank($attributes['label'])) {
            $attributes['label'] = ($type ?? $place->type)->defaultLabel();
        }

        $place->update($attributes);

        return $place;
    }

    public function delete(SavedPlace $place): void
    {
        $place->delete();
    }
}
