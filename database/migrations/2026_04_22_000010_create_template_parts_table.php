<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('template_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            // header | footer (extendable — kept as string rather than enum).
            $table->string('type', 32);
            $table->string('name');
            $table->string('slug');
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            // One default per (template, type); used when no active variant is chosen.
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['template_id', 'slug']);
            $table->index(['template_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_parts');
    }
};
