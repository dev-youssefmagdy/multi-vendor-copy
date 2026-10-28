<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mobile_access_tokens')) {
            Schema::create('mobile_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('device_name')->nullable();
                $table->string('token_hash', 64)->unique();
                $table->string('refresh_hash', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mobile_app_settings')) {
            Schema::create('mobile_app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mobile_app_theme')) {
            Schema::create('mobile_app_theme', function (Blueprint $table) {
                $table->id();
                $table->string('theme_slug');
                $table->string('variant_key')->default('v1');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_theme');
        Schema::dropIfExists('mobile_app_settings');
        Schema::dropIfExists('mobile_access_tokens');
    }
};
