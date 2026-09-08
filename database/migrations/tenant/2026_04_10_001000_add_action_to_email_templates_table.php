<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('action')->nullable()->after('name');
            $table->index('action');
        });

        foreach ($this->templateMap() as $name => $action) {
            DB::table('email_templates')
                ->where('name', $name)
                ->update(['action' => $action]);
        }
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropColumn('action');
        });
    }

    private function templateMap(): array
    {
        return [
            'Customer Order Confirmation' => 'tenant.order_confirmation',
            'Order Processing Update' => 'tenant.order_processing',
            'Shipping Update' => 'tenant.shipping_update',
            'Order Delivered Confirmation' => 'tenant.order_delivered',
            'Order Cancelled Notice' => 'tenant.order_cancelled',
            'Payment Receipt' => 'tenant.payment_receipt',
            'Refund Processed' => 'tenant.refund_processed',
            'Wallet Transaction Receipt' => 'tenant.wallet_transaction_receipt',
            'Store Subscription Activated' => 'tenant.subscription_activated',
            'Store Subscription Renewal Reminder' => 'tenant.subscription_renewal_reminder',
        ];
    }
};
