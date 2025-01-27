<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTestingToStatusEnumInTasks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('not_started', 'in_progress', 'completed', 'awaiting_feedback', 'canceled', 'testing') DEFAULT 'not_started';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('not_started', 'in_progress', 'completed', 'awaiting_feedback', 'canceled') DEFAULT 'not_started';");
    }
}
