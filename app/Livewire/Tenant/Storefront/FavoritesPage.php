<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Favorite;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class FavoritesPage extends Component
{
    use HasStorefrontLayout;

    public function render()
    {
        $storeName = app(StorefrontRepository::class)->storeName();
        $customer = auth('storefront')->user();

        $favoriteProducts = $customer
            ? Favorite::with('product.centralProduct')
                ->where('customer_id', $customer->id)
                ->latest()
                ->get()
                ->pluck('product')
                ->filter()
                ->values()
            : collect();

        return view($this->pageView('favorites'), array_merge($this->sharedData(), [
            'favoriteProducts' => $favoriteProducts,
        ]))
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Favorites') . " — {$storeName}" : __('Favorites'),
                'metaDescription' => '',
            ]);
    }
}
