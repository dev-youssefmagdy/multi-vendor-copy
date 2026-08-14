<?php

namespace App\Livewire\Tenant\Store;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\CustomTemplate;
use App\Services\Tenant\CustomTemplateService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

class CustomTemplatePage extends TenantPage
{
    use InteractsWithTenantUi;
    use WithFileUploads;

    public $templateZip = null;
    public ?string $previewVersion = null;

    public function mount(): void
    {
        //
    }

    public function preview(string $version): void
    {
        $this->previewVersion = $version;
    }

    public function closePreview(): void
    {
        $this->previewVersion = null;
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.store.custom-template-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Custom Template',
            'badge' => 'Storefront',
            'description' => 'Upload your own HTML/CSS/JS storefront template and preview it before going live.',
        ];
    }

    protected function pageData(): array
    {
        $tenantId = (string) tenant()->getTenantKey();

        $templates = tenancy()->central(fn() => CustomTemplate::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->get());

        return array_merge(parent::pageData(), [
            'templates' => $templates,
            'activeTemplate' => $templates->firstWhere('is_active', true),
        ]);
    }

    public function upload(CustomTemplateService $service): void
    {
        $this->validate([
            'templateZip' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ]);

        try {
            $service->uploadAndExtract(tenant(), $this->templateZip);
        } catch (RuntimeException $e) {
            $this->addError('templateZip', $e->getMessage());
            return;
        } catch (Throwable $e) {
            Log::error('Custom template upload failed', ['error' => $e->getMessage()]);
            $this->addError('templateZip', 'The template could not be processed. Please check the ZIP contents and try again.');
            return;
        }

        $this->templateZip = null;
        $this->toast('Template uploaded and queued for admin review.');
    }

    public function activate(int $templateId, CustomTemplateService $service): void
    {
        $tenantId = (string) tenant()->getTenantKey();

        try {
            $service->activate($tenantId, $templateId);
        } catch (Throwable $e) {
            $this->dispatch('admin-toast', message: 'Only approved templates can be activated.', type: 'error');
            return;
        }

        $this->toast('Custom template activated. Your storefront now uses your uploaded template.');
    }

    public function deactivate(CustomTemplateService $service): void
    {
        $service->deactivate((string) tenant()->getTenantKey());
        $this->toast('Custom template deactivated. Your storefront now uses the system theme again.');
    }

    public function delete(int $templateId): void
    {
        tenancy()->central(function () use ($templateId) {
            $tenantId = (string) tenant()->getTenantKey();
            $template = CustomTemplate::query()->where('tenant_id', $tenantId)->findOrFail($templateId);

            if ($template->is_active) {
                return;
            }

            $path = storage_path('app/' . $template->storage_path);
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }

            $template->delete();
        });

        $this->toast('Template deleted.');
    }
}
