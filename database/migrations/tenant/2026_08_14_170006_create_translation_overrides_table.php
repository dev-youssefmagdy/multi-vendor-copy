<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('key');
            $table->text('value');
            $table->timestamps();

            $table->unique(['language_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_overrides');
    }
};
