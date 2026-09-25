<?php

namespace App\Modules\Profile\Database\Factories;

use App\Models\User;
use App\Modules\Profile\Enums\SavedPlaceType;
use App\Modules\Profile\Models\SavedPlace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedPlace>
 */
class SavedPlaceFactory extends Factory
{
    protected $model = SavedPlace::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => SavedPlaceType::Other,
            'label' => fake()->words(2, true),
            'address' => fake()->streetAddress().', Yaoundé',
            // Around Yaoundé.
            'latitude' => fake()->latitude(3.80, 3.95),
            'longitude' => fake()->longitude(11.45, 11.58),
        ];
    }

    public function home(): static
    {
        return $this->state(fn () => ['type' => SavedPlaceType::Home, 'label' => 'Maison']);
    }

    public function work(): static
    {
        return $this->state(fn () => ['type' => SavedPlaceType::Work, 'label' => 'Travail']);
    }
}
