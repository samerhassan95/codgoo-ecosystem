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
        Schema::create('service_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique(); // For internal lookup/URLs
            $table->enum('type', ['General', 'Master']);
            $table->string('category');
            $table->text('description');
            $table->boolean('is_external')->default(true)->comment('Whether this app uses external SSO authentication.');

            // Price structure (e.g., store 13500 for 135.00 EGP)
            $table->unsignedInteger('price_amount');
            $table->string('price_currency', 3)->default('EGP');

            // Rating structure
            $table->decimal('rating_average', 2, 1);
            $table->unsignedSmallInteger('rating_scale')->default(5);
            $table->unsignedInteger('reviews_count')->default(0);

            // Icon structure (store as JSON or multiple fields)
            $table->string('icon_type')->default('image');
            $table->string('icon_url');
            $table->string('icon_alt')->nullable();
            $table->string('app_url')->nullable()->comment('Root URL of the external service.');
            $table->string('sso_entrypoint')->default('/sso/auth')->comment('SSO endpoint path on the external app.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_apps');
    }
};
