<?php

namespace App\Livewire\Admin;

use App\Repositories\DashboardRepository;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $repository = app(DashboardRepository::class);

        return view('livewire.admin.dashboard', [
            'stats' => $repository->stats(),
            'series' => $repository->revenueSeries(),
            'latestPayments' => $repository->latestPayments(),
        ]);
    }
}
