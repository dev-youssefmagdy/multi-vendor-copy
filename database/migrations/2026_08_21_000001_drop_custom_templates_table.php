<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('custom_templates');
    }

    public function down(): void
    {
        // Irreversible — table structure lived in 2026_08_15_000003_create_custom_templates_table.php
        // Re-run that migration manually if rollback is ever needed.
    }
};
