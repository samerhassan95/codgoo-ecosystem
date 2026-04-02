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
        Schema::create('business_app_subscriptions', function (Blueprint $table) {
    $table->id();
   $table->unsignedBigInteger('customer_id');

$table->foreign(
    'customer_id',
    'fk_bapp_subscription_client'
)->references('id')
 ->on('clients')
 ->cascadeOnDelete();
    $table->foreignId('business_app_id')->constrained()->cascadeOnDelete();
    $table->foreignId('business_app_plan_id')->constrained()->cascadeOnDelete();

    $table->enum('status', ['pending', 'active', 'rejected', 'expired'])->default('pending');
    $table->boolean('is_approved')->default(false);

    $table->timestamp('started_at')->nullable();
    $table->timestamp('expires_at')->nullable();

    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('admins');

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_app_subscriptions');
    }
};
