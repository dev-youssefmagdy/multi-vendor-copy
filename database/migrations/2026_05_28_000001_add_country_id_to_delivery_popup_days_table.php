<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add per-country grouping to delivery_popup_days.
 *
 * country_id = NULL → global default (shown when the customer's country has no
 * specific records).
 * country_id = X   → shown only to customers whose detected country matches X.
 *
 * The old UNIQUE(day_number) constraint is dropped because the same day_number
 * can now exist once per country group.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_popup_days', function (Blueprint $table) {
            // Drop the old unique constraint on day_number alone
            $table->dropUnique(['day_number']);

            // Add nullable FK – NULL means "global / default group"
            $table->foreignId('country_id')
                ->nullable()
                ->after('id')
                ->constrained('countries')
                ->nullOnDelete();

            // Composite index so lookups by country are fast
            $table->index(['country_id', 'day_number'], 'dpd_country_day_idx');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_popup_days', function (Blueprint $table) {
            $table->dropIndex('dpd_country_day_idx');
            $table->dropConstrainedForeignId('country_id');
            $table->unique('day_number');
        });
    }
};
