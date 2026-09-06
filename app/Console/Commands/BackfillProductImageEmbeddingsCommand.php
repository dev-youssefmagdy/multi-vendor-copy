<?php

namespace App\Console\Commands;

use App\Jobs\EmbedProductImagesJob;
use App\Models\Product;
use App\Models\ProductImageEmbedding;
use Illuminate\Console\Command;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
// One-off backfill for the whole catalog. Ongoing/incremental embedding for
// individual products is handled by ProductFileObserver and
// CentralCatalogSyncObserver (dispatched per product on create/image change),
// so this command only needs to cover products that have never been embedded.
class BackfillProductImageEmbeddingsCommand extends Command
{
    protected $signature = 'image-search:backfill {--force : Re-embed products that already have embeddings}';

    protected $description = 'Queue image-embedding jobs for every product that has no embeddings yet (primary, gallery, and variant images).';

    public function handle(): int
    {
        $count = 0;
        $force = (bool) $this->option('force');

        $alreadyEmbeddedIds = $force
            ? []
            : ProductImageEmbedding::query()->distinct()->pluck('product_id')->all();

        Product::query()
            ->select('id')
            ->when(!$force, fn($query) => $query->whereNotIn('id', $alreadyEmbeddedIds))
            ->chunkById(200, function ($products) use (&$count) {
                foreach ($products as $product) {
                    EmbedProductImagesJob::dispatch($product->id);
                    $count++;
                }
            });

        $this->info("Queued image embedding for {$count} products.");

        return self::SUCCESS;
    }
}
