<?php

namespace App\Livewire\Tenant\Storefront\Concerns;

use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Support\Arr;
use Livewire\Attributes\Renderless;

/**
 * Current display data for favorites kept in the browser. localStorage holds a snapshot from
 * the moment the heart was clicked, so its image URLs and prices go stale (e.g. every
 * jpg/png URL died when images:convert-avif replaced the files with .avif ones).
 */
trait ProvidesFavoriteDisplayData
{
    /** Same visibility as cart data: active products only. Renderless so the JS-painted grid is not morphed away. */
    #[Renderless]
    public function favoriteDisplayData(array $slugs, array $ids = []): array
    {
        $slugs = collect($slugs)->filter(fn ($s) => is_string($s) && $s !== '')->unique()->take(100)->values();
        $ids = collect($ids)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->take(100)->values();
        if ($slugs->isEmpty() && $ids->isEmpty()) {
            return [];
        }

        $repository = app(StorefrontRepository::class);
        $currency = $repository->currentCurrency();

        // Guests can call this with up to 200 products: batch-load what the payload reads.
        return Product::with($repository->productRelations())
            ->where('active', true)
            ->where(fn ($q) => $q->whereIn('slug', $slugs)->orWhereIn('id', $ids))
            ->get()
            ->each(fn (Product $product) => $product->variants->each->setRelation('product', $product))
            ->map(fn (Product $product) => ['id' => $product->id] + Arr::except($product->storefrontFavPayload($currency), ['added']))
            ->values()
            ->all();
    }
}
