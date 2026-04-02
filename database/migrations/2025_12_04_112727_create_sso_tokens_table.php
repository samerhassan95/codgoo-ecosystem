<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_tokens', function (Blueprint $table) {
            $table->id();
                        $table->string('token', 512)->unique();
            $table->enum('token_type', ['login', 'profile_access'])
                ->default('login')
                ->comment('login = short-lived (30s), profile_access = long-lived (expires with subscription)');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('service_app_id')->nullable();
            $table->unsignedBigInteger('custom_bundle_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Indexes for faster lookups
            $table->index('token');
            $table->index('client_id');
            $table->index(['service_app_id', 'client_id']);
            $table->index('expires_at');

            // Foreign keys - changed 'customers' to 'clients'
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')  // Changed from 'customers' to 'clients'
                ->onDelete('cascade');

            $table->foreign('service_app_id')
                ->references('id')
                ->on('service_apps')
                ->onDelete('cascade');

            $table->foreign('custom_bundle_id')
                ->references('id')
                ->on('custom_bundles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_tokens');
    }
};
