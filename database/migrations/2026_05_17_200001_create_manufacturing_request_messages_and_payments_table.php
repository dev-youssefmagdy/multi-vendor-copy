<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Chat messages between admin and tenant per manufacturing request
        Schema::create('manufacturing_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_request_id')->constrained('manufacturing_requests')->cascadeOnDelete();
            $table->string('sender_type');          // 'admin' | 'tenant'
            $table->string('sender_name');           // display name snapshot
            $table->text('message');
            $table->timestamps();

            $table->index('manufacturing_request_id');
        });

        // Payment requests created by admin, paid by tenant
        Schema::create('manufacturing_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_request_id')->constrained('manufacturing_requests')->cascadeOnDelete();
            $table->string('tenant_id', 36)->index();
            $table->string('label');                     // e.g. "Deposit - 50%"
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 30)->default('pending'); // pending | paid | cancelled
            $table->string('gateway_code')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_payment_requests');
        Schema::dropIfExists('manufacturing_request_messages');
    }
};
