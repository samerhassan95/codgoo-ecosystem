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
        Schema::table('custom_bundles', function (Blueprint $table) {
            $table->json('requested_app_ids')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('custom_bundles', function (Blueprint $table) {
            $table->dropColumn('requested_app_ids');
        });
    }
};
