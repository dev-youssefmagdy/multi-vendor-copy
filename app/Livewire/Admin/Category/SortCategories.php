<?php

namespace App\Livewire\Admin\Category;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Category;
use Livewire\Component;

class SortCategories extends Component
{
    use AuthorizesAdminPermissions;

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('catalog.categories.manage');

        foreach ($orderedIds as $index => $categoryId) {
            Category::query()->whereKey((int) $categoryId)->update(['order_number' => $index]);
        }

        session()->flash('status', 'Category order saved.');
    }

    public function render()
    {
        return view('livewire.admin.category.sort-categories', [
            'categories' => Category::query()
                ->with('translations.language')
                ->orderBy('order_number')
                ->get(),
        ]);
    }
}
