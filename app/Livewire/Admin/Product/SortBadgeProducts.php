<?php

namespace App\Livewire\Admin\Product;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\ProductBadge;
use Illuminate\Support\Facades\Request;
use Livewire\Component;

class SortBadgeProducts extends Component
{
    use AuthorizesAdminPermissions;

    public ProductBadge $badge;

    public ?int $activeCountryId = null;

    public function mount(ProductBadge $badge): void
    {
        $this->authorizePermission('catalog.badges.manage');
        $this->badge = $badge;
        $this->activeCountryId = Request::integer('country_id') ?: null;
    }

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('catalog.badges.manage');

        foreach ($orderedIds as $index => $productId) {
            \DB::table('product_badge_product')
                ->where('product_badge_id', $this->badge->id)
                ->where('product_id', (int) $productId)
                ->when($this->activeCountryId === null, fn ($q) => $q->whereNull('country_id'), fn ($q) => $q->where('country_id', $this->activeCountryId))
                ->update(['sort_order' => $index]);
        }

        session()->flash('status', 'Product order saved.');
    }

    public function render()
    {
        return view('livewire.admin.product.sort-badge-products', [
            'products' => $this->badge->productsForCountry($this->activeCountryId)->get(),
        ]);
    }
}
