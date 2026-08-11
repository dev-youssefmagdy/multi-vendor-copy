<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class NotFoundPage extends Component
{
    use HasStorefrontLayout;

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $storeName = $repo->storeName();

        return view($this->pageView('404'), $this->sharedData())
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? "404 — {$storeName}" : '404 — Page Not Found',
                'metaDescription' => 'The page you are looking for could not be found.',
            ])
            ->withHeaders(['X-Status-Code' => 404]);
    }
}
