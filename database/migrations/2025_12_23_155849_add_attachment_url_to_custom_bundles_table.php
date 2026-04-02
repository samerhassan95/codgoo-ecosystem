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
        $table->string('attachment_url')->nullable()->after('expires_at');
    });
}

public function down()
{
    Schema::table('custom_bundles', function (Blueprint $table) {
        $table->dropColumn('attachment_url');
    });
}
};
