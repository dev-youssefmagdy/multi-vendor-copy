<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_color_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug');
            $table->string('variable_key');
            $table->string('value');
            $table->timestamps();

            $table->unique(['theme_slug', 'variable_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_color_defaults');
    }
};
