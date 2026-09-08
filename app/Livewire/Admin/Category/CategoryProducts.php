<?php

namespace App\Livewire\Admin\Category;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Category;
use Livewire\Component;

class CategoryProducts extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithAdminUi;

    public Category $category;

    public function mount(Category $category): void
    {
        $this->authorizePermission('catalog.categories.manage');
        $this->category = $category;
    }

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('catalog.categories.manage');

        foreach ($orderedIds as $index => $productId) {
            $this->category->products()->updateExistingPivot((int) $productId, ['sort_order' => $index]);
        }

        $this->toast('Product order saved.');
    }

    public function render()
    {
        return view('livewire.admin.category.category-products', [
            'products' => $this->category->products()->get(),
        ]);
    }
}
