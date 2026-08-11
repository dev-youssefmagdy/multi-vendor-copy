<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemePart;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class AddEditThemePart extends TenantPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    // ── Route / query params ──────────────────────────────────────────────────
    public ?int $partId = null;
    public ?int $themeId = null;

    #[Url(as: 'type', except: '')]
    public string $type = 'header';

    // ── Form fields ───────────────────────────────────────────────────────────
    public string $name = '';
    public string $content = '';
    public ?string $imagePath = null;
    public $imageUpload = null;
    public bool $isActive = true;
    public bool $isDefault = false;
    public bool $isCustomized = false;
    public ?int $centralPartId = null;

    public function mount(?int $themeId = null, ?int $partId = null): void
    {
        if ($partId !== null) {
            $part = ThemePart::findOrFail($partId);
            $this->partId = $part->id;
            $this->themeId = $part->theme_id;
            $this->type = $part->type;
            $this->name = $part->name;
            $this->content = $part->content ?? '';
            $this->imagePath = $part->image;
            $this->isActive = (bool) $part->is_active;
            $this->isDefault = (bool) $part->is_default;
            $this->isCustomized = (bool) $part->is_customized;
            $this->centralPartId = $part->central_part_id;
            return;
        }

        if ($themeId !== null) {
            $this->themeId = $themeId;
            Theme::findOrFail($this->themeId);
        }
    }

    protected function pageMeta(): array
    {
        return [
            'title' => $this->partId ? 'Edit Theme Part' : 'New Theme Part',
            'badge' => 'Storefront',
            'description' => $this->partId
                ? 'Update the theme part content and image.'
                : 'Add a new header or footer to your storefront theme.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.add-edit-theme-part';
    }

    public function updatedImageUpload(): void
    {
        $this->validate(['imageUpload' => ['nullable', 'image', 'max:2048']]);
    }

    public function removeImage(): void
    {
        $this->imagePath = null;
        $this->imageUpload = null;
    }

    public function save()
    {
        $this->validate([
            'themeId' => ['required', 'integer', 'exists:themes,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', ThemePart::TYPES)],
            'content' => ['nullable', 'string'],
            'imageUpload' => ['nullable', 'image', 'max:2048'],
            'isActive' => ['boolean'],
            'isDefault' => ['boolean'],
        ]);

        // Handle image upload (tenant-scoped storage).
        $imagePath = $this->imagePath;
        if ($this->imageUpload) {
            $stored = $this->imageUpload->store('template-parts/images', 'public');
            $imagePath = tenant_asset($stored);
        } elseif ($this->imagePath === null) {
            $imagePath = null;
        }

        if ($this->partId) {
            $part = ThemePart::findOrFail($this->partId);
            $part->fill([
                'name' => $this->name,
                'content' => $this->content,
                'image' => $imagePath,
                'is_active' => $this->isActive,
                'is_customized' => true, // editing a part always marks it customised
            ]);
            $part->save();
        } else {
            $slug = $this->ensureUniqueSlug($this->themeId, Str::slug($this->name) ?: $this->type . '-' . uniqid());
            $part = ThemePart::create([
                'theme_id' => $this->themeId,
                'type' => $this->type,
                'name' => $this->name,
                'slug' => $slug,
                'content' => $this->content,
                'image' => $imagePath,
                'is_active' => $this->isActive,
                'is_default' => false,
                'is_customized' => true,
                'central_part_id' => null,
            ]);
        }

        if ($this->isDefault) {
            ThemePart::query()
                ->where('theme_id', $part->theme_id)
                ->where('type', $part->type)
                ->where('id', '!=', $part->id)
                ->update(['is_default' => false]);
            $part->forceFill(['is_default' => true, 'is_active' => true])->save();
        }

        session()->flash('status', $this->partId ? 'Theme part updated.' : 'Theme part created.');

        return redirect()->route('tenant.store.theme-parts', ['theme' => $this->themeId]);
    }

    protected function ensureUniqueSlug(int $themeId, string $slug): string
    {
        $base = Str::slug($slug) ?: 'part';
        $candidate = $base;
        $i = 2;

        while (
            ThemePart::query()
                ->where('theme_id', $themeId)
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'isEditing' => $this->partId !== null,
            'theme' => $this->themeId ? Theme::find($this->themeId) : null,
            'themes' => Theme::orderBy('name')->get(),
            'typeOptions' => array_combine(ThemePart::TYPES, array_map('ucfirst', ThemePart::TYPES)),
        ]);
    }
}
