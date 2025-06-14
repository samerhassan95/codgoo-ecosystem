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
        Schema::create('employee_meetings', function (Blueprint $table) {
            $table->id();
            $table->morphs('created_by'); // Can be employee or admin
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->string('meeting_url')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->date('date')->nullable();
            $table->enum('status', ['not_started', 'scheduled', 'completed', 'canceled'])->default('not_started');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_meetings');
    }
};
