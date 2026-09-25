<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reference data of the Expédier module: parcel natures, vehicles (with their
 * tariff grid) and options. Default rows are inserted here so that a fresh
 * installation is immediately usable; values are then edited in database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_natures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label', 80);
            $table->string('description', 255)->nullable();
            // Billed per unit (quantity matters) rather than as a whole.
            $table->boolean('unit_billing')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label', 60);
            $table->string('description', 255)->nullable();
            $table->decimal('max_weight_kg', 8, 2);
            // Tariff grid (FCFA).
            $table->unsignedInteger('base_fare');
            $table->unsignedInteger('per_km');
            $table->decimal('included_weight_kg', 8, 2)->default(0);
            $table->unsignedInteger('per_extra_kg')->default(0);
            $table->unsignedInteger('min_fare');
            // Average speed in town and average time for a courier to reach the pickup point.
            $table->unsignedSmallInteger('speed_kmh');
            $table->unsignedSmallInteger('pickup_eta_minutes');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('delivery_options', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label', 80);
            $table->string('description', 255)->nullable();
            // fixed (FCFA) | fare_percent (% of the ride) | value_percent (% of declared value)
            $table->string('pricing_type', 20);
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('min_amount')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('package_natures')->insert(array_map(fn ($row) => $row + [
            'created_at' => $now, 'updated_at' => $now,
        ], [
            ['code' => 'DOCUMENT', 'label' => 'Documents', 'description' => 'Enveloppe, dossier administratif', 'unit_billing' => false, 'sort' => 1],
            ['code' => 'COLIS_STD', 'label' => 'Colis standard', 'description' => 'Carton ou sac de taille moyenne', 'unit_billing' => true, 'sort' => 2],
            ['code' => 'VETEMENTS', 'label' => 'Vêtements et chaussures', 'description' => null, 'unit_billing' => true, 'sort' => 3],
            ['code' => 'ALIMENTAIRE', 'label' => 'Alimentaire', 'description' => 'Repas, courses, produits frais', 'unit_billing' => true, 'sort' => 4],
            ['code' => 'ELECTRONIQUE', 'label' => 'Électronique', 'description' => 'Téléphone, ordinateur, accessoires', 'unit_billing' => true, 'sort' => 5],
            ['code' => 'FRAGILE', 'label' => 'Objet fragile', 'description' => 'Verre, céramique, objet délicat', 'unit_billing' => true, 'sort' => 6],
            ['code' => 'VOLUMINEUX', 'label' => 'Volumineux', 'description' => 'Meuble, électroménager, gros carton', 'unit_billing' => true, 'sort' => 7],
        ]));

        DB::table('vehicle_types')->insert(array_map(fn ($row) => $row + [
            'created_at' => $now, 'updated_at' => $now,
        ], [
            [
                'code' => 'moto', 'label' => 'Moto', 'description' => 'Rapide, petits colis',
                'max_weight_kg' => 30, 'base_fare' => 500, 'per_km' => 150,
                'included_weight_kg' => 5, 'per_extra_kg' => 50, 'min_fare' => 1000,
                'speed_kmh' => 25, 'pickup_eta_minutes' => 6, 'sort' => 1,
            ],
            [
                'code' => 'tricycle', 'label' => 'Tricycle', 'description' => 'Cargo, colis moyens',
                'max_weight_kg' => 300, 'base_fare' => 800, 'per_km' => 200,
                'included_weight_kg' => 20, 'per_extra_kg' => 20, 'min_fare' => 1500,
                'speed_kmh' => 20, 'pickup_eta_minutes' => 11, 'sort' => 2,
            ],
            [
                'code' => 'camion', 'label' => 'Camion', 'description' => 'Volumineux, déménagement',
                'max_weight_kg' => 3000, 'base_fare' => 3000, 'per_km' => 400,
                'included_weight_kg' => 500, 'per_extra_kg' => 5, 'min_fare' => 5000,
                'speed_kmh' => 18, 'pickup_eta_minutes' => 20, 'sort' => 3,
            ],
        ]));

        DB::table('delivery_options')->insert(array_map(fn ($row) => $row + [
            'created_at' => $now, 'updated_at' => $now,
        ], [
            ['code' => 'express', 'label' => 'Livraison express', 'description' => 'Coursier dédié, sans détour', 'pricing_type' => 'fare_percent', 'amount' => 30, 'min_amount' => 0, 'sort' => 1],
            ['code' => 'fragile', 'label' => 'Colis fragile', 'description' => 'Manipulation avec précaution', 'pricing_type' => 'fixed', 'amount' => 300, 'min_amount' => 0, 'sort' => 2],
            ['code' => 'assurance', 'label' => 'Assurance', 'description' => 'Remboursement de la valeur déclarée en cas de perte ou de casse', 'pricing_type' => 'value_percent', 'amount' => 2, 'min_amount' => 200, 'sort' => 3],
            ['code' => 'accuse_reception', 'label' => 'Accusé de réception', 'description' => 'Signature du destinataire à la remise', 'pricing_type' => 'fixed', 'amount' => 200, 'min_amount' => 0, 'sort' => 4],
            ['code' => 'emballage', 'label' => 'Emballage protecteur', 'description' => 'Le coursier apporte un emballage adapté', 'pricing_type' => 'fixed', 'amount' => 500, 'min_amount' => 0, 'sort' => 5],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_options');
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('package_natures');
    }
};
