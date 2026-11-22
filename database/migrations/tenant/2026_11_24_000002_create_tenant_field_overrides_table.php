<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan §4.2a: a tenant keeps ONLY the (field, locale) pairs of a central entity that a vendor asked to change
 * and the platform approved. Every other field / locale keeps following central.
 *
 *  entity_type   central_product (later: central_variant, central_category, …)
 *  entity_id     CENTRAL id
 *  field/locale  whitelisted per entity (App\Support\Catalog\EditableCentralFields); locale NULL = not translatable
 *  value         the approved value (wins over central)
 *  central_value_at_approval  central's value when approved ("central changed since" hint)
 *  edit_request_id / approved_at / approved_by  central product_edit_requests.id / review time / central admin id
 *
 * `locale_key` = IFNULL(locale, '') so the natural key is unique also for non-translatable fields.
 * Written by the central admin approve / reset (App\Services\Catalog\ProductEditRequestReview) and by
 * `catalog:dedup-migrate field_overrides`; read by the products sync (only overridden pairs are kept) and, from
 * phase 5, by the merged product read. Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenant_field_overrides')) {
            return;
        }

        Schema::create('tenant_field_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id');
            $table->string('field', 64);
            $table->string('locale', 10)->nullable();
            $table->string('locale_key', 10)->storedAs("IFNULL(`locale`, '')");
            $table->longText('value')->nullable();
            $table->longText('central_value_at_approval')->nullable();
            $table->unsignedBigInteger('edit_request_id')->nullable();
            $table->timestamp('approved_at');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'field', 'locale_key'], 'tfo_field_unique');
            $table->index('edit_request_id', 'tfo_edit_request_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_field_overrides');
    }
};
