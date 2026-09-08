<?php

namespace App\Observers;

use App\Jobs\EmbedProductImagesJob;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductVariant;

// Keeps product image embeddings in sync whenever a product/variant image is
// added or removed, so image search reflects the catalog without needing a
// manual `image-search:backfill` run after every upload.
class ProductFileObserver
{
    public function saved(File $file): void
    {
        $this->dispatchForFile($file);
    }

    public function deleted(File $file): void
    {
        $this->dispatchForFile($file);
    }

    private function dispatchForFile(File $file): void
    {
        $productId = match ($file->model_type) {
            Product::class => $file->model_id,
            ProductVariant::class => ProductVariant::find($file->model_id)?->product_id,
            default => null,
        };

        if ($productId) {
            EmbedProductImagesJob::dispatch($productId);
        }
    }
}
