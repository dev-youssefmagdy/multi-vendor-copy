<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            // Points at the central template_parts.id the row was synced from
            // (nullable because tenants can author entirely new parts locally).
            $table->unsignedBigInteger('central_part_id')->nullable();
            $table->string('type', 32);
            $table->string('name');
            $table->string('slug');
            $table->longText('content');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            // Set to true when the tenant edits the part — further central syncs
            // won't overwrite customised rows.
            $table->boolean('is_customized')->default(false);
            $table->timestamps();

            $table->unique(['theme_id', 'slug']);
            $table->index(['theme_id', 'type', 'is_active']);
            $table->index('central_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_parts');
    }
};
