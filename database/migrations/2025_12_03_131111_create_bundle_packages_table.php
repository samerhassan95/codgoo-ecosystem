<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bundle_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('tagline');

            // Price structure
            $table->unsignedInteger('price_amount');
            $table->string('price_currency', 3)->default('EGP');

            // Features (stored as JSON array of strings)
            $table->json('features');

            // Savings
            $table->unsignedTinyInteger('savings_percentage');
            $table->string('savings_text');

            // Badges (stored as JSON array of strings, e.g., ["Most Popular"])
            $table->json('badges')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_packages');
    }
};
