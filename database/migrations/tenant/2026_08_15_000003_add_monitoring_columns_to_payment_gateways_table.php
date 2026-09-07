<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('use_own');
            $table->string('webhook_url')->nullable()->after('connection_status');
            $table->string('webhook_status')->nullable()->after('webhook_url');
            $table->timestamp('last_synced_at')->nullable()->after('webhook_status');
            $table->timestamp('last_transaction_at')->nullable()->after('last_synced_at');
            $table->text('last_error')->nullable()->after('last_transaction_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table): void {
            $table->dropColumn([
                'is_primary',
                'webhook_url',
                'webhook_status',
                'last_synced_at',
                'last_transaction_at',
                'last_error',
            ]);
        });
    }
};
