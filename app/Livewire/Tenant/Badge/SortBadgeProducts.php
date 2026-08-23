<?php

namespace App\Livewire\Tenant\Badge;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\ProductBadge;
use Illuminate\Support\Facades\Request;
use Livewire\Component;

class SortBadgeProducts extends Component
{
    use InteractsWithTenantUi;

    public ProductBadge $badge;

    public ?int $activeCountryId = null;

    public function mount(ProductBadge $badge): void
    {
        $this->badge = $badge;
        $this->activeCountryId = Request::integer('country_id') ?: null;
    }

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $productId) {
            \DB::table('product_badge_product')
                ->where('product_badge_id', $this->badge->id)
                ->where('product_id', (int) $productId)
                ->when($this->activeCountryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $this->activeCountryId))
                ->update(['sort_order' => $index]);
        }

        $this->toast('Product order saved.');
    }

    public function render()
    {
        return view('livewire.tenant.badge.sort-badge-products', [
            'products' => $this->badge->productsForCountry($this->activeCountryId)->get(),
        ]);
    }
}
