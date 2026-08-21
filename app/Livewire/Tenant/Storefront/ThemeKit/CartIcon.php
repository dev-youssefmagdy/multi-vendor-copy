<?php

namespace App\Livewire\Tenant\Storefront\ThemeKit;

use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Drop-in cart icon with live badge for vendor Blade themes.
 * Usage: @livewire('storefront.cart-icon')
 */
class CartIcon extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->count = app(StorefrontRepository::class)->cartCount();
    }

    #[On('cart-updated')]
    #[On('cartUpdated')]
    public function refresh(): void
    {
        $this->count = app(StorefrontRepository::class)->cartCount();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.tenant.storefront.theme-kit.cart-icon');
    }
}
