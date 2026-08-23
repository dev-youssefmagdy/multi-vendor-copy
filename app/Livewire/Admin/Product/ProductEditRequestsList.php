<?php

namespace App\Livewire\Admin\Product;

use App\Enums\ProductEditRequestStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\ProductEditRequest;
use App\Models\Tenant;
use App\Models\Tenant\Product;
use App\Services\TenantNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class ProductEditRequestsList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending'; // all | pending | approved | rejected
    public string $tenantFilter = '';

    // Reject modal state
    public ?int $rejectingId = null;
    public string $adminNote = '';

    // ── Filter watchers ──────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedTenantFilter(): void
    {
        $this->resetPage();
    }

    // ── Actions ──────────────────────────────────────────────────────────────

    public function approve(int $id): void
    {
        $this->authorizePermission('catalog.product-edit-requests.manage');

        /** @var ProductEditRequest $request */
        $request = ProductEditRequest::query()->findOrFail($id);

        if ($request->status !== ProductEditRequestStatus::Pending) {
            $this->dispatch('admin-toast', message: 'This request has already been reviewed.', type: 'warning');
            return;
        }

        $tenant = Tenant::query()->find($request->tenant_id);
        if (!$tenant) {
            $this->dispatch('admin-toast', message: 'Tenant not found.', type: 'error');
            return;
        }

        try {
            tenancy()->initialize($tenant);

            $product = Product::query()->find($request->product_id);

            if ($product) {
                // Load ALL current translations so we can merge without losing
                // fields like meta_keywords/meta_description or other locales.
                $fullTranslations = $product->translationsByLocale(
                    ['name', 'description', 'meta_keywords', 'meta_description', 'slug']
                );

                foreach ($request->requested_translations as $locale => $fields) {
                    if (!isset($fullTranslations[$locale])) {
                        $fullTranslations[$locale] = [];
                    }
                    if (array_key_exists('name', $fields)) {
                        $fullTranslations[$locale]['name'] = $fields['name'];
                    }
                    if (array_key_exists('description', $fields)) {
                        $fullTranslations[$locale]['description'] = $fields['description'];
                    }
                }

                $product->syncTranslations($fullTranslations);
                $product->update(['has_custom_translations' => true]);
            }
        } catch (Throwable $e) {
            tenancy()->end();
            $this->dispatch('admin-toast', message: 'Failed to apply changes: ' . $e->getMessage(), type: 'error');
            return;
        } finally {
            tenancy()->end();
        }

        $request->update([
            'status' => ProductEditRequestStatus::Approved,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        // Notify the vendor
        app(TenantNotificationService::class)->notify(
            $tenant,
            'product_edit_approved',
            'Product Edit Approved',
            "Your edit request for product \"{$request->product_slug}\" has been approved and the changes are now live.",
        );

        $this->dispatch('admin-toast', message: 'Request approved and product updated.', type: 'success');
    }

    public function resetToCentral(int $id): void
    {
        $this->authorizePermission('catalog.product-edit-requests.manage');

        /** @var ProductEditRequest $request */
        $request = ProductEditRequest::query()->findOrFail($id);

        if ($request->status !== ProductEditRequestStatus::Approved) {
            $this->dispatch('admin-toast', message: 'Only approved requests can be reset.', type: 'warning');
            return;
        }

        $tenant = Tenant::query()->find($request->tenant_id);
        if (!$tenant) {
            $this->dispatch('admin-toast', message: 'Tenant not found.', type: 'error');
            return;
        }

        try {
            tenancy()->initialize($tenant);

            $product = Product::query()->find($request->product_id);

            if ($product) {
                $fullTranslations = $product->translationsByLocale(
                    ['name', 'description', 'meta_keywords', 'meta_description', 'slug']
                );

                foreach ($request->current_translations as $locale => $fields) {
                    if (!isset($fullTranslations[$locale])) {
                        $fullTranslations[$locale] = [];
                    }
                    if (array_key_exists('name', $fields)) {
                        $fullTranslations[$locale]['name'] = $fields['name'];
                    }
                    if (array_key_exists('description', $fields)) {
                        $fullTranslations[$locale]['description'] = $fields['description'];
                    }
                }

                $product->syncTranslations($fullTranslations);
                $product->update(['has_custom_translations' => false]);
            }
        } catch (Throwable $e) {
            tenancy()->end();
            $this->dispatch('admin-toast', message: 'Failed to reset translations: ' . $e->getMessage(), type: 'error');
            return;
        } finally {
            tenancy()->end();
        }

        $this->dispatch('admin-toast', message: 'Product translations reset to central values.', type: 'success');
    }

    public function openRejectModal(int $id): void
    {
        $this->authorizePermission('catalog.product-edit-requests.manage');
        $this->rejectingId = $id;
        $this->adminNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->adminNote = '';
    }

    public function confirmReject(): void
    {
        $this->authorizePermission('catalog.product-edit-requests.manage');

        if (!$this->rejectingId) {
            return;
        }

        $this->validate(['adminNote' => ['nullable', 'string', 'max:500']]);

        /** @var ProductEditRequest $request */
        $request = ProductEditRequest::query()->findOrFail($this->rejectingId);

        if ($request->status !== ProductEditRequestStatus::Pending) {
            $this->dispatch('admin-toast', message: 'This request has already been reviewed.', type: 'warning');
            $this->cancelReject();
            return;
        }

        $request->update([
            'status' => ProductEditRequestStatus::Rejected,
            'admin_note' => $this->adminNote ?: null,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $tenant = Tenant::query()->find($request->tenant_id);
        if ($tenant) {
            $noteText = filled($this->adminNote) ? " Reason: {$this->adminNote}" : '';
            app(TenantNotificationService::class)->notify(
                $tenant,
                'product_edit_rejected',
                'Product Edit Rejected',
                "Your edit request for product \"{$request->product_slug}\" has been rejected.{$noteText}",
            );
        }

        $this->cancelReject();
        $this->dispatch('admin-toast', message: 'Request rejected.', type: 'info');
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $query = ProductEditRequest::query()->with('tenant');

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('product_slug', 'like', '%' . $this->search . '%')
                    ->orWhere('tenant_id', 'like', '%' . $this->search . '%');
            });
        }

        if (filled($this->tenantFilter)) {
            $query->where('tenant_id', $this->tenantFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $records = $query->latest()->paginate(20);

        $stats = [
            'total' => ProductEditRequest::count(),
            'pending' => ProductEditRequest::where('status', ProductEditRequestStatus::Pending)->count(),
            'approved' => ProductEditRequest::where('status', ProductEditRequestStatus::Approved)->count(),
            'rejected' => ProductEditRequest::where('status', ProductEditRequestStatus::Rejected)->count(),
        ];

        return view('livewire.admin.product.product-edit-requests-list', [
            'records' => $records,
            'stats' => $stats,
            'canManage' => $this->hasPermission('catalog.product-edit-requests.manage'),
            'tenants' => Tenant::query()->orderBy('id')->get(),
        ]);
    }
}
