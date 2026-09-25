<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A price computed by the server for one vehicle: the delivery is created from it,
        // so the price announced to the customer is the price charged.
        Schema::create('delivery_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle_code', 20);
            $table->decimal('pickup_latitude', 10, 7);
            $table->decimal('pickup_longitude', 10, 7);
            $table->decimal('delivery_latitude', 10, 7);
            $table->decimal('delivery_longitude', 10, 7);
            $table->decimal('distance_km', 8, 2);
            $table->string('route_source', 10);
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedInteger('declared_value')->default(0);
            $table->json('options');
            $table->json('breakdown');
            $table->unsignedInteger('total');
            $table->unsignedSmallInteger('eta_minutes');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained();
            $table->uuid('quote_id')->nullable();
            $table->string('status', 30)->index();
            $table->string('vehicle_code', 20);

            $table->string('pickup_address', 255);
            $table->string('pickup_details', 255)->nullable();
            $table->string('pickup_instructions', 500)->nullable();
            $table->decimal('pickup_latitude', 10, 7);
            $table->decimal('pickup_longitude', 10, 7);

            $table->string('delivery_address', 255);
            $table->string('delivery_details', 255)->nullable();
            $table->string('delivery_instructions', 500)->nullable();
            $table->decimal('delivery_latitude', 10, 7);
            $table->decimal('delivery_longitude', 10, 7);

            $table->string('sender_name', 120);
            $table->string('sender_phone', 20);
            $table->string('receiver_name', 120);
            $table->string('receiver_phone', 20)->index();

            $table->decimal('distance_km', 8, 2);
            $table->unsignedSmallInteger('eta_minutes');
            $table->json('options');
            $table->json('breakdown');
            $table->unsignedInteger('total_amount');
            $table->string('currency', 3);

            $table->string('payment_method', 20);
            $table->string('payment_status', 20);
            $table->string('payment_phone', 20)->nullable();

            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('delivery_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_nature_id')->constrained();
            $table->string('description', 255)->nullable();
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('declared_value')->default(0);
            $table->unsignedSmallInteger('length_cm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->timestamps();
        });

        // Status history ("chronologie" shown in the tracking screen).
        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('actor', 20);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('delivery_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_media');
        Schema::dropIfExists('delivery_events');
        Schema::dropIfExists('delivery_packages');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('delivery_quotes');
    }
};
