<?php

namespace App\Services;

use App\Models\MaintenanceWindow;

class MaintenanceService
{
    public function save(array $attributes): MaintenanceWindow
    {
        $record = MaintenanceWindow::query()->latest('id')->first() ?? new MaintenanceWindow();
        $record->fill($attributes);
        $record->save();

        return $record->fresh();
    }
}
