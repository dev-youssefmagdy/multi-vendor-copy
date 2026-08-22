<?php

namespace App\Livewire\Admin\Product;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\ProductBadge;
use Livewire\Component;

class SortBadgeProducts extends Component
{
    use AuthorizesAdminPermissions;

    public ProductBadge $badge;

    public function mount(ProductBadge $badge): void
    {
        $this->authorizePermission('catalog.badges.manage');
        $this->badge = $badge;
    }

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('catalog.badges.manage');

        foreach ($orderedIds as $index => $productId) {
            $this->badge->products()->updateExistingPivot((int) $productId, ['sort_order' => $index]);
        }

        session()->flash('status', 'Product order saved.');
    }

    public function render()
    {
        return view('livewire.admin.product.sort-badge-products', [
            'products' => $this->badge->products()->get(),
        ]);
    }
}
