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
        Schema::create('task_discussion_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_id'); // FK to task_discussions
            $table->unsignedBigInteger('user_id');       // FK to users/employees
            $table->string('user_type');                 // Polymorphic type: App\Models\Employee, etc.
            $table->timestamps();

            // Optional: add foreign key for discussion
            $table->foreign('discussion_id')
                  ->references('id')->on('task_discussions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_discussion_members');
    }
};
