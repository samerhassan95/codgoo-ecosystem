<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        // Polymorphic relation
        $table->morphs('payable'); // payable_type, payable_id

        $table->unsignedBigInteger('payer_id')->index();
        $table->string('payer_type'); // client, admin, system

        $table->string('provider'); // paypal, offline, stripe
        $table->string('provider_payment_id')->nullable();

        $table->integer('amount');
        $table->string('currency', 3);

        $table->enum('status', [
            'pending',
            'completed',
            'failed',
            'cancelled'
        ])->default('pending');

        $table->json('meta')->nullable(); // PayPal payload, debug

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
