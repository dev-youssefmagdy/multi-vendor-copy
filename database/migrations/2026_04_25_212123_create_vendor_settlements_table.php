<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('order_id');   // tenant-side order PK
            $table->string('order_uuid');
            $table->string('invoice_number')->unique();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_email')->nullable();
            $table->decimal('product_cost', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('gateway_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('gateway_code')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status', 50)->default('paid');
            $table->json('meta')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_settlements');
    }
};
