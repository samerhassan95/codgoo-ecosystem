<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientTwoFactorSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('client_two_factor_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('method')->nullable(); // email, sms, authenticator
            $table->string('secret')->nullable(); // for authenticator apps
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_two_factor_settings');
    }
}