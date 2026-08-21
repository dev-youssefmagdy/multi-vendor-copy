<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\BladeTheme;
use App\Services\Tenant\BladeThemeService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

class BladeThemePage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    public $themeZip = null;

    public function mount(): void
    {
        //
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.blade-theme-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Blade Theme',
            'badge' => 'Storefront',
            'description' => 'Upload your own Blade storefront theme. Every upload is reviewed by our team before it can go live.',
        ];
    }

    protected function pageData(): array
    {
        $tenantId = (string) tenant()->getTenantKey();
        $themes = app(BladeThemeService::class)->themesForTenant($tenantId);

        return array_merge(parent::pageData(), [
            'themes' => $themes,
            'activeTheme' => $themes->first(fn(BladeTheme $t) => $t->is_active),
        ]);
    }

    public function upload(BladeThemeService $service): void
    {
        $this->validate([
            'themeZip' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        try {
            $service->upload(tenant(), $this->themeZip);
        } catch (RuntimeException $e) {
            $this->addError('themeZip', $e->getMessage());
            return;
        } catch (Throwable $e) {
            Log::error('Blade theme upload failed', ['error' => $e->getMessage()]);
            $this->addError('themeZip', 'The theme could not be processed. Please check the ZIP contents and try again.');
            return;
        }

        $this->themeZip = null;
        $this->toast('Theme uploaded and queued for admin review.');
    }

    public function activate(int $themeId, BladeThemeService $service): void
    {
        $tenantId = (string) tenant()->getTenantKey();

        try {
            $service->activate($tenantId, $themeId);
        } catch (Throwable $e) {
            $this->dispatch('admin-toast', message: 'Only approved themes can be activated.', type: 'error');
            return;
        }

        $this->toast('Blade theme activated. Your storefront now uses your uploaded theme.');
    }

    public function deactivate(BladeThemeService $service): void
    {
        $service->deactivate((string) tenant()->getTenantKey());
        $this->toast('Blade theme deactivated. Your storefront now uses the system theme again.');
    }

    public function delete(int $themeId): void
    {
        tenancy()->central(function () use ($themeId) {
            $tenantId = (string) tenant()->getTenantKey();
            $theme = BladeTheme::query()->where('tenant_id', $tenantId)->findOrFail($themeId);

            if ($theme->is_active) {
                return;
            }

            $path = storage_path('app/' . $theme->storage_path);
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }

            $theme->delete();
        });

        $this->toast('Theme deleted.');
    }
}
