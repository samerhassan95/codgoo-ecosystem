<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('notification_template_id')->constrained()->onDelete('cascade')->after('message')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['notification_template_id']);
            $table->dropColumn('notification_template_id');
        });
    }
    
};
