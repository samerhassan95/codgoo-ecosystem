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
        Schema::table('projects', function (Blueprint $table) {

            $table->dropColumn(['created_by_id', 'created_by_type']);

            $table->morphs('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            
            $table->dropMorphs('created_by');

            $table->unsignedBigInteger('created_by_id');
            $table->string('created_by_type');
        });
    }
};