<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->morphs('translatable');
            $table->string('field');
            $table->longText('value');
            $table->timestamps();

            $table->unique([
                'language_id',
                'translatable_type',
                'translatable_id',
                'field',
            ], 'translations_unique_field_per_language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};