<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tenant_theme_colors');
    }

    public function down(): void
    {
        // No-op: theme color customisation system has been removed.
    }
};
