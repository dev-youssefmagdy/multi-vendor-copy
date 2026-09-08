<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class OrderTrackingPage extends Component
{
    use HasStorefrontLayout;

    public string $uuid = '';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    function showDetails(): void
    {
        $this->redirect(route('tenant.storefront.order-status', ['uuid' => $this->uuid]));
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (!$order) {
            abort(404);
        }

        $data = array_merge($this->sharedData(), [
            'order' => $order,
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('order-tracking'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Track Order') . " — {$storeName}" : __('Track Order'),
                'metaDescription' => '',
                'canonicalUrl' => route('tenant.storefront.order-tracking', $this->uuid),
            ]);
    }
}

