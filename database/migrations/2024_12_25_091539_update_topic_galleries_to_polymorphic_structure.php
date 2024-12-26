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
        Schema::table('topic_galleries', function (Blueprint $table) {
                Schema::rename('topic_galleries', 'galleries');
            });
 
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropForeign('topic_galleries_topic_id_foreign'); 
            $table->dropColumn('topic_id');

            $table->unsignedBigInteger('galleriable_id'); 
            $table->string('galleriable_type'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            // Revert polymorphic structure changes
            $table->dropColumn(['galleriable_id', 'galleriable_type']);

            // Restore topic_id column
            $table->unsignedBigInteger('topic_id')->after('id');
            $table->foreign('topic_id')->references('id')->on('topics')->onDelete('cascade');
        });

        // Rename table back to 'topic_galleries'
        Schema::rename('galleries', 'topic_galleries');
    }
};
