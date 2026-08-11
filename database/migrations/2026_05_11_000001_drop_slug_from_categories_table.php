<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Backfill existing slug values into translations (field=slug) for the
        // default language to avoid data loss when the column is dropped.
        if (Schema::hasColumn('categories', 'slug')) {
            $defaultLanguageId = DB::table('languages')->where('is_default', true)->value('id')
                ?? DB::table('languages')->orderBy('id')->value('id');

            if ($defaultLanguageId) {
                DB::table('categories')
                    ->select('id', 'slug')
                    ->whereNotNull('slug')
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($defaultLanguageId): void {
                        foreach ($rows as $row) {
                            $exists = DB::table('translations')
                                ->where('translatable_type', Category::class)
                                ->where('translatable_id', $row->id)
                                ->where('language_id', $defaultLanguageId)
                                ->where('field', 'slug')
                                ->exists();

                            if (!$exists) {
                                DB::table('translations')->insert([
                                    'translatable_type' => Category::class,
                                    'translatable_id' => $row->id,
                                    'language_id' => $defaultLanguageId,
                                    'field' => 'slug',
                                    'value' => $row->slug,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    });
            }
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->unique()->after('parent_id');
        });
    }
};
