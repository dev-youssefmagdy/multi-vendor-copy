<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class FavoritesPage extends Component
{
    use HasStorefrontLayout;

    public function render()
    {
        $storeName = app(StorefrontRepository::class)->storeName();

        return view($this->pageView('favorites'), $this->sharedData())
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Favorites') . " — {$storeName}" : __('Favorites'),
                'metaDescription' => '',
            ]);
    }
}
