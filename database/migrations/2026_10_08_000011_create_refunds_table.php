<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('tenant_id', 36)->index();
            $table->string('order_number')->index();
            $table->foreignId('return_request_id')->nullable()->constrained('return_requests')->nullOnDelete();
            $table->string('source', 20);
            $table->string('reason', 255);
            $table->string('currency', 3)->default('USD');
            $table->decimal('items_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('return_fee', 12, 2)->default(0);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('gateway')->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->string('refund_method', 20);
            $table->string('gateway_refund_id')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->string('requested_by_type', 20)->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->string('approved_by_type', 20)->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'status']);
            $table->index('source');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
