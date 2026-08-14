<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\ThemeColorDefault;
use App\Services\Tenant\TemplateRegistryService;
use App\Support\ThemeColorKeys;

class ThemeColorsPage extends ContentPage
{
    use InteractsWithAdminUi;

    /** @var array<string, array<string, string>> theme slug => [variable key => value] */
    public array $colors = [];

    public function mount(TemplateRegistryService $registry): void
    {
        $saved = ThemeColorDefault::query()
            ->get()
            ->groupBy('theme_slug')
            ->map(fn($rows) => $rows->pluck('value', 'variable_key')->all());

        foreach (array_keys($registry->all()) as $slug) {
            $strategy = $registry->resolve($slug);
            $this->colors[$slug] = array_merge(
                $strategy->colorDefaults(),
                $saved->get($slug, [])
            );
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Theme Colors',
            'badge' => 'Appearance',
            'description' => 'Set the global default CSS color variables for each storefront theme. Tenants can override these from their own Appearance settings.',
        ];
    }

    protected function pageData(): array
    {
        $fieldGroups = [];

        foreach ($this->colors as $slug => $values) {
            $fields = [];
            foreach (ThemeColorKeys::all() as $key) {
                $fields[] = [
                    'label' => ThemeColorKeys::label($key),
                    'model' => "colors.{$slug}.{$key}",
                    'type' => 'color',
                ];
            }

            $fieldGroups[] = [
                'title' => ucfirst($slug),
                'description' => "Default color variables for the {$slug} theme.",
                'gridClass' => 'form-grid-2',
                'fields' => $fields,
            ];
        }

        return array_merge($this->pageMeta(), [
            'fieldGroups' => $fieldGroups,
            'formAction' => 'save',
            'submitLabel' => 'Save Defaults',
        ]);
    }

    public function save(): void
    {
        $this->authorizePermission('settings.theme-colors.manage');

        $rules = [];
        foreach ($this->colors as $slug => $values) {
            foreach (array_keys($values) as $key) {
                $rules["colors.{$slug}.{$key}"] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
            }
        }
        $this->validate($rules);

        foreach ($this->colors as $slug => $values) {
            foreach ($values as $key => $value) {
                ThemeColorDefault::query()->updateOrCreate(
                    ['theme_slug' => $slug, 'variable_key' => $key],
                    ['value' => $value]
                );
            }
        }

        $this->toast('Theme color defaults saved successfully.');
    }
}
