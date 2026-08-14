<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_theme_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            $table->string('variable_key');
            $table->string('value');
            $table->timestamps();

            $table->unique(['theme_id', 'variable_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_theme_colors');
    }
};
