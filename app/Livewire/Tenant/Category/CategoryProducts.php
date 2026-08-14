<?php

namespace App\Livewire\Tenant\Category;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Category;
use Livewire\Component;

class CategoryProducts extends Component
{
    use InteractsWithTenantUi;

    public Category $category;

    public function mount(Category $category): void
    {
        $this->category = $category;
    }

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $productId) {
            $this->category->products()->updateExistingPivot((int) $productId, ['sort_order' => $index]);
        }

        $this->toast('Product order saved.');
    }

    public function render()
    {
        return view('livewire.tenant.category.category-products', [
            'products' => $this->category->products()->get(),
        ]);
    }
}
