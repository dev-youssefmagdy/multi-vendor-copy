<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_ai_translation_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('central_language_id')->index();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('gateway_code')->nullable();
            $table->string('transaction_uuid')->nullable()->index();
            $table->timestamp('translated_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('central_language_id')->references('id')->on('languages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_ai_translation_purchases');
    }
};
