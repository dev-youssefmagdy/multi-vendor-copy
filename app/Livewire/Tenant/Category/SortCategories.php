<?php

namespace App\Livewire\Tenant\Category;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Category;
use Livewire\Component;

class SortCategories extends Component
{
    use InteractsWithTenantUi;

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $categoryId) {
            Category::query()->whereKey((int) $categoryId)->update(['order_number' => $index]);
        }

        $this->toast('Category order saved.');
    }

    public function render()
    {
        return view('livewire.tenant.category.sort-categories', [
            'categories' => Category::query()
                ->with('translations.language')
                ->where('active', true)
                ->orderBy('order_number')
                ->get(),
        ]);
    }
}
