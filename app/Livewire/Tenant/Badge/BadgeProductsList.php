<?php

namespace App\Livewire\Tenant\Badge;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductBadge;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\Attributes\Layout;
use Livewire\Component;

class BadgeProductsList extends Component
{
    public int $badgeId;

    public string $badgeText = '';

    public string $badgeTitle = '';

    public array $selectedProducts = [];

    public ?int $filterCategoryId = null;

    public function mount(ProductBadge $badge): void
    {
        $this->badgeId    = $badge->id;
        $this->badgeText  = $badge->text;
        $this->badgeTitle = match ($badge->text) {
            'new-in'       => 'New In Products',
            'best-selling' => 'Best Selling Products',
            default        => ucwords(str_replace('-', ' ', $badge->text)) . ' Products',
        };

        // Pre-load currently assigned product IDs into the multiselect
        $this->selectedProducts = $badge->products()->pluck('products.id')->map(fn($id) => (string) $id)->all();
    }

    public function assignAllInCategory(): void
    {
        if (! $this->filterCategoryId) {
            session()->flash('status', 'Please choose a category first.');
            return;
        }

        $productIds = Product::query()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $this->filterCategoryId))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (! $productIds) {
            session()->flash('status', 'No products found in that category.');
            return;
        }

        $this->selectedProducts = array_values(array_unique(array_merge($this->selectedProducts, $productIds)));

        session()->flash('status', count($productIds) . ' products merged into the selection. Click Save to apply.');
    }

    public function save(): void
    {
        ProductBadge::query()->findOrFail($this->badgeId)
            ->products()
            ->sync($this->selectedProducts);

        session()->flash('status', 'Badge assignment saved — ' . count($this->selectedProducts) . ' products assigned.');
    }

    #[Layout('layouts.tenant')]
    public function render()
    {
        $badge = ProductBadge::query()->findOrFail($this->badgeId);

        $productOptions = Product::query()
            ->with('translations.language')
            ->get()
            ->mapWithKeys(fn ($p) => [(string) $p->id => $p->translationValue('name') ?? $p->slug])
            ->all();

        return view('livewire.tenant.badge.badge-products-list', [
            'badge'          => $badge,
            'assignedCount'  => count($this->selectedProducts),
            'catalogCount'   => Product::query()->count(),
            'categoryTree'   => app(TenantPanelRepository::class)->categoryTreeOptions(),
            'productOptions' => $productOptions,
        ]);
    }
}
