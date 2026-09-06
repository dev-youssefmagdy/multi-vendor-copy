<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * JSON-locale translation keys (Laravel's __('Full sentence.') convention)
 * can be arbitrarily long source text, not a short dotted key, and were
 * being truncated by the original 255-char `key` column. Since MySQL can't
 * put a unique index on an unbounded TEXT column, uniqueness now lives on a
 * fixed-length `key_hash` (sha256 of `key`) instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('translation_overrides', function (Blueprint $table) {
            // MySQL uses the (language_id, key) unique index to back the
            // language_id foreign key, so a plain index must replace it
            // before that unique index can be dropped.
            $table->index('language_id', 'translation_overrides_language_id_index');
        });

        Schema::table('translation_overrides', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'key']);
            $table->text('key')->change();
            $table->char('key_hash', 64)->nullable()->after('key');
        });

        DB::table('translation_overrides')->orderBy('id')->each(function ($row) {
            DB::table('translation_overrides')
                ->where('id', $row->id)
                ->update(['key_hash' => hash('sha256', $row->key)]);
        });

        Schema::table('translation_overrides', function (Blueprint $table) {
            $table->char('key_hash', 64)->nullable(false)->change();
            $table->unique(['language_id', 'key_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('translation_overrides', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'key_hash']);
            $table->dropColumn('key_hash');
            $table->string('key')->change();
            $table->unique(['language_id', 'key']);
            $table->dropIndex('translation_overrides_language_id_index');
        });
    }
};
