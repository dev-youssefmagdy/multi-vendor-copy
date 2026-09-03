<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_language_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('central_language_id')->index();
            $table->string('transaction_uuid')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('gateway_code')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'central_language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_language_purchases');
    }
};
