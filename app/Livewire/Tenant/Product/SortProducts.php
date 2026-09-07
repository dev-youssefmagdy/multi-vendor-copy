<?php

namespace App\Livewire\Tenant\Product;

use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Product;
use Livewire\Component;

class SortProducts extends Component
{
    use InteractsWithTenantUi;

    public string $search = '';

    public function updateOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $productId) {
            Product::query()->whereKey((int) $productId)->update(['order_number' => $index]);
        }

        $this->toast('Product order saved.');
    }

    public function render()
    {
        return view('livewire.tenant.product.sort-products', [
            'products' => Product::query()
                ->with('translations.language')
                ->when(filled($this->search), function ($query) {
                    $search = trim($this->search);
                    $query->where(function ($nested) use ($search) {
                        $nested->where('slug', 'like', "%{$search}%")
                            ->orWhereHas('translations', fn($t) => $t->where('field', 'name')->where('value', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('order_number')
                ->get(),
        ]);
    }
}
