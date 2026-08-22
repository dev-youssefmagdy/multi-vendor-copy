<?php

namespace App\Console\Commands;

use App\Jobs\EmbedProductImagesJob;
use App\Models\Product;
use Illuminate\Console\Command;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
// One-off backfill for the whole catalog, e.g. after extending
// ImageSearchService::backfillForProduct to also cover gallery/variant images.
class BackfillProductImageEmbeddingsCommand extends Command
{
    protected $signature = 'image-search:backfill';

    protected $description = 'Queue image-embedding jobs for every product (primary, gallery, and variant images).';

    public function handle(): int
    {
        $count = 0;

        Product::query()->select('id')->chunkById(200, function ($products) use (&$count) {
            foreach ($products as $product) {
                EmbedProductImagesJob::dispatch($product->id);
                $count++;
            }
        });

        $this->info("Queued image embedding for {$count} products.");

        return self::SUCCESS;
    }
}
