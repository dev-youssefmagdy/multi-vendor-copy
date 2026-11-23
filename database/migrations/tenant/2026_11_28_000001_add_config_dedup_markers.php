<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 6 (config entities): what a tenant owns about CENTRAL config once its `catalog_source.<section>`
 * is `central` (App\Repositories\Tenant\Catalog\ConfigCatalog).
 *
 * Decision (plan §12, phase 6): config rows keep their tenant id as an IDENTITY row (many local FKs: `translations`,
 * `theme_id` children, `orders.payment_gateway_id`, `theme_country`), so the override store is the row itself plus a
 * marker for the three entities where the vendor edits central-owned text:
 *
 *  - `settings.central_overridden`                       1 = the vendor's `value` wins over central's `app_settings`
 *  - `email_templates.central_overridden`               1 = the vendor's subject / body / is_active win
 *  - `email_template_translations.central_overridden`   1 = the vendor's locale text wins
 *
 * Everything else a vendor owns (`is_active`, `is_default`, `use_own`, `required_values`, `mode`, theme choice,
 * `theme_country.is_enabled`) already is a tenant-only column. NULL / 0 = inherit central. Nothing reads the markers
 * until a tenant's flag is `central`. Idempotent, additive (nullable-free default 0 = INSTANT), down() drops only these.
 */
return new class extends Migration {
    private const TABLES = ['settings', 'email_templates', 'email_template_translations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'central_overridden')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('central_overridden')->default(false);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'central_overridden')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('central_overridden');
                });
            }
        }
    }
};
