<?php

namespace App\Livewire\Tenant\Storefront\Layout;

use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDropdown extends Component
{
    public int $cartCount = 0;

    public function mount(): void
    {
        $this->cartCount = app(StorefrontRepository::class)->cartCount();
    }

    #[On('cartUpdated')]
    public function refresh(): void
    {
        $this->cartCount = app(StorefrontRepository::class)->cartCount();
    }

    public function removeItem(string $key): void
    {
        $cart = session('storefront_cart', []);
        unset($cart[$key]);
        session(['storefront_cart' => $cart]);
        $this->cartCount = app(StorefrontRepository::class)->cartCount();
        $this->dispatch('cartUpdated');
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);

        return view('livewire.tenant.storefront.layout.cart-dropdown', [
            'cartCount'       => $this->cartCount,
            'cartItems'       => $repo->cartItems(),
            'cartTotal'       => $repo->cartTotal(),
            'currentCurrency' => $repo->currentCurrency(),
        ]);
    }
}
