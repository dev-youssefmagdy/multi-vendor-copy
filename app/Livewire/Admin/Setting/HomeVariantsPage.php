<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\AdminPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\HomeVariant;
use App\Services\Tenant\PageBuilder\SectionRegistry;
use App\Services\Tenant\TemplateRegistryService;
use App\Support\ColorPresets;
use App\Support\ThemeColorKeys;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HomeVariantsPage extends AdminPage
{
    use InteractsWithAdminUi;

    public bool $modalOpen = false;
    public ?int $editingId = null;

    public string $themeSlug = '';
    public string $name = '';
    public string $key = '';
    public string $description = '';
    public string $preset = '';
    /** @var array<string, string> */
    public array $colors = [];
    /** @var array<int, string> selected section keys, in order */
    public array $sections = [];
    public string $view = '';
    public bool $isDefault = false;
    public bool $isActive = true;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Home Page Variants',
            'badge' => 'Appearance',
            'description' => 'Create named home page layouts per theme — different section arrangements and colors that tenants can pick, optionally per country.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.setting.home-variants-page';
    }

    protected function pageData(): array
    {
        $themes = array_keys(app(TemplateRegistryService::class)->all());

        $variants = HomeVariant::query()
            ->orderBy('theme_slug')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn(HomeVariant $v) => [
                'id' => $v->id,
                'theme_slug' => $v->theme_slug,
                'name' => $v->name,
                'key' => $v->key,
                'is_default' => $v->is_default,
                'is_active' => $v->is_active,
                'section_count' => $v->sections ? count($v->sections) : null,
                'swatches' => array_values(($v->colors ?? []) ?: []),
            ]);

        return array_merge($this->pageMeta(), [
            'themes' => $themes,
            'variants' => $variants,
            'presets' => array_keys(ColorPresets::all()),
            'colorKeyLabels' => collect(ThemeColorKeys::all())
                ->mapWithKeys(fn($k) => [$k => ThemeColorKeys::label($k)])
                ->all(),
            'sectionLabels' => $this->themeSlug ? SectionRegistry::labelsFor($this->themeSlug, 'home') : [],
        ]);
    }

    public function updatedThemeSlug(): void
    {
        $labels = SectionRegistry::labelsFor($this->themeSlug, 'home');
        $this->sections = array_keys($labels);
        $this->resetColorsToStrategyDefault();
    }

    public function updatedName(): void
    {
        if (!$this->editingId) {
            $this->key = Str::slug($this->name);
        }
    }

    public function applyPreset(): void
    {
        if ($this->preset && $values = ColorPresets::get($this->preset)) {
            $this->colors = $values;
        }
    }

    protected function resetColorsToStrategyDefault(): void
    {
        if (!$this->themeSlug) {
            $this->colors = [];
            return;
        }

        $strategy = app(TemplateRegistryService::class)->resolve($this->themeSlug);
        $this->colors = $strategy->colorDefaults();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $variant = HomeVariant::query()->findOrFail($id);

        $this->editingId = $variant->id;
        $this->themeSlug = $variant->theme_slug;
        $this->name = $variant->name;
        $this->key = $variant->key;
        $this->description = (string) $variant->description;
        $this->preset = '';
        $this->view = (string) $variant->view;
        $this->isDefault = $variant->is_default;
        $this->isActive = $variant->is_active;

        $labels = SectionRegistry::labelsFor($this->themeSlug, 'home');
        $this->sections = $variant->sections ?? array_keys($labels);

        $strategy = app(TemplateRegistryService::class)->resolve($this->themeSlug);
        $this->colors = array_merge($strategy->colorDefaults(), $variant->colors ?? []);

        $this->modalOpen = true;
    }

    public function toggleSection(string $key): void
    {
        if (in_array($key, $this->sections, true)) {
            $this->sections = array_values(array_diff($this->sections, [$key]));
        } else {
            $this->sections[] = $key;
        }
    }

    public function updateSectionOrder(array $orderedKeys): void
    {
        $this->sections = array_values(array_intersect($orderedKeys, $this->sections));
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->themeSlug = array_key_first(app(TemplateRegistryService::class)->all()) ?? '';
        $this->name = '';
        $this->key = '';
        $this->description = '';
        $this->preset = '';
        $this->view = '';
        $this->isDefault = false;
        $this->isActive = true;
        $this->sections = $this->themeSlug ? array_keys(SectionRegistry::labelsFor($this->themeSlug, 'home')) : [];
        $this->resetColorsToStrategyDefault();
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorizePermission('settings.home-variants.manage');

        $rules = [
            'themeSlug' => ['required', 'string', Rule::in(array_keys(app(TemplateRegistryService::class)->all()))],
            'name' => ['required', 'string', 'max:100'],
            'key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('home_variants', 'key')
                    ->where('theme_slug', $this->themeSlug)
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'view' => ['nullable', 'string', 'max:150'],
        ];
        foreach (array_keys($this->colors) as $colorKey) {
            $rules["colors.{$colorKey}"] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $this->validate($rules);

        $labels = SectionRegistry::labelsFor($this->themeSlug, 'home');
        $sections = array_values(array_intersect($this->sections, array_keys($labels)));
        // null sections = inherit tenant/default order; only store when it differs from the registry order.
        $sectionsToStore = ($sections !== array_keys($labels)) ? $sections : null;

        $payload = [
            'theme_slug' => $this->themeSlug,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'sections' => $sectionsToStore,
            'colors' => $this->colors,
            'view' => $this->view ?: null,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
        ];

        if ($this->isDefault) {
            HomeVariant::query()
                ->where('theme_slug', $this->themeSlug)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->update(['is_default' => false]);
        }

        if ($this->editingId) {
            HomeVariant::query()->findOrFail($this->editingId)->update($payload);
        } else {
            HomeVariant::query()->create($payload);
        }

        $this->closeModal();
        $this->toast('Home page variant saved successfully.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmAction('delete', [$id], [
            'title' => 'Delete this variant?',
            'text' => 'Tenants currently using it will fall back to their theme\'s default variant.',
            'confirmButtonText' => 'Delete variant',
        ]);
    }

    public function delete(int $id): void
    {
        $this->authorizePermission('settings.home-variants.manage');

        $variant = HomeVariant::query()->findOrFail($id);
        if ($variant->is_default) {
            $this->toast('The default variant for a theme cannot be deleted.', 'error');
            return;
        }

        $variant->delete();
        $this->toast('Home page variant deleted.');
    }
}
