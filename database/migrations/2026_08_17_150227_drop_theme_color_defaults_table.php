<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('theme_color_defaults');
    }

    public function down(): void
    {
        // No-op: theme color customisation system has been removed.
    }
};
