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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete(); 
            $table->string('label'); 
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable(); 
            // $table->foreignId('assigned_to')->nullable()->constrained('users')->cascadeOnDelete(); visable ot employee
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'awaiting_feedback', 'canceled'])->default('not_started'); 
            $table-> enum('priority', ['High', 'Low', 'Medium'])->default('Medium'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
