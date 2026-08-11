<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_country', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            // References central countries.id — tenant DB does not have a countries table,
            // so we purposely do not constrain this FK.
            $table->unsignedBigInteger('country_id');
            // Denormalised snapshot so tenant UI can display without cross-DB joins.
            $table->string('iso2', 2)->nullable();
            $table->string('name')->nullable();
            $table->string('flag_emoji', 10)->nullable();
            // Tenant-controlled toggle. Rows exist for every country the central template
            // allows; the tenant can turn individual ones off without losing the "allowed"
            // relationship, so we can show an accurate picker.
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['theme_id', 'country_id']);
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_country');
    }
};
