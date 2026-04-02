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
    Schema::table('task_discussion_messages', function (Blueprint $table) {
        $table->foreign('discussion_id')
              ->references('id')
              ->on('task_discussions')
              ->onDelete('cascade');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_discussion_messages', function (Blueprint $table) {
            //
        });
    }
};
