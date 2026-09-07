<?php

namespace App\Livewire\Tenant\ProductRequest;

use App\Events\ProductRequestMessageSent;
use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ProductRequest;
use App\Models\ProductRequestMessage;
use App\Services\AdminNotificationService;
use Livewire\WithFileUploads;

class CreateRequest extends ContentPage
{
    use InteractsWithTenantUi, WithFileUploads;

    public string $title       = '';
    public string $description = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array  $files       = [];

    protected function pageMeta(): array
    {
        return [
            'title'       => 'New Product Request',
            'badge'       => 'Catalog',
            'description' => 'Describe the product you want added to the Neozena catalog.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.product-request.create';
    }

    protected function pageData(): array
    {
        return parent::pageData();
    }

    protected function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:8000'],
            'files'       => ['array', 'max:5'],
            'files.*'     => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,zip'],
        ];
    }

    public function submit(AdminNotificationService $notificationService): void
    {
        $validated = $this->validate();

        $tenant     = tenant();
        $tenantId   = $tenant->getTenantKey();
        $tenantName = $tenant->getAttribute('shop_name') ?? $tenant->getAttribute('name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;

        $storedPaths = [];
        foreach ($this->files as $file) {
            $storedPaths[] = $file->store("product-requests/{$tenantId}", 'public');
        }

        $request = tenancy()->central(function () use ($validated, $tenantId, $senderName, $storedPaths) {
            $req = ProductRequest::create([
                'tenant_id'        => $tenantId,
                'title'            => $validated['title'],
                'description'      => $validated['description'],
                'attachments'      => $storedPaths ?: null,
                'status'           => 'pending',
                'admin_has_unread' => true,
                'last_reply_at'    => now(),
            ]);

            ProductRequestMessage::create([
                'product_request_id' => $req->id,
                'sender_type'        => 'tenant',
                'sender_name'        => $senderName,
                'body'               => $validated['description'],
                'attachments'        => $storedPaths ?: null,
            ]);

            return $req;
        });

        $notificationService->notify(
            'product_request',
            'New Product Request',
            "{$tenantName} submitted a product request: {$request->title}",
            ['request_id' => $request->id, 'tenant_id' => $tenantId],
        );

        event(new ProductRequestMessageSent(
            requestId:  $request->id,
            tenantId:   $tenantId,
            senderType: 'tenant',
            senderName: $senderName,
            body:       $validated['description'],
            sentAt:     now()->toIso8601String(),
        ));

        $this->toast('Product request submitted successfully.');
        $this->redirect(route('tenant.product-requests.show', $request->id));
    }
}
