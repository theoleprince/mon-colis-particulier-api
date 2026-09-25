<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('language', 5)->default('fr');
            // App colour theme chosen in Settings (orange / green / blue...).
            $table->string('theme', 30)->nullable();
            $table->boolean('notify_push')->default(true);
            $table->boolean('notify_sms')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
