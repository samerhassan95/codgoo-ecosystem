<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('custom_bundles', function (Blueprint $table) {
        // 1. Drop the existing foreign key
        // The naming convention is usually table_column_foreign
        $table->dropForeign(['customer_id']);

        // 2. Add it back with cascade
        $table->foreign('customer_id')
              ->references('id')
              ->on('clients')
              ->onDelete('cascade'); 
    });
}

public function down()
{
    Schema::table('custom_bundles', function (Blueprint $table) {
        $table->dropForeign(['customer_id']);
        $table->foreign('customer_id')
              ->references('id')
              ->on('clients'); // back to default (restrict)
    });
}
};
