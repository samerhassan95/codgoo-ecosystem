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
        Schema::table('employees', function (Blueprint $table) {
            Schema::table('employees', function (Blueprint $table) {
                $table->date('birth_date')->nullable(); 
                $table->date('join_date')->nullable(); 
                $table->year('graduation_year')->nullable();
                $table->unsignedTinyInteger('experience_years')->nullable();
             });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'graduation_year', 'experience_years']);
        });
    }
};
