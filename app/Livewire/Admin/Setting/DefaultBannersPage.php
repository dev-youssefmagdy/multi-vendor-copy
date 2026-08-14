<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\DefaultBanner;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DefaultBannersPage extends Component
{
    use AuthorizesAdminPermissions;
    use WithFileUploads;

    // ── Modal state ────────────────────────────────────────────────────────
    public bool $modalOpen = false;
    public ?int $bannerId = null;

    // ── Form fields ────────────────────────────────────────────────────────
    public string $bannerUrl = '';
    public int $bannerSerial = 0;
    public $bannerImage = null;
    public array $bannerTranslations = [];

    public function mount(): void
    {
        $this->authorizePermission('settings.default-banners.manage');
        $this->initTranslations();
    }

    // ── Open / close ───────────────────────────────────────────────────────

    public function openModal(?int $id = null): void
    {
        $this->resetForm();

        if ($id) {
            $banner = DefaultBanner::query()->with('translations.language')->findOrFail($id);
            $this->bannerId = $banner->id;
            $this->bannerUrl = (string) ($banner->url ?? '');
            $this->bannerSerial = (int) $banner->serial_number;
            $this->bannerTranslations = array_replace_recursive(
                $this->bannerTranslations,
                $banner->translationsByLocale(['title', 'subtitle', 'button_text'])
            );
        }

        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->resetForm();
    }

    // ── Save ───────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->authorizePermission('settings.default-banners.manage');

        $rules = [
            'bannerUrl' => ['nullable', 'url', 'max:500'],
            'bannerSerial' => ['required', 'integer', 'min:0'],
            'bannerImage' => ['nullable', 'image', 'max:3072'],
        ];
        foreach (array_keys($this->bannerTranslations) as $locale) {
            $rules["bannerTranslations.{$locale}.title"] = ['nullable', 'string', 'max:255'];
            $rules["bannerTranslations.{$locale}.subtitle"] = ['nullable', 'string', 'max:500'];
            $rules["bannerTranslations.{$locale}.button_text"] = ['nullable', 'string', 'max:100'];
        }
        $this->validate($rules);

        $imagePath = $this->bannerId
            ? DefaultBanner::query()->find($this->bannerId)?->image_path
            : null;

        if ($this->bannerImage) {
            $imagePath = $this->bannerImage->store('default-banners', 'public');
            $imagePath = Storage::disk('public')->url($imagePath);
        }

        $banner = $this->bannerId
            ? DefaultBanner::query()->findOrFail($this->bannerId)
            : new DefaultBanner();

        $banner->fill([
            'url' => $this->bannerUrl ?: null,
            'image_path' => $imagePath,
            'serial_number' => $this->bannerSerial,
        ])->save();

        $banner->syncTranslations($this->bannerTranslations);

        $this->modalOpen = false;
        $this->resetForm();
        $this->dispatch('admin-toast', message: $this->bannerId ? 'Banner updated.' : 'Banner created.', type: 'success');
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        $this->authorizePermission('settings.default-banners.manage');
        DefaultBanner::query()->findOrFail($id)->delete();
        $this->dispatch('admin-toast', message: 'Banner deleted.', type: 'success');
    }

    // ── Reorder ────────────────────────────────────────────────────────────

    public function updateOrder(array $orderedIds): void
    {
        $this->authorizePermission('settings.default-banners.manage');

        foreach ($orderedIds as $index => $id) {
            DefaultBanner::query()->where('id', (int) $id)->update(['serial_number' => $index]);
        }

        $this->dispatch('admin-toast', message: 'Banner order saved.', type: 'success');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function initTranslations(): void
    {
        $languages = Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get();
        $this->bannerTranslations = $languages->mapWithKeys(fn($l) => [
            $l->code => ['title' => '', 'subtitle' => '', 'button_text' => ''],
        ])->all();
    }

    protected function resetForm(): void
    {
        $this->bannerId = null;
        $this->bannerUrl = '';
        $this->bannerSerial = 0;
        $this->bannerImage = null;
        $this->initTranslations();
        $this->resetErrorBag();
    }

    // ── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.admin.setting.default-banners-page', [
            'banners' => DefaultBanner::query()
                ->with('translations.language')
                ->orderBy('serial_number')
                ->orderBy('id')
                ->get(),
            'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderByDesc('is_default')->get(),
        ]);
    }
}
