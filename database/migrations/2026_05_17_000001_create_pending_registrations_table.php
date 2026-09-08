<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->unsignedBigInteger('catalog_id')->nullable();
            $table->boolean('all_catalogs')->default(true);
            $table->json('payment_data')->nullable(); // gateway_code, transaction_id, amount, raw_response
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
