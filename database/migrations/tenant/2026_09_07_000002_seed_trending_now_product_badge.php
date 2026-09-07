<?php

use App\Models\Tenant\ProductBadge;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        ProductBadge::query()->firstOrCreate(['text' => 'trending-now'], ['active' => true]);
    }

    public function down(): void
    {
        ProductBadge::query()->where('text', 'trending-now')->delete();
    }
};
