<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_order_id')->nullable()->after('tenant_id');
            $table->string('order_uuid')->nullable()->after('tenant_order_id');
            $table->string('order_number')->nullable()->after('order_uuid');
            $table->string('customer_name')->nullable()->after('invoice_number');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('currency', 12)->default('USD')->after('customer_email');
            $table->string('payment_method')->nullable()->after('status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('pdf_disk')->nullable()->after('payment_reference');
            $table->string('pdf_path')->nullable()->after('pdf_disk');
            $table->json('meta')->nullable()->after('pdf_path');

            $table->index(['tenant_id', 'order_uuid']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'order_uuid']);
            $table->dropColumn([
                'tenant_order_id',
                'order_uuid',
                'order_number',
                'customer_name',
                'customer_email',
                'currency',
                'payment_method',
                'payment_reference',
                'pdf_disk',
                'pdf_path',
                'meta',
            ]);
        });
    }
};
