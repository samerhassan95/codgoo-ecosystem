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
        Schema::create('custom_bundles', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            // Assumes a 'users' or 'customers' table exists
            $table->foreignId('customer_id')->constrained('clients')->comment('Purchasing User/Customer ID');
            $table->foreignId('bundle_package_id')->constrained('bundle_packages');

            // Order details
            $table->unsignedInteger('total_price_amount');
            $table->string('total_price_currency', 3)->default('EGP');
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');

            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Date until modification is allowed');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_bundles');
    }
};
