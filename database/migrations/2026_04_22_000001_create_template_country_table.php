<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('template_country', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['template_id', 'country_id']);
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_country');
    }
};
