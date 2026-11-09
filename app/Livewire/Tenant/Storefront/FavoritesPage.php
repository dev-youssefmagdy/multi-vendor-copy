<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Livewire\Tenant\Storefront\Concerns\ProvidesFavoriteDisplayData;
use App\Models\Tenant\Favorite;
use App\Repositories\Tenant\StorefrontRepository;
use App\Models\Tenant\Product;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class FavoritesPage extends Component
{
    use HasStorefrontLayout;
    use ProvidesFavoriteDisplayData;

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $storeName = $repo->storeName();
        $customer = auth('storefront')->user();

        $favoriteProducts = $customer
            ? Favorite::with(['product.centralProduct', 'product.variants.centralVariant'])
                ->where('customer_id', $customer->id)
                ->latest()
                ->get()
                ->pluck('product')
                ->filter()
                ->values()
            : collect();

        $currency = $repo->currentCurrency();
        $symbol = data_get($currency, 'symbol', '$');
        $rate = (float) data_get($currency, 'conversion_rate', 1.0);
        $cartData = $favoriteProducts
            ->mapWithKeys(fn ($product) => [$product->slug => $product->storefrontCartData($symbol, $rate)])
            ->all();

        return view($this->pageView('favorites'), array_merge($this->sharedData(), [
            'favoriteProducts' => $favoriteProducts,
            'favoriteCartData' => $cartData,
        ]))
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Favorites') . " — {$storeName}" : __('Favorites'),
                'metaDescription' => '',
            ]);
    }

    /**
     * Cart data for favorites kept in the browser (localStorage), which may not exist in the
     * customer's DB favorites. Renderless so the JS-painted grid is not morphed away.
     */
    #[Renderless]
    public function cartData(array $slugs): array
    {
        $slugs = collect($slugs)->filter(fn ($s) => is_string($s) && $s !== '')->unique()->take(100)->values();
        if ($slugs->isEmpty()) {
            return [];
        }

        $currency = app(StorefrontRepository::class)->currentCurrency();
        $symbol = data_get($currency, 'symbol', '$');
        $rate = (float) data_get($currency, 'conversion_rate', 1.0);

        return Product::with(['centralProduct', 'variants.centralVariant'])
            ->where('active', true)
            ->whereIn('slug', $slugs)
            ->get()
            ->mapWithKeys(fn ($product) => [$product->slug => $product->storefrontCartData($symbol, $rate)])
            ->all();
    }
}
