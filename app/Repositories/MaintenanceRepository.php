<?php

namespace App\Repositories;

use App\Models\MaintenanceWindow;

class MaintenanceRepository
{
    public function current(): ?MaintenanceWindow
    {
        return MaintenanceWindow::query()->latest('updated_at')->first();
    }
}
