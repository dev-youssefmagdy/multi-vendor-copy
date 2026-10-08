<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\BladeTheme;
use App\Services\Tenant\BladeThemeService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BladeThemeQueuePage extends Component
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
            'title' => 'Approve this theme?',
            'text' => 'The tenant will be able to activate it on their storefront. This does not activate it automatically.',
            'confirmButtonText' => 'Approve',
        ]);
    }

    public function approve(int $id): void
    {
        $this->authorizePermission('settings.templates.manage');

        $reviewerName = auth('admin')->user()?->name ?? auth('admin')->user()?->email ?? 'admin';

        // Delegate to the service — it handles DB update, symlink re-link (if already
        // active), and keeps the logic in one place rather than split across admin UI.
        app(BladeThemeService::class)->approve($id, $reviewerName);

        // Create the 'custom' Theme variant row in THIS tenant's own DB so the
        // variant card shows up on Store → Appearance → Themes immediately.
        $theme = BladeTheme::query()->find($id);
        $tenantModel = $theme ? \App\Models\Tenant::query()->find($theme->tenant_id) : null;
        if ($tenantModel) {
            tenancy()->initialize($tenantModel);
            \App\Models\Tenant\Theme::query()->firstOrCreate(
                ['slug' => 'custom'],
                ['name' => 'Custom Theme', 'is_universal' => true, 'is_active' => false]
            );
            tenancy()->end();
        }

        $this->toast('Theme approved. The tenant can now activate it as a variant.');
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

        $theme = BladeTheme::query()->findOrFail($this->rejectingId);
        $theme->update([
            'status' => BladeTheme::STATUS_REJECTED,
            'is_active' => false,
            'rejection_reason' => $this->rejectionReason,
            'reviewed_at' => now(),
            'reviewed_by' => auth('admin')->user()?->name ?? auth('admin')->user()?->email,
        ]);

        $this->closeReject();
        $this->toast('Theme rejected.');
    }

    public function render()
    {
        $themes = BladeTheme::query()
            ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
            ->with('tenant')
            ->latest('id')
            ->paginate(20);

        $stats = [
            'pending' => BladeTheme::query()->where('status', BladeTheme::STATUS_PENDING)->count(),
            'approved' => BladeTheme::query()->where('status', BladeTheme::STATUS_APPROVED)->count(),
            'rejected' => BladeTheme::query()->where('status', BladeTheme::STATUS_REJECTED)->count(),
        ];

        return view('livewire.admin.setting.blade-theme-queue-page', [
            'themes' => $themes,
            'stats' => $stats,
        ]);
    }
}
