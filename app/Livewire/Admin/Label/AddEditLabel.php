<?php

namespace App\Livewire\Admin\Label;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
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
    /** @var array<string, string> locale code => translated text */
    public array $translations = [];
    public string $activeLocale = '';
    public string $icon = '';
    public bool $active = true;
    public int $sortOrder = 0;

    public function mount(?ProductLabel $label = null): void
    {
        $this->authorizePermission('catalog.labels.manage');

        if (!$label?->exists) {
            $this->sortOrder = (int) ProductLabel::query()->max('sort_order') + 1;
            return;
        }

        $this->labelId = $label->id;
        $this->text = $label->text;
        $this->translations = (array) $label->translations;
        $this->icon = (string) $label->icon;
        $this->active = $label->active;
        $this->sortOrder = $label->sort_order;
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(CentralCatalogTenantSyncService $sync): mixed
    {
        $this->authorizePermission('catalog.labels.manage');

        $validated = $this->validate([
            'text' => ['required', 'string', 'max:120'],
            'translations' => ['array'],
            'translations.*' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', Rule::in(array_keys(ProductLabelIcons::all()))],
            'active' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
        ]);

        $label = ProductLabel::query()->updateOrCreate(['id' => $this->labelId], [
            'text' => trim($validated['text']),
            'translations' => collect($validated['translations'] ?? [])
                ->map(fn($t) => trim((string) $t))->filter()->all() ?: null,
            'icon' => $validated['icon'] ?: null,
            'active' => $validated['active'],
            'sort_order' => $validated['sortOrder'],
        ]);

        $sync->syncLabels();

        session()->flash('status', $this->labelId ? 'Label updated successfully.' : 'Label created successfully.');

        return redirect()->route('admin.labels.edit', $label);
    }

    public function render()
    {
        $languages = \App\Models\Language::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('sort_order')->get(['code', 'name', 'native_name', 'is_default']);
        $this->activeLocale = $languages->contains('code', $this->activeLocale) ? $this->activeLocale : (string) $languages->first()?->code;

        return view('livewire.admin.label.add-edit-label', [
            'pageTitle' => $this->labelId ? 'Edit Label' : 'Add Label',
            'languages' => $languages,
            'icons' => ProductLabelIcons::all(),
        ]);
    }
}
