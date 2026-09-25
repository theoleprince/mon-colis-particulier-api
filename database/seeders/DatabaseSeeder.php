<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Profile\Enums\SavedPlaceType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo account — login "demo" / "password".
        $user = User::factory()->create([
            'first_name' => 'Awa',
            'last_name' => 'Ngo Bikoe',
            'username' => 'demo',
            'phone' => '+237677897012',
            'email' => 'demo@moncolis.test',
        ]);

        $user->preferences()->create();

        $user->savedPlaces()->createMany([
            [
                'type' => SavedPlaceType::Home,
                'label' => 'Maison',
                'address' => 'Bastos, Yaoundé',
                'latitude' => 3.8780,
                'longitude' => 11.5170,
            ],
            [
                'type' => SavedPlaceType::Work,
                'label' => 'Travail',
                'address' => 'Essos, Yaoundé',
                'latitude' => 3.8650,
                'longitude' => 11.5320,
            ],
        ]);
    }
}
