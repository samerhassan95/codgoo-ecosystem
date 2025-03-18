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
        Schema::create('implemented_apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_api_id')->constrained('requested_apis')->onDelete('cascade');
            $table->string('postman_collection_url')->nullable();
            $table->enum('status', ['pending', 'complete', 'tested'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implemented_apis');
    }
};
