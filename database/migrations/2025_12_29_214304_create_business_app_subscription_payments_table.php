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
        Schema::create('business_app_subscription_payments', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('business_app_subscription_id');

    $table->string('attachment_url');
    $table->enum('status', ['uploaded', 'verified', 'rejected'])->default('uploaded');

    $table->timestamps();

    $table->foreign(
        'business_app_subscription_id',
        'fk_bapp_sub_payment_subscription'
    )->references('id')
     ->on('business_app_subscriptions')
     ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_app_subscription_payments');
    }
};
