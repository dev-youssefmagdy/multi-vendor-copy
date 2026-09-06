<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('central_coupon_country');
    }

    public function down(): void
    {
        Schema::create('central_coupon_country', function (Blueprint $table) {
            $table->foreignId('central_coupon_id')
                  ->constrained('central_coupons')
                  ->cascadeOnDelete();
            $table->foreignId('country_id')
                  ->constrained('countries')
                  ->cascadeOnDelete();
            $table->primary(['central_coupon_id', 'country_id']);
        });
    }
};
