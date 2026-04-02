<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientPaymentMethodsTable extends Migration
{
    public function up()
    {
        Schema::create('client_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('type'); // card, paypal, etc
            $table->string('provider_token')->nullable(); // token from payment gateway
            $table->string('label')->nullable(); // e.g., "Visa •••• 4242"
            $table->boolean('default')->default(false);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_payment_methods');
    }
}