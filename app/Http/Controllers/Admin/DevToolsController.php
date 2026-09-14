<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductFixedShippingCosts;
use Illuminate\Support\Facades\Artisan;

class DevToolsController extends Controller
{
    public function syncProductFixedShippingCosts(): void
    {
        SyncProductFixedShippingCosts::dispatch();
    }

    public function importNeozenaProducts(): void
    {
        for ($i = 0; $i <= 50; $i++) {
            Artisan::call('neozena:import', ['--page' => $i, '--only-page' => true]);
        }
    }
}
