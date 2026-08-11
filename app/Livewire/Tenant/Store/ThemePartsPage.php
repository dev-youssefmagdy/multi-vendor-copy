<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemePart;
use Livewire\Attributes\Url;

class ThemePartsPage extends TenantPage
{
    use InteractsWithTenantUi;

    #[Url(as: 'theme', except: null)]
    public ?int $themeId = null;

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    public function mount(): void
    {
        // Permission is enforced by route middleware.
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Theme Parts',
            'badge' => 'Storefront',
            'description' => 'Manage headers and footers for your storefront theme.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.theme-parts-page';
    }

    protected function pageData(): array
    {
        $themes = Theme::orderBy('name')->get();
        $theme = $this->themeId ? $themes->firstWhere('id', $this->themeId) : null;

        $query = ThemePart::with('theme')->orderBy('type')->orderBy('name');

        if ($this->themeId) {
            $query->where('theme_id', $this->themeId);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        $parts = $query->get();

        $stats = [
            'total' => $parts->count(),
            'headers' => $parts->where('type', 'header')->count(),
            'footers' => $parts->where('type', 'footer')->count(),
            'customised' => $parts->where('is_customized', true)->count(),
        ];

        return array_merge(parent::pageData(), compact('themes', 'theme', 'parts', 'stats'));
    }

    public function selectTheme(?int $id): void
    {
        $this->themeId = $id;
        $this->typeFilter = '';
    }

    public function setDefault(int $partId): void
    {
        $part = ThemePart::findOrFail($partId);

        ThemePart::query()
            ->where('theme_id', $part->theme_id)
            ->where('type', $part->type)
            ->where('id', '!=', $part->id)
            ->update(['is_default' => false]);

        $part->forceFill(['is_default' => true, 'is_active' => true])->save();
        $this->toast('Set as default successfully.');
    }

    public function toggleActive(int $partId): void
    {
        $part = ThemePart::findOrFail($partId);
        $part->is_active = !$part->is_active;
        $part->save();
        $this->toast($part->is_active ? 'Part activated.' : 'Part deactivated.');
    }

    public function revertToCentral(int $partId): void
    {
        $part = ThemePart::findOrFail($partId);

        if (!$part->central_part_id) {
            $this->toast('This part has no central template to reset from.', 'error');
            return;
        }

        // Cross the tenancy boundary to fetch the live central part data.
        $central = tenancy()->central(
            fn() => \App\Models\TemplatePart::find($part->central_part_id)
        );

        if (!$central) {
            $this->toast('Central template part no longer exists.', 'error');
            return;
        }

        $part->forceFill([
            'name' => $central->name,
            'content' => $central->content,
            'image' => $central->image,
            'is_active' => (bool) $central->is_active,
            'is_default' => (bool) $central->is_default,
            'is_customized' => false,
        ])->save();

        // Re-demote siblings if this part just became the default.
        if ($central->is_default) {
            ThemePart::query()
                ->where('theme_id', $part->theme_id)
                ->where('type', $part->type)
                ->where('id', '!=', $part->id)
                ->update(['is_default' => false]);
        }

        $this->toast('Reset to central template content successfully.');
    }

    public function requestDelete(int $partId): void
    {
        $this->confirmAction('deletePart', [$partId], [
            'title' => 'Delete Theme Part?',
            'text' => 'This part will be permanently deleted.',
            'confirmButtonText' => 'Delete',
            'icon' => 'warning',
        ]);
    }

    public function deletePart(int $partId): void
    {
        ThemePart::findOrFail($partId)->delete();
        $this->toast('Theme part deleted.');
    }
}
