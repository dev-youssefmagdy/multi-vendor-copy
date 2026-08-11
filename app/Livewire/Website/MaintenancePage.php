<?php

namespace App\Livewire\Website;

use App\Repositories\MaintenanceRepository;
use Livewire\Component;

class MaintenancePage extends Component
{
    public function render()
    {
        $window = app(MaintenanceRepository::class)->current();

        return view('livewire.website.maintenance', [
            'window' => $window,
        ])->layout('layouts.website', ['title' => 'Scheduled Maintenance']);
    }
}
