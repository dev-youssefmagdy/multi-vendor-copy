<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

class CacheAdminService
{
    public function clearMain(): array
    {
        Artisan::call('optimize:clear');

        return [
            'ok' => true,
            'output' => Artisan::output(),
        ];
    }

    public function clearTenants(): array
    {
        Artisan::call('cache:clear');

        return [
            'ok' => true,
            'output' => Artisan::output(),
        ];
    }
}
