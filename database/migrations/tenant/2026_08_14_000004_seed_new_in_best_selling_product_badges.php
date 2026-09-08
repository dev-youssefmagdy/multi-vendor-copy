<?php

use App\Models\Tenant\ProductBadge;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        ProductBadge::query()->firstOrCreate(['text' => 'new-in'], ['active' => true]);
        ProductBadge::query()->firstOrCreate(['text' => 'best-selling'], ['active' => true]);
    }

    public function down(): void
    {
        ProductBadge::query()->whereIn('text', ['new-in', 'best-selling'])->delete();
    }
};
