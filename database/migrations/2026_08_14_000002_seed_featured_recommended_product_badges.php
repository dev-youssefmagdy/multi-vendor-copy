<?php

use App\Models\ProductBadge;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        ProductBadge::query()->firstOrCreate(['text' => 'featured'], ['active' => true]);
        ProductBadge::query()->firstOrCreate(['text' => 'recommended'], ['active' => true]);
    }

    public function down(): void
    {
        ProductBadge::query()->whereIn('text', ['featured', 'recommended'])->delete();
    }
};
