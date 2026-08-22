<?php

namespace App\Livewire\Tenant\Badge;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\ProductBadge;
use Livewire\Component;

class SortBadgeProducts extends Component
{
    use InteractsWithTenantUi;

    public ProductBadge $badge;

    public function mount(ProductBadge $badge): void
    {
        $this->badge = $badge;
    }

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $productId) {
            $this->badge->products()->updateExistingPivot((int) $productId, ['sort_order' => $index]);
        }

        $this->toast('Product order saved.');
    }

    public function render()
    {
        return view('livewire.tenant.badge.sort-badge-products', [
            'products' => $this->badge->products()->get(),
        ]);
    }
}
