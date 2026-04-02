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
        Schema::create('subscription_apps', function (Blueprint $table) {
            $table->id();

            // 1. Link to the Client (Master App User)
            $table->foreignId('client_id')
                ->constrained('clients') // Assumes your client table is named 'clients'
                ->onDelete('cascade'); // If the client is deleted, delete their subscriptions

            // 2. Identify the Marketplace App
            $table->string('app_name')->comment('Unique identifier for the subscribed marketplace app (e.g., invoices, crm_lite)');

            // 3. Status and Expiration (Crucial for Token Logic)
            $table->enum('status', ['active', 'pending', 'expired', 'canceled'])->default('active');
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable(); // The exact time access should stop

            // Optional: Tracking and Billing info
            $table->string('plan_name')->nullable()->comment('E.g., Basic, Pro, Enterprise');
            $table->decimal('price', 8, 2)->nullable();

            // Unique Index to prevent duplicate active subscriptions for the same app/client
            $table->unique(['client_id', 'app_name']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_apps');
    }
};
