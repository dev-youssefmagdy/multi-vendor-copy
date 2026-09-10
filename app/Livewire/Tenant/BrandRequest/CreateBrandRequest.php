<?php

namespace App\Livewire\Tenant\BrandRequest;

use App\Enums\BrandRequestStatus;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\BrandRequest;
use Livewire\WithFileUploads;

class CreateBrandRequest extends TenantPage
{
    use InteractsWithTenantUi, WithFileUploads;

    public string $title = '';
    public string $description = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $files = [];

    protected function pageMeta(): array
    {
        return [
            'pageTitle' => 'New Brand Request',
            'badge' => 'Brands',
            'pageDescription' => 'Submit a new brand request. Our team will review it and update the status.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.brand-request.create';
    }

    protected function pageData(): array
    {
        return parent::pageData();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:8000'],
            'files' => ['array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,zip'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $tenantId = tenant('id');

        $storedPaths = [];
        foreach ($this->files as $file) {
            $storedPaths[] = $file->store("brand-requests/{$tenantId}", 'public');
        }

        BrandRequest::create([
            'tenant_id' => $tenantId,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'attachments' => $storedPaths ?: null,
            'status' => BrandRequestStatus::Pending->value,
        ]);

        session()->flash('status', 'Brand request submitted successfully. We will update you when the status changes.');
        $this->redirect(route('tenant.brand-requests.index'));
    }
}
