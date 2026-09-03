<?php

use App\Enums\TranslationSource;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `source` column to `translations` and `translation_overrides` so AI
 * translation jobs can tell a never-reviewed placeholder value apart from
 * one a vendor deliberately typed, and only ever overwrite the former.
 * Without this, TranslateStoreJob had to guess "already translated?" by
 * comparing text (`target === source`), which silently broke for correctly
 * untranslated brand terms (see ai_translation.static_keys_chunk_completed
 * logs: items_translated stayed 0 forever because the guess never learned
 * the term was reviewed) and re-billed OpenAI for the same keys every run.
 *
 * Backfill is intentionally conservative: every existing row is marked
 * Manual (protected from being overwritten) since we cannot know in
 * hindsight whether it was AI-written or vendor-typed. The one exception is
 * Product/ProductVariant translation rows belonging to products that were
 * never flagged has_custom_translations=true — those are marked Ai, since
 * that flag is this app's existing signal that nobody has hand-edited the
 * product's translations, so future TranslateStoreJob runs may keep them
 * fresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->string('source', 20)->default(TranslationSource::Manual->value)->after('value');
        });

        Schema::table('translation_overrides', function (Blueprint $table) {
            $table->string('source', 20)->default(TranslationSource::Manual->value)->after('value');
        });

        $autoManagedProductIds = DB::table('products')
            ->where('has_custom_translations', false)
            ->pluck('id');

        if ($autoManagedProductIds->isNotEmpty()) {
            DB::table('translations')
                ->where('translatable_type', (new Product())->getMorphClass())
                ->whereIn('translatable_id', $autoManagedProductIds)
                ->update(['source' => TranslationSource::Ai->value]);

            $autoManagedVariantIds = DB::table('product_variants')
                ->whereIn('product_id', $autoManagedProductIds)
                ->pluck('id');

            if ($autoManagedVariantIds->isNotEmpty()) {
                DB::table('translations')
                    ->where('translatable_type', (new ProductVariant())->getMorphClass())
                    ->whereIn('translatable_id', $autoManagedVariantIds)
                    ->update(['source' => TranslationSource::Ai->value]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('translation_overrides', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
