<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the platform admin forwarded the request to the merchant (status AwaitingMerchantReview).
     * A customer reply to an info request then goes back to the merchant instead of Pending.
     */
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->timestamp('forwarded_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn('forwarded_at');
        });
    }
};
