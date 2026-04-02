<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bundle_package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_package_id')->constrained()->onDelete('cascade');
            $table->string('name'); // monthly, quarterly, annually
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('duration_days')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_package_prices');
    }
};

