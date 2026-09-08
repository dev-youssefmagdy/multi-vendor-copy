<?php

namespace App\Livewire\Tenant\Widgets;

use App\Helpers\TenantNavigation;
use App\Services\Tenant\TenantPanelService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Persistent "Account Setup: X% Complete" widget (Prompt 33). Rendered inside
 * the tenant sidebar on every page. Polls lightly so the bar updates in near
 * real time as steps are completed elsewhere in the panel, and can also be
 * refreshed immediately via the `setup-progress-refresh` browser event.
 */
class AccountSetupProgress extends Component
{
    public bool $expanded = false;

    #[On('setup-progress-refresh')]
    public function refresh(): void
    {
        // No-op handler — its presence causes Livewire to re-render on the event.
    }

    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function markPagesReviewed(TenantPanelService $service): void
    {
        $service->markDefaultPagesReviewed();
    }

    public function render()
    {
        return view('livewire.tenant.widgets.account-setup-progress', [
            'progress' => TenantNavigation::setupProgress(),
        ]);
    }
}
