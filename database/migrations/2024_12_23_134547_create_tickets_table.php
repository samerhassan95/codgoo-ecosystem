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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('department_id');
            $table-> enum('priority', ['High', 'Low', 'Medium'])->default('Low'); 
            $table->text('description');
            $table->foreignId('created_by')->constrained('clients'); 
            $table->enum('status', ['pending', 'open', 'closed', 'answered'])->default('pending');
            $table->string('attachment')->nullable(); 
            $table->timestamps();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
