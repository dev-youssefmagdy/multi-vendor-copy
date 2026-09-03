<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('language_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_language_id')->index();
            $table->string('language_code');
            $table->string('language_name');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('gateway_code')->nullable();
            $table->string('transaction_uuid')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique('central_language_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_purchases');
    }
};
