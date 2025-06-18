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
        Schema::create('screen_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->onDelete('cascade');
            $table->text('comment');
            $table->enum('review_type', ['ui', 'frontend', 'backend', 'mobile'])->default('ui');
            $table->morphs('creator');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_reviews');
    }
};
