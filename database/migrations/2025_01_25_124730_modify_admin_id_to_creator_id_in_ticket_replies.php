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
        Schema::table('ticket_replies', function (Blueprint $table) {

            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
            
            $table->unsignedBigInteger('creator_id');
            $table->string('creator_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropColumn(['creator_id', 'creator_type']);

            
            $table->foreignId('admin_id')->constrained('admins');
        });
    }
};
