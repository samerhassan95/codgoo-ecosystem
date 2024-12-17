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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete(); 
            $table->string('label'); 
            $table->text('description')->nullable(); 
            $table->decimal('cost', 10, 2); 
            $table->integer('period'); 
            $table->date('start_date')->nullable(); 
            $table->date('end_date')->nullable(); 
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'canceled'])->default('not_started'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
