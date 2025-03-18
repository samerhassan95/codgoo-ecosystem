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
        Schema::create('requested_apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->onDelete('cascade');
            $table->string('endpoint');
            $table->enum('method', ['GET', 'POST', 'PUT', 'DELETE']);
            $table->json('request_body')->nullable();
            $table->json('response_structure')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_apis');
    }
};
