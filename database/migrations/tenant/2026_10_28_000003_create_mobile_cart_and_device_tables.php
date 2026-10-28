<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mobile_carts')) {
            Schema::create('mobile_carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->unique();
                $table->json('items')->nullable();   // same shape as session('storefront_cart')
                $table->string('coupon_code')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mobile_device_tokens')) {
            Schema::create('mobile_device_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('token', 512)->unique();
                $table->string('platform', 20)->nullable();
                $table->string('app_version', 30)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_device_tokens');
        Schema::dropIfExists('mobile_carts');
    }
};
