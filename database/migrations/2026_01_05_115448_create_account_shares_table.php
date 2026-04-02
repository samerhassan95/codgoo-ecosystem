<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountSharesTable extends Migration
{
    public function up()
    {
        Schema::create('account_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index(); // the owner
            $table->string('email')->index(); // the invited account
            $table->json('apps')->nullable(); // array of app slugs the invite has access to
            $table->string('status')->default('pending'); // pending, accepted, revoked
            $table->string('invite_code')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_shares');
    }
}