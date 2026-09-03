<?php

namespace App\Livewire\Tenant;

use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('layouts.tenant')]
    public function render()
    {
        $repository = app(TenantPanelRepository::class);

        return view('livewire.tenant.dashboard.index', [
            'overview' => $repository->dashboardOverview(),
        ]);
    }
}
