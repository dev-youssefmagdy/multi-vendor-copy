<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\CustomTemplate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomTemplateQueuePage extends Component
{
    use InteractsWithAdminUi;
    use AuthorizesAdminPermissions;
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    public bool $rejectModalOpen = false;
    public ?int $rejectingId = null;
    public string $rejectionReason = '';

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function requestApprove(int $id): void
    {
        $this->authorizePermission('settings.templates.manage');

        $this->confirmAction('approve', [$id], [
            'title' => 'Approve this template?',
            'text' => 'The tenant will be able to activate it on their storefront.',
            'confirmButtonText' => 'Approve',
        ]);
    }

    public function approve(int $id): void
    {
        $this->authorizePermission('settings.templates.manage');

        $template = CustomTemplate::query()->findOrFail($id);
        $template->update([
            'status' => CustomTemplate::STATUS_APPROVED,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => auth('admin')->user()?->name ?? auth('admin')->user()?->email,
        ]);

        $this->toast('Template approved.');
    }

    public function openReject(int $id): void
    {
        $this->authorizePermission('settings.templates.manage');
        $this->rejectingId = $id;
        $this->rejectionReason = '';
        $this->rejectModalOpen = true;
    }

    public function closeReject(): void
    {
        $this->rejectModalOpen = false;
        $this->rejectingId = null;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        $this->authorizePermission('settings.templates.manage');

        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $template = CustomTemplate::query()->findOrFail($this->rejectingId);
        $template->update([
            'status' => CustomTemplate::STATUS_REJECTED,
            'is_active' => false,
            'rejection_reason' => $this->rejectionReason,
            'reviewed_at' => now(),
            'reviewed_by' => auth('admin')->user()?->name ?? auth('admin')->user()?->email,
        ]);

        $this->closeReject();
        $this->toast('Template rejected.');
    }

    public function render()
    {
        $templates = CustomTemplate::query()
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->with('tenant')
            ->latest('id')
            ->paginate(20);

        $stats = [
            'pending' => CustomTemplate::query()->where('status', CustomTemplate::STATUS_PENDING)->count(),
            'approved' => CustomTemplate::query()->where('status', CustomTemplate::STATUS_APPROVED)->count(),
            'rejected' => CustomTemplate::query()->where('status', CustomTemplate::STATUS_REJECTED)->count(),
        ];

        return view('livewire.admin.setting.custom-template-queue-page', [
            'templates' => $templates,
            'stats' => $stats,
        ]);
    }
}
