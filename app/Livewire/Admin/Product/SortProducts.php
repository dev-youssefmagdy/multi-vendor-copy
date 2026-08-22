<?php

namespace App\Livewire\Admin\Product;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Product;
use Livewire\Component;

class SortProducts extends Component
{
    use AuthorizesAdminPermissions;

    public string $search = '';

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('catalog.products.manage');

        foreach ($orderedIds as $index => $productId) {
            Product::query()->whereKey((int) $productId)->update(['order_number' => $index]);
        }

        session()->flash('status', 'Product order saved.');
    }

    public function render()
    {
        return view('livewire.admin.product.sort-products', [
            'products' => Product::query()
                ->with('translations.language')
                ->when(filled($this->search), function ($query) {
                    $search = trim($this->search);
                    $query->where(function ($nested) use ($search) {
                        $nested->where('sku', 'like', "%{$search}%")
                            ->orWhere('slug', 'like', "%{$search}%")
                            ->orWhereHas('translations', fn($t) => $t->where('field', 'name')->where('value', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('order_number')
                ->get(),
        ]);
    }
}
