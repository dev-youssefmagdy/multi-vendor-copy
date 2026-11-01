<?php

namespace App\Livewire\Admin\Label;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\Product;
use App\Models\ProductLabel;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use App\Support\ProductLabelIcons;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditLabel extends Component
{
    use AuthorizesAdminPermissions;

    public ?int $labelId = null;
    public string $text = '';
    public string $icon = '';
    public bool $active = true;
    public int $sortOrder = 0;
    /** @var array<int, int|string> */
    public array $productIds = [];

    public function mount(?ProductLabel $label = null): void
    {
        $this->authorizePermission('catalog.labels.manage');

        if (!$label?->exists) {
            $this->sortOrder = (int) ProductLabel::query()->max('sort_order') + 1;
            return;
        }

        $this->labelId = $label->id;
        $this->text = $label->text;
        $this->icon = (string) $label->icon;
        $this->active = $label->active;
        $this->sortOrder = $label->sort_order;
        $this->productIds = $label->products()->pluck('products.id')->map(fn($id) => (string) $id)->all();
    }

    public function save(CentralCatalogTenantSyncService $sync): mixed
    {
        $this->authorizePermission('catalog.labels.manage');

        $validated = $this->validate([
            'text' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', Rule::in(array_keys(ProductLabelIcons::all()))],
            'active' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
            'productIds' => ['array'],
            'productIds.*' => ['integer', 'exists:products,id'],
        ]);

        // One label per product: reject products that already carry a different label.
        $taken = $this->takenProductIds();
        if (array_intersect(array_map('intval', $validated['productIds'] ?? []), $taken)) {
            $this->addError('productIds', 'Some selected products already have another label.');
            return null;
        }

        $label = ProductLabel::query()->updateOrCreate(['id' => $this->labelId], [
            'text' => trim($validated['text']),
            'icon' => $validated['icon'] ?: null,
            'active' => $validated['active'],
            'sort_order' => $validated['sortOrder'],
        ]);

        $label->products()->sync(array_map('intval', array_filter($validated['productIds'] ?? [], filled(...))));

        $sync->syncLabels();

        session()->flash('status', $this->labelId ? 'Label updated successfully.' : 'Label created successfully.');

        return redirect()->route('admin.labels.edit', $label);
    }

    /** @return array<int, int> products already assigned to a label other than this one */
    protected function takenProductIds(): array
    {
        return \DB::table('product_label_product')
            ->when($this->labelId, fn($q) => $q->where('product_label_id', '!=', $this->labelId))
            ->pluck('product_id')->map(fn($id) => (int) $id)->all();
    }

    public function render()
    {
        return view('livewire.admin.label.add-edit-label', [
            'pageTitle' => $this->labelId ? 'Edit Label' : 'Add Label',
            'icons' => ProductLabelIcons::all(),
            'products' => Product::query()->with('translations.language')->whereNotIn('id', $this->takenProductIds())->orderBy('id')->get(),
        ]);
    }
}
