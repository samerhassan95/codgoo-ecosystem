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
        Schema::create('custom_bundle_service_app', function (Blueprint $table) {
            $table->foreignId('custom_bundle_id')->constrained('custom_bundles')->onDelete('cascade');
            $table->foreignId('service_app_id')->constrained('service_apps')->onDelete('cascade');

            $table->primary(['custom_bundle_id', 'service_app_id']);
            $table->string('external_profile_url', 1000)->nullable(); // ✅ CORRECTED LINE            // Soft Deletes for the DELETE endpoint (Endpoint 6)
            // This allows us to track applications that were removed from the bundle.
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_bundle_service_app');
    }
};
