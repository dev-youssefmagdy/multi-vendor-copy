<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Repositories\AppSettingRepository;
use Illuminate\Console\Command;

class ArchiveOutOfStockProductsCommand extends Command
{
    protected $signature = 'products:archive-out-of-stock';

    protected $description = 'Archive (and hide from tenants) products that have been out of stock for longer than the configured number of days';

    public function handle(AppSettingRepository $settings): int
    {
        $days = (int) ($settings->getByKeys(['out_of_stock_archive_days'])->get('out_of_stock_archive_days')?->value ?? 0);

        if ($days <= 0) {
            $this->info('Out-of-stock auto-archiving is disabled (no positive day threshold configured).');

            return self::SUCCESS;
        }

        $threshold = now()->subDays($days);

        $products = Product::query()
            ->whereNotNull('out_of_stock_at')
            ->where('out_of_stock_at', '<=', $threshold)
            ->where('status', '!=', ProductStatus::Archived)
            ->get();

        foreach ($products as $product) {
            $product->update(['status' => ProductStatus::Archived]);
            $this->info("Archived out-of-stock product #{$product->id} ({$product->sku}).");
        }

        $this->info("Archived {$products->count()} out-of-stock product(s).");

        return self::SUCCESS;
    }
}
